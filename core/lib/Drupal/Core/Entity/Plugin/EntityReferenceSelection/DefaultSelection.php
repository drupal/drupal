<?php

namespace Drupal\Core\Entity\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default plugin implementation of the Entity Reference Selection plugin.
 *
 * Also serves as a base class for specific types of Entity Reference
 * Selection plugins.
 *
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager
 * @see \Drupal\Core\Entity\Annotation\EntityReferenceSelection
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
 * @see \Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver
 * @see plugin_api
 *
 * @EntityReferenceSelection(
 *   id = "default",
 *   label = @Translation("Default"),
 *   group = "default",
 *   weight = 0,
 *   deriver = "Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver"
 * )
 */
class DefaultSelection extends SelectionPluginBase implements ContainerFactoryPluginInterface, SelectionWithAutocreateInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  public $entityTypeBundleInfo;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new DefaultSelection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityRepositoryInterface $entity_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // For the 'target_bundles' setting, a NULL value is equivalent to "allow
      // entities from any bundle to be referenced" and an empty array value is
      // equivalent to "no entities from any bundle can be referenced".
      'target_bundles' => NULL,
      'sort' => [
        'field' => '_none',
        'direction' => 'ASC',
      ],
      'auto_create' => FALSE,
      'auto_create_bundle' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $configuration = $this->getConfiguration();
    $entity_type_id = $configuration['target_type'];
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);

    if ($entity_type->hasKey('bundle')) {
      $bundle_options = [];
      foreach ($bundles as $bundle_name => $bundle_info) {
        $bundle_options[$bundle_name] = $bundle_info['label'];
      }
      natsort($bundle_options);
      $selected_bundles = array_intersect_key(
        $bundle_options,
        array_filter((array) $configuration['target_bundles'])
      );

      $form['target_bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $entity_type->getBundleLabel(),
        '#options' => $bundle_options,
        '#default_value' => (array) $configuration['target_bundles'],
        '#required' => TRUE,
        '#size' => 6,
        '#multiple' => TRUE,
        '#element_validate' => [[static::class, 'elementValidateFilter']],
        '#ajax' => TRUE,
        '#limit_validation_errors' => [],
      ];

      $form['target_bundles_update'] = [
        '#type' => 'submit',
        '#value' => $this->t('Update form'),
        '#limit_validation_errors' => [],
        '#attributes' => [
          'class' => ['js-hide'],
        ],
        '#submit' => [[EntityReferenceItem::class, 'settingsAjaxSubmit']],
      ];
    }
    else {
      $form['target_bundles'] = [
        '#type' => 'value',
        '#value' => [],
      ];
    }

    if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      $options = $entity_type->hasKey('bundle') ? $selected_bundles : $bundles;
      $fields = [];
      foreach (array_keys($options) as $bundle) {
        $bundle_fields = array_filter($this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle), function ($field_definition) {
          return !$field_definition->isComputed();
        });
        foreach ($bundle_fields as $field_name => $field_definition) {
          /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
          $columns = $field_definition->getFieldStorageDefinition()->getColumns();
          // If there is more than one column, display them all, otherwise just
          // display the field label.
          // @todo: Use property labels instead of the column name.
          if (count($columns) > 1) {
            foreach ($columns as $column_name => $column_info) {
              $fields[$field_name . '.' . $column_name] = $this->t('@label (@column)', ['@label' => $field_definition->getLabel(), '@column' => $column_name]);
            }
          }
          else {
            $fields[$field_name] = $this->t('@label', ['@label' => $field_definition->getLabel()]);
          }
        }
      }

      $form['sort']['field'] = [
        '#type' => 'select',
        '#title' => $this->t('Sort by'),
        '#options' => $fields,
        '#ajax' => TRUE,
        '#empty_value' => '_none',
        '#sort_options' => TRUE,
        '#limit_validation_errors' => [],
        '#default_value' => $configuration['sort']['field'],
      ];
      if ($entity_type->hasKey('bundle')) {
        $form['sort']['field']['#states'] = [
          'visible' => [
            ':input[name^="settings[handler_settings][target_bundles]["]' => ['checked' => TRUE],
          ],
        ];
      }

      $form['sort']['settings'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['entity_reference-settings']],
        '#process' => [[EntityReferenceItem::class, 'formProcessMergeParent']],
      ];

      $form['sort']['settings']['direction'] = [
        '#type' => 'select',
        '#title' => $this->t('Sort direction'),
        '#required' => TRUE,
        '#options' => [
          'ASC' => $this->t('Ascending'),
          'DESC' => $this->t('Descending'),
        ],
        '#default_value' => $configuration['sort']['direction'],
        '#states' => [
          'visible' => [
            ':input[name="settings[handler_settings][sort][field]"]' => [
              '!value' => '_none',
            ],
          ],
        ],
      ];
      if ($entity_type->hasKey('bundle')) {
        $form['sort']['settings']['direction']['#states']['visible'][] = [
          ':input[name^="settings[handler_settings][target_bundles]["]' => [
            'checked' => TRUE,
          ],
        ];
      }

    }

    $form['auto_create'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Create referenced entities if they don't already exist"),
      '#default_value' => $configuration['auto_create'],
      '#weight' => -2,
    ];

    if ($entity_type->hasKey('bundle')) {
      $form['auto_create_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Store new items in'),
        '#options' => $selected_bundles,
        '#default_value' => $configuration['auto_create_bundle'],
        '#access' => count($selected_bundles) > 1,
        '#states' => [
          'visible' => [
            ':input[name="settings[handler_settings][auto_create]"]' => ['checked' => TRUE],
          ],
        ],
        '#weight' => -1,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    // If no checkboxes were checked for 'target_bundles', store NULL ("all
    // bundles are referenceable") rather than empty array ("no bundle is
    // referenceable" - typically happens when all referenceable bundles have
    // been deleted).
    if ($form_state->getValue(['settings', 'handler_settings', 'target_bundles']) === []) {
      $form_state->setValue(['settings', 'handler_settings', 'target_bundles'], NULL);
    }

    // Don't store the 'target_bundles_update' button value into the field
    // config settings.
    $form_state->unsetValue(['settings', 'handler_settings', 'target_bundles_update']);
  }

  /**
   * Form element validation handler; Filters the #value property of an element.
   */
  public static function elementValidateFilter(&$element, FormStateInterface $form_state) {
    $element['#value'] = array_filter($element['#value']);
    $form_state->setValueForElement($element, $element['#value']);
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->getConfiguration()['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $options[$bundle][$entity_id] = Html::escape($this->entityRepository->getTranslationFromContext($entity)->label());
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    $query = $this->buildEntityQuery($match, $match_operator);
    return $query
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $result = [];
    if ($ids) {
      $target_type = $this->configuration['target_type'];
      $entity_type = $this->entityTypeManager->getDefinition($target_type);
      $query = $this->buildEntityQuery();
      $result = $query
        ->condition($entity_type->getKey('id'), $ids, 'IN')
        ->execute();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $values = [
      $entity_type->getKey('label') => $label,
    ];

    if ($bundle_key = $entity_type->getKey('bundle')) {
      $values[$bundle_key] = $bundle;
    }

    $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values);

    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId($uid);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    return array_filter($entities, function ($entity) {
      $target_bundles = $this->getConfiguration()['target_bundles'];
      if (isset($target_bundles)) {
        return in_array($entity->bundle(), $target_bundles);
      }
      return TRUE;
    });
  }

  /**
   * Builds an EntityQuery to get referenceable entities.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery object with the basic conditions and sorting applied to
   *   it.
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $configuration = $this->getConfiguration();
    $target_type = $configuration['target_type'];
    $entity_type = $this->entityTypeManager->getDefinition($target_type);

    $query = $this->entityTypeManager->getStorage($target_type)->getQuery();

    // If 'target_bundles' is NULL, all bundles are referenceable, no further
    // conditions are needed.
    if (is_array($configuration['target_bundles'])) {
      // If 'target_bundles' is an empty array, no bundle is referenceable,
      // force the query to never return anything and bail out early.
      if ($configuration['target_bundles'] === []) {
        $query->condition($entity_type->getKey('id'), NULL, '=');
        return $query;
      }
      else {
        $query->condition($entity_type->getKey('bundle'), $configuration['target_bundles'], 'IN');
      }
    }

    if (isset($match) && $label_key = $entity_type->getKey('label')) {
      $query->condition($label_key, $match, $match_operator);
    }

    // Add entity-access tag.
    $query->accessCheck(TRUE);
    $query->addTag($target_type . '_access');

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    // Add the sort option.
    if ($configuration['sort']['field'] !== '_none') {
      $query->sort($configuration['sort']['field'], $configuration['sort']['direction']);
    }

    return $query;
  }

  /**
   * Helper method: Passes a query to the alteration system again.
   *
   * This allows Entity Reference to add a tag to an existing query so it can
   * ask access control mechanisms to alter it again.
   */
  protected function reAlterQuery(AlterableInterface $query, $tag, $base_table) {
    // Save the old tags and metadata.
    // For some reason, those are public.
    $old_tags = $query->alterTags;
    $old_metadata = $query->alterMetaData;

    $query->alterTags = [$tag => TRUE];
    $query->alterMetaData['base_table'] = $base_table;
    $this->moduleHandler->alter(['query', 'query_' . $tag], $query);

    // Restore the tags and metadata.
    $query->alterTags = $old_tags;
    $query->alterMetaData = $old_metadata;
  }

}
