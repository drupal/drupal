<?php

namespace Drupal\field_ui\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a form for the "field storage" add page.
 *
 * @internal
 */
class FieldAddForm extends FormBase {

  /**
   * The name of the entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The type of field to add.
   *
   * @var string
   */
  protected $fieldType;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new FieldStorageAddForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface|null $entity_field_manager
   *   (optional) The entity field manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   (optional) The entity display repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $entity_field_manager = NULL, EntityDisplayRepositoryInterface $entity_display_repository = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->configFactory = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_ui_field_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL, $field_type = '') {
    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $form_state->set('bundle', $bundle);
    }

    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');
    $this->fieldType = $field_type;

//    // Gather valid field types.
//    $field_type_options = [];
//    foreach ($this->fieldTypePluginManager->getGroupedDefinitions($this->fieldTypePluginManager->getUiDefinitions()) as $category => $field_types) {
//      foreach ($field_types as $name => $field_type) {
//        $field_type_options[$category][$name] = $field_type['label'];
//      }
//    }


    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#size' => 15,
    ];
    $field_prefix = $this->config('field_ui.settings')->get('field_prefix');
    $form['field_name'] = [
      '#type' => 'machine_name',
      '#field_prefix' => $field_prefix,
      '#size' => 15,
      '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
      // Calculate characters depending on the length of the field prefix
      // setting. Maximum length is 32.
      '#maxlength' => FieldStorageConfig::NAME_MAX_LENGTH - strlen($field_prefix),
      '#machine_name' => [
        'source' => ['label'],
        'exists' => [$this, 'fieldNameExists'],
      ],
      '#required' => FALSE,
    ];

    if (str_contains($this->fieldType, 'field_ui:')) {
      [, $field_type, $option_key] = explode(':', $this->fieldType, 3);
      // @todo Add support for pre-configured options.
    }

    // Create a run-time plugin instance for the field item to render the
    // storage settings form.
    $data_definition = FieldItemDataDefinition::createFromDataType('field_item:' . $field_type);
    $ids = (object) [
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->bundle,
      'entity_id' => NULL,
    ];
    $entity_adapter = EntityAdapter::createFromEntity(_field_create_entity_from_ids($ids));
    $plugin_class = $this->fieldTypePluginManager->getPluginClass($field_type);
    /** @var \Drupal\Core\Field\FieldItemInterface $plugin_instance */
    $plugin_instance = new $plugin_class($data_definition);
    $plugin_instance->setContext(NULL, $entity_adapter);

    $form['field_storage_settings'] = [
      '#tree' => TRUE,
    ];
    $form['field_storage_settings'] += $plugin_instance->storageSettingsForm($form, $form_state, FALSE);

    $form['field_settings'] = [
      '#tree' => TRUE,
    ];
    $form['field_settings'] += $plugin_instance->fieldSettingsForm($form, $form_state);

    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required field'),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Help text'),
      '#rows' => 5,
      '#description' => $this->t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', ['@tags' => FieldFilteredMarkup::displayAllowedTags()]) . '<br />' . $this->t('This field supports tokens.'),
    ];

//    // Add handling for default value.
//    if ($element = $plugin_instance->defaultValuesForm($form, $form_state)) {
//      $element = array_merge($element, [
//        '#type' => 'details',
//        '#title' => $this->t('Default value'),
//        '#open' => TRUE,
//        '#tree' => TRUE,
//        '#description' => $this->t('The default value for this field, used when creating new content.'),
//        '#weight' => 12,
//      ]);
//
//      $form['default_value'] = $element;
//    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Missing label.
    if (!$form_state->getValue('label')) {
      $form_state->setErrorByName('label', $this->t('Add new field: you need to provide a label.'));
    }

    // Missing field name.
    if (!$form_state->getValue('field_name')) {
      $form_state->setErrorByName('field_name', $this->t('Add new field: you need to provide a machine name for the field.'));
    }
    // Field name validation.
    else {
      $field_name = $form_state->getValue('field_name');

      // Add the field prefix.
      $field_name = $this->configFactory->get('field_ui.settings')->get('field_prefix') . $field_name;
      $form_state->setValueForElement($form['field_name'], $field_name);
    }
  }



  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $error = FALSE;
    $values = $form_state->getValues();
    $destinations = [];
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);

    xdebug_break();

    // Create new field.
    $field_storage_values = [
      'field_name' => $values['field_name'],
      'entity_type' => $this->entityTypeId,
      'type' => $this->fieldType,
      'translatable' => $values['translatable'],
      'settings' => $values['field_storage_settings'],
    ];
    $field_values = [
      'field_name' => $values['field_name'],
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->bundle,
      'required' => $values['required'],
      'label' => $values['label'],
      // Field translatability should be explicitly enabled by the users.
      'translatable' => FALSE,
      'settings' => $values['field_settings']
    ];
    $widget_id = $formatter_id = NULL;
    $widget_settings = $formatter_settings = [];

