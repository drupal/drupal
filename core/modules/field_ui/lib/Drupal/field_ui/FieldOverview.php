<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldOverview.
 */

namespace Drupal\field_ui;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\field_ui\OverviewBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Field UI field overview form.
 */
class FieldOverview extends OverviewBase {

  /**
   *  The field type manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new FieldOverview.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   */
  public function __construct(EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_manager);
    $this->fieldTypeManager = $field_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRegions() {
    return array(
      'content' => array(
        'title' => $this->t('Content'),
        'invisible' => TRUE,
        // @todo Bring back this message in https://drupal.org/node/1963340.
        //'message' => $this->t('No fields are present yet.'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_ui_field_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $entity_type_id = NULL, $bundle = NULL) {
    parent::buildForm($form, $form_state, $entity_type_id, $bundle);

    // Gather bundle information.
    $instances = field_info_instances($this->entity_type, $this->bundle);
    $field_types = $this->fieldTypeManager->getConfigurableDefinitions();

    // Field prefix.
    $field_prefix = \Drupal::config('field_ui.settings')->get('field_prefix');

    $form += array(
      '#entity_type' => $this->entity_type,
      '#bundle' => $this->bundle,
      '#fields' => array_keys($instances),
    );

    $table = array(
      '#type' => 'field_ui_table',
      '#tree' => TRUE,
      '#header' => array(
        $this->t('Label'),
        array(
          'data' => $this->t('Machine name'),
          'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
        ),
        $this->t('Field type'),
        $this->t('Operations'),
      ),
      '#regions' => $this->getRegions(),
      '#attributes' => array(
        'class' => array('field-ui-overview'),
        'id' => 'field-overview',
      ),
    );

    // Fields.
    foreach ($instances as $name => $instance) {
      $field = $instance->getField();
      $route_parameters = array(
        $this->bundleEntityType => $this->bundle,
        'field_instance_config' => $instance->id(),
      );
      $table[$name] = array(
        '#attributes' => array(
          'id' => drupal_html_class($name),
        ),
        'label' => array(
          '#markup' => check_plain($instance->getLabel()),
        ),
        'field_name' => array(
          '#markup' => $instance->getName(),
        ),
        'type' => array(
          '#type' => 'link',
          '#title' => $field_types[$field->getType()]['label'],
          '#route_name' => 'field_ui.field_edit_' . $this->entity_type,
          '#route_parameters' => $route_parameters,
          '#options' => array('attributes' => array('title' => $this->t('Edit field settings.'))),
        ),
      );

      $links = array();
      $links['edit'] = array(
        'title' => $this->t('Edit'),
        'route_name' => 'field_ui.instance_edit_' . $this->entity_type,
        'route_parameters' => $route_parameters,
        'attributes' => array('title' => $this->t('Edit instance settings.')),
      );
      $links['field-settings'] = array(
        'title' => $this->t('Field settings'),
        'route_name' => 'field_ui.field_edit_' . $this->entity_type,
        'route_parameters' => $route_parameters,
        'attributes' => array('title' => $this->t('Edit field settings.')),
      );
      $links['delete'] = array(
        'title' => $this->t('Delete'),
        'route_name' => 'field_ui.delete_' . $this->entity_type,
        'route_parameters' => $route_parameters,
        'attributes' => array('title' => $this->t('Delete instance.')),
      );
      // Allow altering the operations on this entity listing.
      $this->moduleHandler->alter('entity_operation', $links, $instance);
      $table[$name]['operations']['data'] = array(
        '#type' => 'operations',
        '#links' => $links,
      );

      if (!empty($field->locked)) {
        $table[$name]['operations'] = array('#markup' => $this->t('Locked'));
        $table[$name]['#attributes']['class'][] = 'menu-disabled';
      }
    }

    // Gather valid field types.
    $field_type_options = array();
    foreach ($field_types as $name => $field_type) {
      // Skip field types which should not be added via user interface.
      if (empty($field_type['no_ui'])) {
        $field_type_options[$name] = $field_type['label'];
      }
    }
    asort($field_type_options);

    // Additional row: add new field.
    if ($field_type_options) {
      $name = '_add_new_field';
      $table[$name] = array(
        '#attributes' => array('class' => array('add-new')),
        'label' => array(
          '#type' => 'textfield',
          '#title' => $this->t('New field label'),
          '#title_display' => 'invisible',
          '#size' => 15,
          '#description' => $this->t('Label'),
          '#prefix' => '<div class="label-input"><div class="add-new-placeholder">' . $this->t('Add new field') .'</div>',
          '#suffix' => '</div>',
        ),
        'field_name' => array(
          '#type' => 'machine_name',
          '#title' => $this->t('New field name'),
          '#title_display' => 'invisible',
          // This field should stay LTR even for RTL languages.
          '#field_prefix' => '<span dir="ltr">' . $field_prefix,
          '#field_suffix' => '</span>&lrm;',
          '#size' => 15,
          '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
          // Calculate characters depending on the length of the field prefix
          // setting. Maximum length is 32.
          '#maxlength' => FieldConfig::NAME_MAX_LENGTH - strlen($field_prefix),
          '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
          '#machine_name' => array(
            'source' => array('fields', $name, 'label'),
            'exists' => array($this, 'fieldNameExists'),
            'standalone' => TRUE,
            'label' => '',
          ),
          '#required' => FALSE,
        ),
        'type' => array(
          '#type' => 'select',
          '#title' => $this->t('Type of new field'),
          '#title_display' => 'invisible',
          '#options' => $field_type_options,
          '#empty_option' => $this->t('- Select a field type -'),
          '#description' => $this->t('Type of data to store.'),
          '#attributes' => array('class' => array('field-type-select')),
          '#cell_attributes' => array('colspan' => 2),
          '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
        ),
        // Place the 'translatable' property as an explicit value so that
        // contrib modules can form_alter() the value for newly created fields.
        'translatable' => array(
          '#type' => 'value',
          '#value' => FALSE,
        ),
      );
    }

    // Additional row: re-use existing field.
    $existing_fields = $this->getExistingFieldOptions();
    if ($existing_fields) {
      // Build list of options.
      $existing_field_options = array();
      foreach ($existing_fields as $field_name => $info) {
        $text = $this->t('@type: @field (@label)', array(
          '@type' => $info['type_label'],
          '@label' => $info['label'],
          '@field' => $info['field'],
        ));
        $existing_field_options[$field_name] = truncate_utf8($text, 80, FALSE, TRUE);
      }
      asort($existing_field_options);
      $name = '_add_existing_field';
      $table[$name] = array(
        '#attributes' => array('class' => array('add-new')),
        '#row_type' => 'add_new_field',
        '#region_callback' => array($this, 'getRowRegion'),
        'label' => array(
          '#type' => 'textfield',
          '#title' => $this->t('Existing field label'),
          '#title_display' => 'invisible',
          '#size' => 15,
          '#description' => $this->t('Label'),
          '#attributes' => array('class' => array('label-textfield')),
          '#prefix' => '<div class="label-input"><div class="add-new-placeholder">' . $this->t('Re-use existing field') .'</div>',
          '#suffix' => '</div>',
        ),
        'field_name' => array(
          '#type' => 'select',
          '#title' => $this->t('Existing field to share'),
          '#title_display' => 'invisible',
          '#options' => $existing_field_options,
          '#empty_option' => $this->t('- Select an existing field -'),
          '#description' => $this->t('Field to share'),
          '#attributes' => array('class' => array('field-select')),
          '#cell_attributes' => array('colspan' => 3),
          '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
        ),
      );
    }

    // We can set the 'rows_order' element, needed by theme_field_ui_table(),
    // here instead of a #pre_render callback because this form doesn't have the
    // tabledrag behavior anymore.
    $table['#regions']['content']['rows_order'] = array();
    foreach (element_children($table) as $name) {
      $table['#regions']['content']['rows_order'][] = $name;
    }

    $form['fields'] = $table;

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => $this->t('Save'));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $this->validateAddNew($form, $form_state);
    $this->validateAddExisting($form, $form_state);
  }

  /**
   * Validates the 'add new field' row.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   *
   * @see \Drupal\field_ui\FieldOverview::validateForm()
   */
  protected function validateAddNew(array $form, array &$form_state) {
    $field = $form_state['values']['fields']['_add_new_field'];

    // Validate if any information was provided in the 'add new field' row.
    if (array_filter(array($field['label'], $field['field_name'], $field['type']))) {
      // Missing label.
      if (!$field['label']) {
        $this->setFormError('fields][_add_new_field][label', $form_state, $this->t('Add new field: you need to provide a label.'));
      }

      // Missing field name.
      if (!$field['field_name']) {
        $this->setFormError('fields][_add_new_field][field_name', $form_state, $this->t('Add new field: you need to provide a field name.'));
      }
      // Field name validation.
      else {
        $field_name = $field['field_name'];

        // Add the field prefix.
        $field_name = \Drupal::config('field_ui.settings')->get('field_prefix') . $field_name;
        form_set_value($form['fields']['_add_new_field']['field_name'], $field_name, $form_state);
      }

      // Missing field type.
      if (!$field['type']) {
        $this->setFormError('fields][_add_new_field][type', $form_state, $this->t('Add new field: you need to select a field type.'));
      }
    }
  }

  /**
   * Validates the 're-use existing field' row.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   *
   * @see \Drupal\field_ui\FieldOverview::validate()
   */
  protected function validateAddExisting(array $form, array &$form_state) {
    // The form element might be absent if no existing fields can be added to
    // this bundle.
    if (isset($form_state['values']['fields']['_add_existing_field'])) {
      $field = $form_state['values']['fields']['_add_existing_field'];

      // Validate if any information was provided in the
      // 're-use existing field' row.
      if (array_filter(array($field['label'], $field['field_name']))) {
        // Missing label.
        if (!$field['label']) {
          $this->setFormError('fields][_add_existing_field][label', $form_state, $this->t('Re-use existing field: you need to provide a label.'));
        }

        // Missing existing field name.
        if (!$field['field_name']) {
          $this->setFormError('fields][_add_existing_field][field_name', $form_state, $this->t('Re-use existing field: you need to select a field.'));
        }
      }
    }
  }

  /**
   * Overrides \Drupal\field_ui\OverviewBase::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $error = FALSE;
    $form_values = $form_state['values']['fields'];
    $destinations = array();

    // Create new field.
    if (!empty($form_values['_add_new_field']['field_name'])) {
      $values = $form_values['_add_new_field'];

      $field = array(
        'name' => $values['field_name'],
        'entity_type' => $this->entity_type,
        'type' => $values['type'],
        'translatable' => $values['translatable'],
      );
      $instance = array(
        'field_name' => $values['field_name'],
        'entity_type' => $this->entity_type,
        'bundle' => $this->bundle,
        'label' => $values['label'],
      );

      // Create the field and instance.
      try {
        $this->entityManager->getStorageController('field_config')->create($field)->save();
        $new_instance = $this->entityManager->getStorageController('field_instance_config')->create($instance);
        $new_instance->save();

        // Make sure the field is displayed in the 'default' form mode (using
        // default widget and settings). It stays hidden for other form modes
        // until it is explicitly configured.
        entity_get_form_display($this->entity_type, $this->bundle, 'default')
          ->setComponent($values['field_name'])
          ->save();

        // Make sure the field is displayed in the 'default' view mode (using
        // default formatter and settings). It stays hidden for other view
        // modes until it is explicitly configured.
        entity_get_display($this->entity_type, $this->bundle, 'default')
          ->setComponent($values['field_name'])
          ->save();

        // Always show the field settings step, as the cardinality needs to be
        // configured for new fields.
        $route_parameters = array(
          $this->bundleEntityType => $this->bundle,
          'field_instance_config' => $new_instance->id(),
        );
        $destinations[] = array('route_name' => 'field_ui.field_edit_' . $this->entity_type, 'route_parameters' => $route_parameters);
        $destinations[] = array('route_name' => 'field_ui.instance_edit_' . $this->entity_type, 'route_parameters' => $route_parameters);

        // Store new field information for any additional submit handlers.
        $form_state['fields_added']['_add_new_field'] = $values['field_name'];
      }
      catch (\Exception $e) {
        $error = TRUE;
        drupal_set_message($this->t('There was a problem creating field %label: !message', array('%label' => $instance['label'], '!message' => $e->getMessage())), 'error');
      }
    }

    // Re-use existing field.
    if (!empty($form_values['_add_existing_field']['field_name'])) {
      $values = $form_values['_add_existing_field'];
      $field_name = $values['field_name'];
      $field = field_info_field($this->entity_type, $field_name);
      if (!empty($field->locked)) {
        drupal_set_message($this->t('The field %label cannot be added because it is locked.', array('%label' => $values['label'])), 'error');
      }
      else {
        $instance = array(
          'field_name' => $field_name,
          'entity_type' => $this->entity_type,
          'bundle' => $this->bundle,
          'label' => $values['label'],
        );

        try {
          $new_instance = $this->entityManager->getStorageController('field_instance_config')->create($instance);
          $new_instance->save();

          // Make sure the field is displayed in the 'default' form mode (using
          // default widget and settings). It stays hidden for other form modes
          // until it is explicitly configured.
          entity_get_form_display($this->entity_type, $this->bundle, 'default')
            ->setComponent($field_name)
            ->save();

          // Make sure the field is displayed in the 'default' view mode (using
          // default formatter and settings). It stays hidden for other view
          // modes until it is explicitly configured.
          entity_get_display($this->entity_type, $this->bundle, 'default')
            ->setComponent($field_name)
            ->save();

          $destinations[] = array(
            'route_name' => 'field_ui.instance_edit_' . $this->entity_type,
            'route_parameters' => array(
              $this->bundleEntityType => $this->bundle,
              'field_instance_config' => $new_instance->id(),
            ),
          );
          // Store new field information for any additional submit handlers.
          $form_state['fields_added']['_add_existing_field'] = $instance['field_name'];
        }
        catch (\Exception $e) {
          $error = TRUE;
          drupal_set_message($this->t('There was a problem creating field instance %label: @message.', array('%label' => $instance['label'], '@message' => $e->getMessage())), 'error');
        }
      }
    }

    if ($destinations) {
      $destination = drupal_get_destination();
      $destinations[] = $destination['destination'];
      $form_state['redirect_route'] = FieldUI::getNextDestination($destinations, $form_state);
    }
    elseif (!$error) {
      drupal_set_message($this->t('Your settings have been saved.'));
    }
  }

  /**
   * Returns an array of existing fields to be added to a bundle.
   *
   * @return array
   *   An array of existing fields keyed by field name.
   */
  protected function getExistingFieldOptions() {
    $options = array();

    // Collect candidate field instances: all instances of fields for this
    // entity type that are not already present in the current bundle.
    $field_map = field_info_field_map();
    $instance_ids = array();
    if (!empty($field_map[$this->entity_type])) {
      foreach ($field_map[$this->entity_type] as $field_name => $data) {
        if (!in_array($this->bundle, $data['bundles'])) {
          $bundle = reset($data['bundles']);
          $instance_ids[] = $this->entity_type . '.' . $bundle . '.' . $field_name;
        }
      }
    }

    // Load the instances and build the list of options.
    if ($instance_ids) {
      $field_types = $this->fieldTypeManager->getDefinitions();
      $instances = $this->entityManager->getStorageController('field_instance_config')->loadMultiple($instance_ids);
      foreach ($instances as $instance) {
        // Do not show:
        // - locked fields,
        // - fields that should not be added via user interface.
        $field_type = $instance->getType();
        $field = $instance->getField();
        if (empty($field->locked) && empty($field_types[$field_type]['no_ui'])) {
          $options[$instance->getName()] = array(
            'type' => $field_type,
            'type_label' => $field_types[$field_type]['label'],
            'field' => $instance->getName(),
            'label' => $instance->getLabel(),
          );
        }
      }
    }

    return $options;
  }

  /**
   * Checks if a field machine name is taken.
   *
   * @param string $value
   *   The machine name, not prefixed with 'field_'.
   *
   * @return bool
   *   Whether or not the field machine name is taken.
   */
  public function fieldNameExists($value) {
    // Prefix with 'field_'.
    $field_name = 'field_' . $value;

    return (bool) field_info_field($this->entity_type, $field_name);
  }

}
