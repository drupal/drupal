<?php

namespace Drupal\field_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the "field storage" add page.
 *
 * @internal
 */
class FieldStorageAddForm extends FormBase {

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
    return 'field_ui_field_storage_add_form';
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
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $form_state->set('bundle', $bundle);
    }

    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');

    // Gather valid field types.
    $field_type_options = [];
    foreach ($this->fieldTypePluginManager->getGroupedDefinitions($this->fieldTypePluginManager->getUiDefinitions()) as $category => $field_types) {
      foreach ($field_types as $name => $field_type) {
        $field_type_options[$category][$name] = $field_type['label'];
      }
    }

    $form['add'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
    ];

    $form['add']['new_storage_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Add a new field'),
      '#options' => $field_type_options,
      '#empty_option' => $this->t('- Select a field type -'),
    ];

    // Re-use existing field.
    if ($existing_field_storage_options = $this->getExistingFieldStorageOptions()) {
      $form['add']['separator'] = [
        '#type' => 'item',
        '#markup' => $this->t('or'),
      ];
      $form['add']['existing_storage_name'] = [
        '#type' => 'select',
        '#title' => $this->t('Re-use an existing field'),
        '#options' => $existing_field_storage_options,
        '#empty_option' => $this->t('- Select an existing field -'),
      ];

      $form['#attached']['drupalSettings']['existingFieldLabels'] = $this->getExistingFieldLabels(array_keys($existing_field_storage_options));
    }
    else {
      // Provide a placeholder form element to simplify the validation code.
      $form['add']['existing_storage_name'] = [
        '#type' => 'value',
        '#value' => FALSE,
      ];
    }

    // Field label and field_name.
    $form['new_storage_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        '!visible' => [
          ':input[name="new_storage_type"]' => ['value' => ''],
        ],
      ],
    ];
    $form['new_storage_wrapper']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#size' => 15,
    ];

    $field_prefix = $this->config('field_ui.settings')->get('field_prefix');
    $form['new_storage_wrapper']['field_name'] = [
      '#type' => 'machine_name',
      '#field_prefix' => $field_prefix,
      '#size' => 15,
      '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
      // Calculate characters depending on the length of the field prefix
      // setting. Maximum length is 32.
      '#maxlength' => FieldStorageConfig::NAME_MAX_LENGTH - strlen($field_prefix),
      '#machine_name' => [
        'source' => ['new_storage_wrapper', 'label'],
        'exists' => [$this, 'fieldNameExists'],
      ],
      '#required' => FALSE,
    ];

    // Provide a separate label element for the "Re-use existing field" case
    // and place it outside the $form['add'] wrapper because those elements
    // are displayed inline.
    if ($existing_field_storage_options) {
      $form['existing_storage_label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#size' => 15,
        '#states' => [
          '!visible' => [
            ':input[name="existing_storage_name"]' => ['value' => ''],
          ],
        ],
      ];
    }

    // Place the 'translatable' property as an explicit value so that contrib
    // modules can form_alter() the value for newly created fields. By default
    // we create field storage as translatable so it will be possible to enable
    // translation at field level.
    $form['translatable'] = [
      '#type' => 'value',
      '#value' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
    ];

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Missing field type.
    if (!$form_state->getValue('new_storage_type') && !$form_state->getValue('existing_storage_name')) {
      $form_state->setErrorByName('new_storage_type', $this->t('You need to select a field type or an existing field.'));
    }
    // Both field type and existing field option selected. This is prevented in
    // the UI with JavaScript but we also need a proper server-side validation.
    elseif ($form_state->getValue('new_storage_type') && $form_state->getValue('existing_storage_name')) {
      $form_state->setErrorByName('new_storage_type', $this->t('Adding a new field and re-using an existing field at the same time is not allowed.'));
      return;
    }

    $this->validateAddNew($form, $form_state);
    $this->validateAddExisting($form, $form_state);
  }

  /**
   * Validates the 'add new field' case.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\field_ui\Form\FieldStorageAddForm::validateForm()
   */
  protected function validateAddNew(array $form, FormStateInterface $form_state) {
    // Validate if any information was provided in the 'add new field' case.
    if ($form_state->getValue('new_storage_type')) {
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
        $form_state->setValueForElement($form['new_storage_wrapper']['field_name'], $field_name);
      }
    }
  }

  /**
   * Validates the 're-use existing field' case.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\field_ui\Form\FieldStorageAddForm::validateForm()
   */
  protected function validateAddExisting(array $form, FormStateInterface $form_state) {
    if ($form_state->getValue('existing_storage_name')) {
      // Missing label.
      if (!$form_state->getValue('existing_storage_label')) {
        $form_state->setErrorByName('existing_storage_label', $this->t('Re-use existing field: you need to provide a label.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $field_values = [
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->bundle,
    ];

    if ($values['existing_storage_name']) {
      $is_new_field = FALSE;
      $field_name = $values['existing_storage_name'];

      // Get settings from existing configuration.
      $default_options = $this->getExistingFieldDefaults($field_name);
      $field_values += [
        ...$default_options['field_config'] ?? [],
        'field_name' => $field_name,
        'label' => $values['existing_storage_label'],
      ];
    }
    else {
      $is_new_field = TRUE;
      $field_name = $values['field_name'];
      $field_storage_type = $values['new_storage_type'];
      $default_options = [];

      // Check if we're dealing with a preconfigured field.
      if (strpos($field_storage_type, 'field_ui:') === 0) {
        list(, $field_type, $preset_key) = explode(':', $field_storage_type, 3);
        $default_options = $this->getNewFieldDefaults($field_type, $preset_key);
      }
      else {
        $field_type = $field_storage_type;
      }
      $field_values += [
        ...$default_options['field_config'] ?? [],
        'field_name' => $field_name,
        'label' => $values['label'],
        // Field translatability should be explicitly enabled by the users.
        'translatable' => FALSE,
      ];

      $field_storage_values = [
        ...$default_options['field_storage_config'] ?? [],
        'field_name' => $field_name,
        'type' => $field_type,
        'entity_type' => $this->entityTypeId,
        'translatable' => $values['translatable'],
      ];
    }

    try {
      // Create the field storage.
      if ($is_new_field) {
        assert(isset($field_storage_values));
        $this->entityTypeManager->getStorage('field_storage_config')
          ->create($field_storage_values)->save();
      }

      // Create the field.
      $field = $this->entityTypeManager->getStorage('field_config')
        ->create($field_values);
      $field->save();

      // Configure the display modes.
      $this->configureEntityFormDisplay($field_name, $default_options['entity_form_display'] ?? []);
      $this->configureEntityViewDisplay($field_name, $default_options['entity_view_display'] ?? []);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('There was a problem creating field %label: @message', ['%label' => $values['label'], '@message' => $e->getMessage()]));
      return;
    }

    // Configure next steps in the multi-part form.
    $destinations = [];
    $route_parameters = [
      'field_config' => $field->id(),
    ] + FieldUI::getRouteBundleParameter($entity_type, $this->bundle);
    if ($is_new_field) {
      // Always show the field settings step, as the cardinality needs to be
      // configured for new fields.
      $destinations[] = [
        'route_name' => "entity.field_config.{$this->entityTypeId}_storage_edit_form",
        'route_parameters' => $route_parameters,
      ];
    }

    $destinations[] = [
      'route_name' => "entity.field_config.{$this->entityTypeId}_field_edit_form",
      'route_parameters' => $route_parameters,
    ];
    $destinations[] = [
      'route_name' => "entity.{$this->entityTypeId}.field_ui_fields",
      'route_parameters' => $route_parameters,
    ];
    $destination = $this->getDestinationArray();
    $destinations[] = $destination['destination'];
    $form_state->setRedirectUrl(
        FieldUI::getNextDestination($destinations)
      );

    // Store new field information for any additional submit handlers.
    $subkey = $is_new_field ? '_add_new_field' : '_add_existing_field';
    $form_state->set(['fields_added', $subkey], $field_name);

    $this->messenger()->addMessage($this->t('Your settings have been saved.'));
  }

  /**
   * Configures the field for the default form mode.
   *
   * @param string $field_name
   *   The field name.
   * @param array[] $widget_settings
   *   (optional) Array of widget settings, keyed by form mode. Defaults to an
   *   empty array.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function configureEntityFormDisplay($field_name, array $widget_settings = []) {
    // For a new field, only $mode = 'default' should be set. Use the
    // preconfigured or default widget and settings. The field will not appear
    // in other form modes until it is explicitly configured. When an existing
    // field is reused, copy the behavior for all enabled form modes.
    foreach ($widget_settings as $mode => $options) {
      $form_display = $this->entityDisplayRepository->getFormDisplay($this->entityTypeId, $this->bundle, $mode);
      if ($form_display->status()) {
        $form_display->setComponent($field_name, $options)->save();
      }
    }

    if (empty($widget_settings)) {
      $this->entityDisplayRepository->getFormDisplay($this->entityTypeId, $this->bundle, 'default')
        ->setComponent($field_name, [])
        ->save();
    }
  }

  /**
   * Configures the field for the default view mode.
   *
   * @param string $field_name
   *   The field name.
   * @param array[] $formatter_settings
   *   (optional) An array of settings, keyed by view mode. Only the 'type' key
   *   of the inner array is used, and the value should be the plugin ID of a
   *   formatter. Defaults to an empty array.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function configureEntityViewDisplay($field_name, array $formatter_settings = []) {
    // For a new field, only $mode = 'default' should be set. Use the
    // preconfigured or default formatter and settings. The field stays hidden
    // for other view modes until it is explicitly configured. When an existing
    // field is reused, copy the behavior for all enabled view modes.
    foreach ($formatter_settings as $mode => $options) {
      $view_display = $this->entityDisplayRepository->getViewDisplay($this->entityTypeId, $this->bundle, $mode);
      if ($view_display->status()) {
        $view_display->setComponent($field_name, $options)->save();
      }
    }

    if (empty($formatter_settings)) {
      $this->entityDisplayRepository->getViewDisplay($this->entityTypeId, $this->bundle)
        ->setComponent($field_name, [])
        ->save();
    }
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
   * Get default options from an existing field and bundle.
   *
   * @param string $field_name
   *   The machine name of the field.
   *
   * @return array
   *   An array of settings with keys 'field_config', 'entity_form_display', and
   *   'entity_view_display' if these are defined for an existing field
   *   instance. If the field is not defined for the specified bundle (or for
   *   any bundle if $existing_bundle is omitted) then return an empty array.
   */
  protected function getExistingFieldDefaults(string $field_name): array {
    $default_options = [];
    $field_map = $this->entityFieldManager->getFieldMap();

    if (empty($field_map[$this->entityTypeId][$field_name]['bundles'])) {
      return [];
    }
    $bundles = $field_map[$this->entityTypeId][$field_name]['bundles'];

    // Sort bundles to ensure deterministic behavior.
    sort($bundles);
    $existing_bundle = reset($bundles);

    // Copy field configuration.
    $existing_field = $this->entityFieldManager
      ->getFieldDefinitions($this->entityTypeId, $existing_bundle)[$field_name];
    $default_options['field_config'] = [
      'description' => $existing_field->getDescription(),
      'settings' => $existing_field->getSettings(),
      'required' => $existing_field->isRequired(),
      'default_value' => $existing_field->getDefaultValueLiteral(),
      'default_value_callback' => $existing_field->getDefaultValueCallback(),
    ];

    // Copy form and view mode configuration.
    $properties = [
      'targetEntityType' => $this->entityTypeId,
      'bundle' => $existing_bundle,
    ];
    $existing_forms = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->loadByProperties($properties);
    foreach ($existing_forms as $form) {
      if ($settings = $form->getComponent($field_name)) {
        $default_options['entity_form_display'][$form->getMode()] = $settings;
      }
    }
    $existing_views = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->loadByProperties($properties);
    foreach ($existing_views as $view) {
      if ($settings = $view->getComponent($field_name)) {
        $default_options['entity_view_display'][$view->getMode()] = $settings;
      }
    }

    return $default_options;
  }

  /**
   * Get default options from preconfigured options for a new field.
   *
   * @param string $field_name
   *   The machine name of the field.
   * @param string $preset_key
   *   A key in the preconfigured options array for the field.
   *
   * @return array
   *   An array of settings with keys 'field_storage_config', 'field_config',
   *   'entity_form_display', and 'entity_view_display'.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see \Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface::getPreconfiguredOptions()
   */
  protected function getNewFieldDefaults(string $field_name, string $preset_key): array {
    $field_type_definition = $this->fieldTypePluginManager->getDefinition($field_name);
    $options = $this->fieldTypePluginManager->getPreconfiguredOptions($field_type_definition['id']);
    $field_options = $options[$preset_key] ?? [];

    $default_options = [];
    // Merge in preconfigured field storage options.
    if (isset($field_options['field_storage_config'])) {
      foreach (['cardinality', 'settings'] as $key) {
        if (isset($field_options['field_storage_config'][$key])) {
          $default_options['field_storage_config'][$key] = $field_options['field_storage_config'][$key];
        }
      }
    }

    // Merge in preconfigured field options.
    if (isset($field_options['field_config'])) {
      foreach (['required', 'settings'] as $key) {
        if (isset($field_options['field_config'][$key])) {
          $default_options['field_config'][$key] = $field_options['field_config'][$key];
        }
      }
    }

    // Preconfigured options only apply to the default display modes.
    foreach (['entity_form_display', 'entity_view_display'] as $key) {
      if (isset($field_options[$key])) {
        $default_options[$key] = ['default' => array_intersect_key($field_options[$key], ['type' => '', 'settings' => []])];
      }
      else {
        $default_options[$key] = ['default' => []];
      }
    }

    return $default_options;
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