//    // Check if we're dealing with a preconfigured field.
//    if (strpos($field_storage_values['type'], 'field_ui:') !== FALSE) {
//      [, $field_type, $option_key] = explode(':', $field_storage_values['type'], 3);
//      $field_storage_values['type'] = $field_type;
//
//      $field_definition = $this->fieldTypePluginManager->getDefinition($field_type);
//      $options = $this->fieldTypePluginManager->getPreconfiguredOptions($field_definition['id']);
//      $field_options = $options[$option_key];
//
//      // Merge in preconfigured field storage options.
//      if (isset($field_options['field_storage_config'])) {
//        foreach (['cardinality', 'settings'] as $key) {
//          if (isset($field_options['field_storage_config'][$key])) {
//            $field_storage_values[$key] = $field_options['field_storage_config'][$key];
//          }
//        }
//      }
//
//      // Merge in preconfigured field options.
//      if (isset($field_options['field_config'])) {
//        foreach (['required', 'settings'] as $key) {
//          if (isset($field_options['field_config'][$key])) {
//            $field_values[$key] = $field_options['field_config'][$key];
//          }
//        }
//      }
//
//      $widget_id = $field_options['entity_form_display']['type'] ?? NULL;
//      $widget_settings = $field_options['entity_form_display']['settings'] ?? [];
//      $formatter_id = $field_options['entity_view_display']['type'] ?? NULL;
//      $formatter_settings = $field_options['entity_view_display']['settings'] ?? [];
//    }

    // Create the field storage and field.
    try {
      $this->entityTypeManager->getStorage('field_storage_config')->create($field_storage_values)->save();
      $field = $this->entityTypeManager->getStorage('field_config')->create($field_values);
      $field->save();

      $this->configureEntityFormDisplay($values['field_name'], $widget_id, $widget_settings);
      $this->configureEntityViewDisplay($values['field_name'], $formatter_id, $formatter_settings);

      // Always show the field settings step, as the cardinality needs to be
      // configured for new fields.
      $route_parameters = [
        'field_config' => $field->id(),
      ] + FieldUI::getRouteBundleParameter($entity_type, $this->bundle);
      $destinations[] = ['route_name' => "entity.field_config.{$this->entityTypeId}_storage_edit_form", 'route_parameters' => $route_parameters];
      $destinations[] = ['route_name' => "entity.field_config.{$this->entityTypeId}_field_edit_form", 'route_parameters' => $route_parameters];
      $destinations[] = ['route_name' => "entity.{$this->entityTypeId}.field_ui_fields", 'route_parameters' => $route_parameters];

      // Store new field information for any additional submit handlers.
      $form_state->set(['fields_added', '_add_new_field'], $values['field_name']);
    }
    catch (\Exception $e) {
      $error = TRUE;
      $this->messenger()->addError($this->t('There was a problem creating field %label: @message', ['%label' => $values['label'], '@message' => $e->getMessage()]));
    }

    if ($destinations) {
      $destination = $this->getDestinationArray();
      $destinations[] = $destination['destination'];
      $form_state->setRedirectUrl(FieldUI::getNextDestination($destinations));
    }
    elseif (!$error) {
      $this->messenger()->addStatus($this->t('Your settings have been saved.'));
    }
  }

  /**
   * Configures the field for the default form mode.
   *
   * @param string $field_name
   *   The field name.
   * @param string|null $widget_id
   *   (optional) The plugin ID of the widget. Defaults to NULL.
   * @param array $widget_settings
   *   (optional) An array of widget settings. Defaults to an empty array.
   */
  protected function configureEntityFormDisplay($field_name, $widget_id = NULL, array $widget_settings = []) {
    $options = [];
    if ($widget_id) {
      $options['type'] = $widget_id;
      if (!empty($widget_settings)) {
        $options['settings'] = $widget_settings;
      }
    }
    // Make sure the field is displayed in the 'default' form mode (using
    // default widget and settings). It stays hidden for other form modes
    // until it is explicitly configured.
    $this->entityDisplayRepository->getFormDisplay($this->entityTypeId, $this->bundle, 'default')
      ->setComponent($field_name, $options)
      ->save();
  }

  /**
   * Configures the field for the default view mode.
   *
   * @param string $field_name
   *   The field name.
   * @param string|null $formatter_id
   *   (optional) The plugin ID of the formatter. Defaults to NULL.
   * @param array $formatter_settings
   *   (optional) An array of formatter settings. Defaults to an empty array.
   */
  protected function configureEntityViewDisplay($field_name, $formatter_id = NULL, array $formatter_settings = []) {
    $options = [];
    if ($formatter_id) {
      $options['type'] = $formatter_id;
      if (!empty($formatter_settings)) {
        $options['settings'] = $formatter_settings;
      }
    }
    // Make sure the field is displayed in the 'default' view mode (using
    // default formatter and settings). It stays hidden for other view
    // modes until it is explicitly configured.
    $this->entityDisplayRepository->getViewDisplay($this->entityTypeId, $this->bundle)
      ->setComponent($field_name, $options)
      ->save();
  }

  /**
   * Returns an array of existing field storages that can be added to a bundle.
   *
   * @return array
   *   An array of existing field storages keyed by name.
   */
  protected function getExistingFieldStorageOptions() {
    $options = [];
    // Load the field_storages and build the list of options.
    $field_types = $this->fieldTypePluginManager->getDefinitions();
    foreach ($this->entityFieldManager->getFieldStorageDefinitions($this->entityTypeId) as $field_name => $field_storage) {
      // Do not show:
      // - non-configurable field storages,
      // - locked field storages,
      // - field storages that should not be added via user interface,
      // - field storages that already have a field in the bundle.
      $field_type = $field_storage->getType();
      if ($field_storage instanceof FieldStorageConfigInterface
        && !$field_storage->isLocked()
        && empty($field_types[$field_type]['no_ui'])
        && !in_array($this->bundle, $field_storage->getBundles(), TRUE)) {
        $options[$field_name] = $this->t('@type: @field', [
          '@type' => $field_types[$field_type]['label'],
          '@field' => $field_name,
        ]);
      }
    }
    asort($options);

    return $options;
  }

  /**
   * Gets the human-readable labels for the given field storage names.
   *
   * Since not all field storages are required to have a field, we can only
   * provide the field labels on a best-effort basis (e.g. the label of a field
   * storage without any field attached to a bundle will be the field name).
   *
   * @param array $field_names
   *   An array of field names.
   *
   * @return array
   *   An array of field labels keyed by field name.
   */
  protected function getExistingFieldLabels(array $field_names) {
    // Get all the fields corresponding to the given field storage names and
    // this entity type.
    $field_ids = $this->entityTypeManager->getStorage('field_config')->getQuery()
      ->condition('entity_type', $this->entityTypeId)
      ->condition('field_name', $field_names)
      ->execute();
    $fields = $this->entityTypeManager->getStorage('field_config')->loadMultiple($field_ids);

    // Go through all the fields and use the label of the first encounter.
    $labels = [];
    foreach ($fields as $field) {
      if (!isset($labels[$field->getName()])) {
        $labels[$field->getName()] = $field->label();
      }
    }

    // For field storages without any fields attached to a bundle, the default
    // label is the field name.
    $labels += array_combine($field_names, $field_names);

    return $labels;
  }

  /**
   * Checks if a field machine name is taken.
   *
   * @param string $value
   *   The machine name, not prefixed.
   * @param array $element
   *   An array containing the structure of the 'field_name' element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the field machine name is taken.
   */
  public function fieldNameExists($value, $element, FormStateInterface $form_state) {
    // Don't validate the case when an existing field has been selected.
    if ($form_state->getValue('existing_storage_name')) {
      return FALSE;
    }

    // Add the field prefix.
    $field_name = $this->configFactory->get('field_ui.settings')->get('field_prefix') . $value;

    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($this->entityTypeId);
    return isset($field_storage_definitions[$field_name]);
  }

}
