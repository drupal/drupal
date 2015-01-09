<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldStorageAddForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the "field storage" add page.
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
   * The name of the entity type which provides bundles for the entity type
   * defined above.
   *
   * @var string
   */
  protected $bundleEntityTypeId;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactoryInterface
   */
  public $queryFactory;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new FieldStorageAddForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, QueryFactory $query_factory, ConfigFactoryInterface $config_factory) {
    $this->entityManager = $entity_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->queryFactory = $query_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'field_ui_field_storage_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity.query'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    $this->bundleEntityTypeId = $entity_type->getBundleEntityType();

    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $bundle = $bundle ?: $this->getRouteMatch()->getRawParameter($this->bundleEntityTypeId);
      $form_state->set('bundle', $bundle);
    }

    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');

    // Gather valid field types.
    $field_type_options = array();
    foreach ($this->fieldTypePluginManager->getDefinitions() as $name => $field_type) {
      // Skip field types which should not be added via user interface.
      if (empty($field_type['no_ui'])) {
        $field_type_options[$name] = $field_type['label'];
      }
    }
    asort($field_type_options);

    $form['add'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('field-type-wrapper', 'clearfix')),
    );

    $form['add']['new_storage_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Add a new field'),
      '#options' => $field_type_options,
      '#empty_option' => $this->t('- Select a field type -'),
    );

    // Re-use existing field.
    if ($existing_field_storage_options = $this->getExistingFieldStorageOptions()) {
      $form['add']['separator'] = array(
        '#type' => 'item',
        '#markup' => $this->t('or'),
      );
      $form['add']['existing_storage_name'] = array(
        '#type' => 'select',
        '#title' => $this->t('Re-use an existing field'),
        '#options' => $existing_field_storage_options,
        '#empty_option' => $this->t('- Select an existing field -'),
      );

      $form['#attached']['drupalSettings']['existingFieldLabels'] = $this->getExistingFieldLabels(array_keys($existing_field_storage_options));
    }
    else {
      // Provide a placeholder form element to simplify the validation code.
      $form['add']['existing_storage_name'] = array(
        '#type' => 'value',
        '#value' => FALSE,
      );
    }

    // Field label and field_name.
    $form['new_storage_wrapper'] = array(
      '#type' => 'container',
      '#states' => array(
        '!visible' => array(
          ':input[name="new_storage_type"]' => array('value' => ''),
        ),
      ),
    );
    $form['new_storage_wrapper']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#size' => 15,
    );

    $field_prefix = $this->config('field_ui.settings')->get('field_prefix');
    $form['new_storage_wrapper']['field_name'] = array(
      '#type' => 'machine_name',
      // This field should stay LTR even for RTL languages.
      '#field_prefix' => '<span dir="ltr">' . $field_prefix,
      '#field_suffix' => '</span>&lrm;',
      '#size' => 15,
      '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
      // Calculate characters depending on the length of the field prefix
      // setting. Maximum length is 32.
      '#maxlength' => FieldStorageConfig::NAME_MAX_LENGTH - strlen($field_prefix),
      '#machine_name' => array(
        'source' => array('new_storage_wrapper', 'label'),
        'exists' => array($this, 'fieldNameExists'),
      ),
      '#required' => FALSE,
    );

    // Provide a separate label element for the "Re-use existing field" case
    // and place it outside the $form['add'] wrapper because those elements
    // are displayed inline.
    if ($existing_field_storage_options) {
      $form['existing_storage_label'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#size' => 15,
        '#states' => array(
          '!visible' => array(
            ':input[name="existing_storage_name"]' => array('value' => ''),
          ),
        ),
      );
    }

    // Place the 'translatable' property as an explicit value so that contrib
    // modules can form_alter() the value for newly created fields. By default
    // we create field storage as translatable so it will be possible to enable
    // translation at field level.
    $form['translatable'] = array(
      '#type' => 'value',
      '#value' => TRUE,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
    );

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
    $error = FALSE;
    $values = $form_state->getValues();
    $destinations = array();

    // Create new field.
    if ($values['new_storage_type']) {
      // Create the field storage and field.
      try {
        $this->entityManager->getStorage('field_storage_config')->create(array(
          'field_name' => $values['field_name'],
          'entity_type' => $this->entityTypeId,
          'type' => $values['new_storage_type'],
          'translatable' => $values['translatable'],
        ))->save();

        $field = $this->entityManager->getStorage('field_config')->create(array(
          'field_name' => $values['field_name'],
          'entity_type' => $this->entityTypeId,
          'bundle' => $this->bundle,
          'label' => $values['label'],
          // Field translatability should be explicitly enabled by the users.
          'translatable' => FALSE,
        ));
        $field->save();

        $this->configureEntityDisplays($values['field_name']);

        // Always show the field settings step, as the cardinality needs to be
        // configured for new fields.
        $route_parameters = array(
          $this->bundleEntityTypeId => $this->bundle,
          'field_config' => $field->id(),
        );
        $destinations[] = array('route_name' => 'field_ui.storage_edit_' . $this->entityTypeId, 'route_parameters' => $route_parameters);
        $destinations[] = array('route_name' => 'field_ui.field_edit_' . $this->entityTypeId, 'route_parameters' => $route_parameters);
        $destinations[] = array('route_name' => 'field_ui.overview_' . $this->entityTypeId, 'route_parameters' => $route_parameters);

        // Store new field information for any additional submit handlers.
        $form_state->set(['fields_added', '_add_new_field'], $values['field_name']);
      }
      catch (\Exception $e) {
        $error = TRUE;
        drupal_set_message($this->t('There was a problem creating field %label: !message', array('%label' => $values['label'], '!message' => $e->getMessage())), 'error');
      }
    }

    // Re-use existing field.
    if ($values['existing_storage_name']) {
      $field_name = $values['existing_storage_name'];

      try {
        $field = $this->entityManager->getStorage('field_config')->create(array(
          'field_name' => $field_name,
          'entity_type' => $this->entityTypeId,
          'bundle' => $this->bundle,
          'label' => $values['existing_storage_label'],
        ));
        $field->save();

        $this->configureEntityDisplays($field_name);

        $route_parameters = array(
          $this->bundleEntityTypeId => $this->bundle,
          'field_config' => $field->id(),
        );
        $destinations[] = array('route_name' => 'field_ui.field_edit_' . $this->entityTypeId, 'route_parameters' => $route_parameters);
        $destinations[] = array('route_name' => 'field_ui.overview_' . $this->entityTypeId, 'route_parameters' => $route_parameters);

        // Store new field information for any additional submit handlers.
        $form_state->set(['fields_added', '_add_existing_field'], $field_name);
      }
      catch (\Exception $e) {
        $error = TRUE;
        drupal_set_message($this->t('There was a problem creating field %label: !message', array('%label' => $values['label'], '!message' => $e->getMessage())), 'error');
      }
    }

    if ($destinations) {
      $destination = drupal_get_destination();
      $destinations[] = $destination['destination'];
      $form_state->setRedirectUrl(FieldUI::getNextDestination($destinations, $form_state));
    }
    elseif (!$error) {
      drupal_set_message($this->t('Your settings have been saved.'));
    }
  }

  /**
   * Configures the newly created field for the default view and form modes.
   *
   * @param string $field_name
   *   The field name.
   */
  protected function configureEntityDisplays($field_name) {
    // Make sure the field is displayed in the 'default' form mode (using
    // default widget and settings). It stays hidden for other form modes
    // until it is explicitly configured.
    entity_get_form_display($this->entityTypeId, $this->bundle, 'default')
      ->setComponent($field_name)
      ->save();

    // Make sure the field is displayed in the 'default' view mode (using
    // default formatter and settings). It stays hidden for other view
    // modes until it is explicitly configured.
    entity_get_display($this->entityTypeId, $this->bundle, 'default')
      ->setComponent($field_name)
      ->save();
  }

  /**
   * Returns an array of existing field storages that can be added to a bundle.
   *
   * @return array
   *   An array of existing field storages keyed by name.
   */
  protected function getExistingFieldStorageOptions() {
    $options = array();
    // Load the field_storages and build the list of options.
    $field_types = $this->fieldTypePluginManager->getDefinitions();
    foreach ($this->entityManager->getFieldStorageDefinitions($this->entityTypeId) as $field_name => $field_storage) {
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
        $options[$field_name] = $this->t('@type: @field', array(
          '@type' => $field_types[$field_type]['label'],
          '@field' => $field_name,
        ));
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
    $field_ids = $this->queryFactory->get('field_config')
      ->condition('entity_type', $this->entityTypeId)
      ->condition('field_name', $field_names)
      ->execute();
    $fields = $this->entityManager->getStorage('field_config')->loadMultiple($field_ids);

    // Go through all the fields and use the label of the first encounter.
    $labels = array();
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

    $field_storage_definitions = $this->entityManager->getFieldStorageDefinitions($this->entityTypeId);
    return isset($field_storage_definitions[$field_name]);
  }

}
