<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\EntityReferenceSelection\SelectionBase.
 */

namespace Drupal\Core\Entity\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\String;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
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
 * @see \Drupal\Core\Entity\Plugin\Derivative\SelectionBase
 * @see plugin_api
 *
 * @EntityReferenceSelection(
 *   id = "default",
 *   label = @Translation("Default"),
 *   group = "default",
 *   weight = 0,
 *   deriver = "Drupal\Core\Entity\Plugin\Derivative\SelectionBase"
 * )
 */
class SelectionBase extends PluginBase implements SelectionInterface, ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
   * Constructs a new SelectionBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $this->configuration['target_type'];
    $selection_handler_settings = $this->configuration['handler_settings'];
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    $bundles = $this->entityManager->getBundleInfo($entity_type_id);

    // Merge-in default values.
    $selection_handler_settings += array(
      'target_bundles' => array(),
      'sort' => array(
        'field' => '_none',
      ),
      'auto_create' => FALSE,
    );

    if ($entity_type->hasKey('bundle')) {
      $bundle_options = array();
      foreach ($bundles as $bundle_name => $bundle_info) {
        $bundle_options[$bundle_name] = $bundle_info['label'];
      }

      $target_bundles_title = $this->t('Bundles');
      // Default core entity types with sensible labels.
      if ($entity_type_id == 'node') {
        $target_bundles_title = $this->t('Content types');
      }
      elseif ($entity_type_id == 'taxonomy_term') {
        $target_bundles_title = $this->t('Vocabularies');
      }

      $form['target_bundles'] = array(
        '#type' => 'checkboxes',
        '#title' => $target_bundles_title,
        '#options' => $bundle_options,
        '#default_value' => (!empty($selection_handler_settings['target_bundles'])) ? $selection_handler_settings['target_bundles'] : array(),
        '#required' => TRUE,
        '#size' => 6,
        '#multiple' => TRUE,
        '#element_validate' => array('_entity_reference_element_validate_filter'),
      );
    }
    else {
      $form['target_bundles'] = array(
        '#type' => 'value',
        '#value' => array(),
      );
    }

    if ($entity_type->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')) {
      $fields = array();
      foreach (array_keys($bundles) as $bundle) {
        $bundle_fields = array_filter($this->entityManager->getFieldDefinitions($entity_type_id, $bundle), function ($field_definition) {
          return !$field_definition->isComputed();
        });
        foreach ($bundle_fields as $field_name => $field_definition) {
          /* @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
          $columns = $field_definition->getFieldStorageDefinition()->getColumns();
          // If there is more than one column, display them all, otherwise just
          // display the field label.
          // @todo: Use property labels instead of the column name.
          if (count($columns) > 1) {
            foreach ($columns as $column_name => $column_info) {
              $fields[$field_name . '.' . $column_name] = $this->t('@label (@column)', array('@label' => $field_definition->getLabel(), '@column' => $column_name));
            }
          }
          else {
            $fields[$field_name] = $this->t('@label', array('@label' => $field_definition->getLabel()));
          }
        }
      }

      $form['sort']['field'] = array(
        '#type' => 'select',
        '#title' => $this->t('Sort by'),
        '#options' => array(
          '_none' => $this->t('- None -'),
        ) + $fields,
        '#ajax' => TRUE,
        '#limit_validation_errors' => array(),
        '#default_value' => $selection_handler_settings['sort']['field'],
      );

      $form['sort']['settings'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('entity_reference-settings')),
        '#process' => array('_entity_reference_form_process_merge_parent'),
      );

      if ($selection_handler_settings['sort']['field'] != '_none') {
        // Merge-in default values.
        $selection_handler_settings['sort'] += array(
          'direction' => 'ASC',
        );

        $form['sort']['settings']['direction'] = array(
          '#type' => 'select',
          '#title' => $this->t('Sort direction'),
          '#required' => TRUE,
          '#options' => array(
            'ASC' => $this->t('Ascending'),
            'DESC' => $this->t('Descending'),
          ),
          '#default_value' => $selection_handler_settings['sort']['direction'],
        );
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) { }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) { }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->configuration['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return array();
    }

    $options = array();
    $entities = entity_load_multiple($target_type, $result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $options[$bundle][$entity_id] = String::checkPlain($entity->label());
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
    $result = array();
    if ($ids) {
      $target_type = $this->configuration['target_type'];
      $entity_type = $this->entityManager->getDefinition($target_type);
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
  public function validateAutocompleteInput($input, &$element, FormStateInterface $form_state, $form, $strict = TRUE) {
    $bundled_entities = $this->getReferenceableEntities($input, '=', 6);
    $entities = array();
    foreach ($bundled_entities as $entities_list) {
      $entities += $entities_list;
    }
    $params = array(
      '%value' => $input,
      '@value' => $input,
    );
    if (empty($entities)) {
      if ($strict) {
        // Error if there are no entities available for a required field.
        $form_state->setError($element, $this->t('There are no entities matching "%value".', $params));
      }
    }
    elseif (count($entities) > 5) {
      $params['@id'] = key($entities);
      // Error if there are more than 5 matching entities.
      $form_state->setError($element, $this->t('Many entities are called %value. Specify the one you want by appending the id in parentheses, like "@value (@id)".', $params));
    }
    elseif (count($entities) > 1) {
      // More helpful error if there are only a few matching entities.
      $multiples = array();
      foreach ($entities as $id => $name) {
        $multiples[] = $name . ' (' . $id . ')';
      }
      $params['@id'] = $id;
      $form_state->setError($element, $this->t('Multiple entities match this reference; "%multiple". Specify the one you want by appending the id in parentheses, like "@value (@id)".', array('%multiple' => implode('", "', $multiples))));
    }
    else {
      // Take the one and only matching entity.
      return key($entities);
    }
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
  public function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $target_type = $this->configuration['target_type'];
    $handler_settings = $this->configuration['handler_settings'];
    $entity_type = $this->entityManager->getDefinition($target_type);

    $query = $this->entityManager->getStorage($target_type)->getQuery();
    if (!empty($handler_settings['target_bundles'])) {
      $query->condition($entity_type->getKey('bundle'), $handler_settings['target_bundles'], 'IN');
    }

    if (isset($match) && $label_key = $entity_type->getKey('label')) {
      $query->condition($label_key, $match, $match_operator);
    }

    // Add entity-access tag.
    $query->addTag($target_type . '_access');

    // Add the Selection handler for
    // entity_reference_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    // Add the sort option.
    if (!empty($handler_settings['sort'])) {
      $sort_settings = $handler_settings['sort'];
      if ($sort_settings['field'] != '_none') {
        $query->sort($sort_settings['field'], $sort_settings['direction']);
      }
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) { }

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

    $query->alterTags = array($tag => TRUE);
    $query->alterMetaData['base_table'] = $base_table;
    $this->moduleHandler->alter(array('query', 'query_' . $tag), $query);

    // Restore the tags and metadata.
    $query->alterTags = $old_tags;
    $query->alterMetaData = $old_metadata;
  }

}
