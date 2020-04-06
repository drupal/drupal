<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\style\Table;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a actions-based bulk operation form element.
 *
 * @ViewsField("bulk_form")
 */
class BulkForm extends FieldPluginBase implements CacheableDependencyInterface {

  use RedirectDestinationTrait;
  use UncacheableFieldHandlerTrait;
  use EntityTranslationRenderTrait;
  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['entityManager' => 'entity.manager'];

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The action storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $actionStorage;

  /**
   * An array of actions that can be executed.
   *
   * @var \Drupal\system\ActionConfigEntityInterface[]
   */
  protected $actions = [];

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new BulkForm object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, MessengerInterface $messenger, EntityRepositoryInterface $entity_repository = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->actionStorage = $entity_type_manager->getStorage('action');
    $this->languageManager = $language_manager;
    $this->messenger = $messenger;
    if (!$entity_repository) {
      @trigger_error('Calling BulkForm::__construct() with the $entity_repository argument is supported in drupal:8.7.0 and will be required before drupal:9.0.0. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $entity_repository = \Drupal::service('entity.repository');
    }
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
      $container->get('language_manager'),
      $container->get('messenger'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $entity_type = $this->getEntityType();
    // Filter the actions to only include those for this entity type.
    $this->actions = array_filter($this->actionStorage->loadMultiple(), function ($action) use ($entity_type) {
      return $action->getType() == $entity_type;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // @todo Consider making the bulk operation form cacheable. See
    //   https://www.drupal.org/node/2503009.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->languageManager->isMultilingual() ? $this->getEntityTranslationRenderer()->getCacheContexts() : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->getEntityType();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityManager() {
    // This relies on DeprecatedServicePropertyTrait to trigger a deprecation
    // message in case it is accessed.
    return $this->entityManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityRepository() {
    return $this->entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguageManager() {
    return $this->languageManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getView() {
    return $this->view;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['action_title'] = ['default' => $this->t('Action')];
    $options['include_exclude'] = [
      'default' => 'exclude',
    ];
    $options['selected_actions'] = [
      'default' => [],
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['action_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Action title'),
      '#default_value' => $this->options['action_title'],
      '#description' => $this->t('The title shown above the actions dropdown.'),
    ];

    $form['include_exclude'] = [
      '#type' => 'radios',
      '#title' => $this->t('Available actions'),
      '#options' => [
        'exclude' => $this->t('All actions, except selected'),
        'include' => $this->t('Only selected actions'),
      ],
      '#default_value' => $this->options['include_exclude'],
    ];
    $form['selected_actions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Selected actions'),
      '#options' => $this->getBulkOptions(FALSE),
      '#default_value' => $this->options['selected_actions'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    $selected_actions = $form_state->getValue(['options', 'selected_actions']);
    $form_state->setValue(['options', 'selected_actions'], array_values(array_filter($selected_actions)));
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    parent::preRender($values);

    // If the view is using a table style, provide a placeholder for a
    // "select all" checkbox.
    if (!empty($this->view->style_plugin) && $this->view->style_plugin instanceof Table) {
      // Add the tableselect css classes.
      $this->options['element_label_class'] .= 'select-all';
      // Hide the actual label of the field on the table header.
      $this->options['label'] = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
  }

  /**
   * Form constructor for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    // Make sure we do not accidentally cache this form.
    // @todo Evaluate this again in https://www.drupal.org/node/2503009.
    $form['#cache']['max-age'] = 0;

    // Add the tableselect javascript.
    $form['#attached']['library'][] = 'core/drupal.tableselect';
    $use_revision = array_key_exists('revision', $this->view->getQuery()->getEntityTableInfo());

    // Only add the bulk form options and buttons if there are results.
    if (!empty($this->view->result)) {
      // Render checkboxes for all rows.
      $form[$this->options['id']]['#tree'] = TRUE;
      foreach ($this->view->result as $row_index => $row) {
        $entity = $this->getEntityTranslation($this->getEntity($row), $row);

        $form[$this->options['id']][$row_index] = [
          '#type' => 'checkbox',
          // We are not able to determine a main "title" for each row, so we can
          // only output a generic label.
          '#title' => $this->t('Update this item'),
          '#title_display' => 'invisible',
          '#default_value' => !empty($form_state->getValue($this->options['id'])[$row_index]) ? 1 : NULL,
          '#return_value' => $this->calculateEntityBulkFormKey($entity, $use_revision),
        ];
      }

      // Replace the form submit button label.
      $form['actions']['submit']['#value'] = $this->t('Apply to selected items');

      // Ensure a consistent container for filters/operations in the view header.
      $form['header'] = [
        '#type' => 'container',
        '#weight' => -100,
      ];

      // Build the bulk operations action widget for the header.
      // Allow themes to apply .container-inline on this separate container.
      $form['header'][$this->options['id']] = [
        '#type' => 'container',
      ];
      $form['header'][$this->options['id']]['action'] = [
        '#type' => 'select',
        '#title' => $this->options['action_title'],
        '#options' => $this->getBulkOptions(),
      ];

      // Duplicate the form actions into the action container in the header.
      $form['header'][$this->options['id']]['actions'] = $form['actions'];
    }
    else {
      // Remove the default actions build array.
      unset($form['actions']);
    }
  }

  /**
   * Returns the available operations for this form.
   *
   * @param bool $filtered
   *   (optional) Whether to filter actions to selected actions.
   * @return array
   *   An associative array of operations, suitable for a select element.
   */
  protected function getBulkOptions($filtered = TRUE) {
    $options = [];
    // Filter the action list.
    foreach ($this->actions as $id => $action) {
      if ($filtered) {
        $in_selected = in_array($id, $this->options['selected_actions']);
        // If the field is configured to include only the selected actions,
        // skip actions that were not selected.
        if (($this->options['include_exclude'] == 'include') && !$in_selected) {
          continue;
        }
        // Otherwise, if the field is configured to exclude the selected
        // actions, skip actions that were selected.
        elseif (($this->options['include_exclude'] == 'exclude') && $in_selected) {
          continue;
        }
      }

      $options[$id] = $action->label();
    }

    return $options;
  }

  /**
   * Submit handler for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user tried to access an action without access to it.
   */
  public function viewsFormSubmit(&$form, FormStateInterface $form_state) {
    if ($form_state->get('step') == 'views_form_views_form') {
      // Filter only selected checkboxes. Use the actual user input rather than
      // the raw form values array, since the site data may change before the
      // bulk form is submitted, which can lead to data loss.
      $user_input = $form_state->getUserInput();
      $selected = array_filter($user_input[$this->options['id']]);
      $entities = [];
      $action = $this->actions[$form_state->getValue('action')];
      $count = 0;

      foreach ($selected as $bulk_form_key) {
        $entity = $this->loadEntityFromBulkFormKey($bulk_form_key);
        // Skip execution if current entity does not exist.
        if (empty($entity)) {
          continue;
        }
        // Skip execution if the user did not have access.
        if (!$action->getPlugin()->access($entity, $this->view->getUser())) {
          $this->messenger->addError($this->t('No access to execute %action on the @entity_type_label %entity_label.', [
            '%action' => $action->label(),
            '@entity_type_label' => $entity->getEntityType()->getLabel(),
            '%entity_label' => $entity->label(),
          ]));
          continue;
        }

        $count++;

        $entities[$bulk_form_key] = $entity;
      }

      // If there were entities selected but the action isn't allowed on any of
      // them, we don't need to do anything further.
      if (!$count) {
        return;
      }

      $action->execute($entities);

      $operation_definition = $action->getPluginDefinition();
      if (!empty($operation_definition['confirm_form_route_name'])) {
        $options = [
          'query' => $this->getDestinationArray(),
        ];
        $form_state->setRedirect($operation_definition['confirm_form_route_name'], [], $options);
      }
      else {
        // Don't display the message unless there are some elements affected and
        // there is no confirmation form.
        $this->messenger->addStatus($this->formatPlural($count, '%action was applied to @count item.', '%action was applied to @count items.', [
          '%action' => $action->label(),
        ]));
      }
    }
  }

  /**
   * Returns the message to be displayed when there are no selected items.
   *
   * @return string
   *   Message displayed when no items are selected.
   */
  protected function emptySelectedMessage() {
    return $this->t('No items selected.');
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    $ids = $form_state->getValue($this->options['id']);
    if (empty($ids) || empty(array_filter($ids))) {
      $form_state->setErrorByName('', $this->emptySelectedMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if ($this->languageManager->isMultilingual()) {
      $this->getEntityTranslationRenderer()->query($this->query, $this->relationship);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

  /**
   * Calculates a bulk form key.
   *
   * This generates a key that is used as the checkbox return value when
   * submitting a bulk form. This key allows the entity for the row to be loaded
   * totally independently of the executed view row.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to calculate a bulk form key for.
   * @param bool $use_revision
   *   Whether the revision id should be added to the bulk form key. This should
   *   be set to TRUE only if the view is listing entity revisions.
   *
   * @return string
   *   The bulk form key representing the entity's id, language and revision (if
   *   applicable) as one string.
   *
   * @see self::loadEntityFromBulkFormKey()
   */
  protected function calculateEntityBulkFormKey(EntityInterface $entity, $use_revision) {
    $key_parts = [$entity->language()->getId(), $entity->id()];

    if ($entity instanceof RevisionableInterface && $use_revision) {
      $key_parts[] = $entity->getRevisionId();
    }

    // An entity ID could be an arbitrary string (although they are typically
    // numeric). JSON then Base64 encoding ensures the bulk_form_key is
    // safe to use in HTML, and that the key parts can be retrieved.
    $key = json_encode($key_parts);
    return base64_encode($key);
  }

  /**
   * Loads an entity based on a bulk form key.
   *
   * @param string $bulk_form_key
   *   The bulk form key representing the entity's id, language and revision (if
   *   applicable) as one string.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity loaded in the state (language, optionally revision) specified
   *   as part of the bulk form key.
   */
  protected function loadEntityFromBulkFormKey($bulk_form_key) {
    $key = base64_decode($bulk_form_key);
    $key_parts = json_decode($key);
    $revision_id = NULL;

    // If there are 3 items, vid will be last.
    if (count($key_parts) === 3) {
      $revision_id = array_pop($key_parts);
    }

    // The first two items will always be langcode and ID.
    $id = array_pop($key_parts);
    $langcode = array_pop($key_parts);

    // Load the entity or a specific revision depending on the given key.
    $storage = $this->entityTypeManager->getStorage($this->getEntityType());
    $entity = $revision_id ? $storage->loadRevision($revision_id) : $storage->load($id);

    if ($entity instanceof TranslatableInterface) {
      $entity = $entity->getTranslation($langcode);
    }

    return $entity;
  }

}
