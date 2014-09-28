<?php

/**
 * @file
 * Contains \Drupal\field_ui\FieldOverview.
 */

namespace Drupal\field_ui;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityListBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\field_ui\OverviewBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;

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
   * Constructs a new FieldOverview.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager
   */
  public function __construct(EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct($entity_manager);
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.field_type')
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
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    parent::buildForm($form, $form_state, $entity_type_id, $bundle);

    // Gather bundle information.
    $fields = array_filter(\Drupal::entityManager()->getFieldDefinitions($this->entity_type, $this->bundle), function ($field_definition) {
      return $field_definition instanceof FieldConfigInterface;
    });
    $field_types = $this->fieldTypeManager->getDefinitions();

    // Field prefix.
    $field_prefix = \Drupal::config('field_ui.settings')->get('field_prefix');

    $form += array(
      '#entity_type' => $this->entity_type,
      '#bundle' => $this->bundle,
      '#fields' => array_keys($fields),
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
    foreach ($fields as $name => $field) {
      $field_storage = $field->getFieldStorageDefinition();
      $route_parameters = array(
        $this->bundleEntityType => $this->bundle,
        'field_config' => $field->id(),
      );
      $table[$name] = array(
        '#attributes' => array(
          'id' => drupal_html_class($name),
        ),
        'label' => array(
          '#markup' => String::checkPlain($field->getLabel()),
        ),
        'field_name' => array(
          '#markup' => $field->getName(),
        ),
        'type' => array(
          '#type' => 'link',
          '#title' => $field_types[$field_storage->getType()]['label'],
          '#route_name' => 'field_ui.storage_edit_' . $this->entity_type,
          '#route_parameters' => $route_parameters,
          '#options' => array('attributes' => array('title' => $this->t('Edit field settings.'))),
        ),
      );

      $table[$name]['operations']['data'] = array(
        '#type' => 'operations',
        '#links' => $this->entityManager->getListBuilder('field_config')->getOperations($field),
      );

      if (!empty($field_storage->locked)) {
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
          '#maxlength' => FieldStorageConfig::NAME_MAX_LENGTH - strlen($field_prefix),
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
          '#value' => TRUE,
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
    foreach (Element::children($table) as $name) {
      $table['#regions']['content']['rows_order'][] = $name;
    }

    $form['fields'] = $table;

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateAddNew($form, $form_state);
    $this->validateAddExisting($form, $form_state);
  }

  /**
   * Validates the 'add new field' row.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\field_ui\FieldOverview::validateForm()
   */
  protected function validateAddNew(array $form, FormStateInterface $form_state) {
    $field = $form_state->getValue(array('fields', '_add_new_field'));

    // Validate if any information was provided in the 'add new field' row.
    if (array_filter(array($field['label'], $field['field_name'], $field['type']))) {
      // Missing label.
      if (!$field['label']) {
        $form_state->setErrorByName('fields][_add_new_field][label', $this->t('Add new field: you need to provide a label.'));
      }

      // Missing field name.
      if (!$field['field_name']) {
        $form_state->setErrorByName('fields][_add_new_field][field_name', $this->t('Add new field: you need to provide a machine name for the field.'));
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
        $form_state->setErrorByName('fields][_add_new_field][type', $this->t('Add new field: you need to select a field type.'));
      }
    }
  }

  /**
   * Validates the 're-use existing field' row.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\field_ui\FieldOverview::validate()
   */
  protected function validateAddExisting(array $form, FormStateInterface $form_state) {
    // The form element might be absent if no existing fields can be added to
    // this bundle.
    if ($field = $form_state->getValue(array('fields', '_add_existing_field'))) {
      // Validate if any information was provided in the
      // 're-use existing field' row.
      if (array_filter(array($field['label'], $field['field_name']))) {
        // Missing label.
        if (!$field['label']) {
          $form_state->setErrorByName('fields][_add_existing_field][label', $this->t('Re-use existing field: you need to provide a label.'));
        }

        // Missing existing field name.
        if (!$field['field_name']) {
          $form_state->setErrorByName('fields][_add_existing_field][field_name', $this->t('Re-use existing field: you need to select a field.'));
        }
      }
    }
  }

  /**
   * Overrides \Drupal\field_ui\OverviewBase::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $error = FALSE;
    $form_values = $form_state->getValue('fields');
    $destinations = array();

    // Create new field.
    if (!empty($form_values['_add_new_field']['field_name'])) {
      $values = $form_values['_add_new_field'];

      $field_storage = array(
        'field_name' => $values['field_name'],
        'entity_type' => $this->entity_type,
        'type' => $values['type'],
        'translatable' => $values['translatable'],
      );
      $field = array(
        'field_name' => $values['field_name'],
        'entity_type' => $this->entity_type,
        'bundle' => $this->bundle,
        'label' => $values['label'],
        // Field translatability should be explicitly enabled by the users.
        'translatable' => FALSE,
      );

      // Create the field storage and field.
      try {
        $this->entityManager->getStorage('field_storage_config')->create($field_storage)->save();
        $new_field = $this->entityManager->getStorage('field_config')->create($field);
        $new_field->save();

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
          'field_config' => $new_field->id(),
        );
        $destinations[] = array('route_name' => 'field_ui.storage_edit_' . $this->entity_type, 'route_parameters' => $route_parameters);
        $destinations[] = array('route_name' => 'field_ui.field_edit_' . $this->entity_type, 'route_parameters' => $route_parameters);

        // Store new field information for any additional submit handlers.
        $form_state->set(['fields_added', '_add_new_field'], $values['field_name']);
      }
      catch (\Exception $e) {
        $error = TRUE;
        drupal_set_message($this->t('There was a problem creating field %label: !message', array('%label' => $field['label'], '!message' => $e->getMessage())), 'error');
      }
    }

    // Re-use existing field.
    if (!empty($form_values['_add_existing_field']['field_name'])) {
      $values = $form_values['_add_existing_field'];
      $field_name = $values['field_name'];
      $field_storage = FieldStorageConfig::loadByName($this->entity_type, $field_name);
      if (!empty($field_storage->locked)) {
        drupal_set_message($this->t('The field %label cannot be added because it is locked.', array('%label' => $values['label'])), 'error');
      }
      else {
        $field = array(
          'field_name' => $field_name,
          'entity_type' => $this->entity_type,
          'bundle' => $this->bundle,
          'label' => $values['label'],
        );

        try {
          $new_field = $this->entityManager->getStorage('field_config')->create($field);
          $new_field->save();

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
            'route_name' => 'field_ui.field_edit_' . $this->entity_type,
            'route_parameters' => array(
              $this->bundleEntityType => $this->bundle,
              'field_config' => $new_field->id(),
            ),
          );
          // Store new field information for any additional submit handlers.
          $form_state->set(['fields_added', '_add_existing_field'], $field['field_name']);
        }
        catch (\Exception $e) {
          $error = TRUE;
          drupal_set_message($this->t('There was a problem creating field %label: @message.', array('%label' => $field['label'], '@message' => $e->getMessage())), 'error');
        }
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
   * Returns an array of existing fields to be added to a bundle.
   *
   * @return array
   *   An array of existing fields keyed by field name.
   */
  protected function getExistingFieldOptions() {
    $options = array();

    // Collect candidate fields: all fields of field storages for this
    // entity type that are not already present in the current bundle.
    $field_map = \Drupal::entityManager()->getFieldMap();
    $field_ids = array();
    if (!empty($field_map[$this->entity_type])) {
      foreach ($field_map[$this->entity_type] as $field_name => $data) {
        if (!in_array($this->bundle, $data['bundles'])) {
          $bundle = reset($data['bundles']);
          $field_ids[] = $this->entity_type . '.' . $bundle . '.' . $field_name;
        }
      }
    }

    // Load the fields and build the list of options.
    if ($field_ids) {
      $field_types = $this->fieldTypeManager->getDefinitions();
      $fields = $this->entityManager->getStorage('field_config')->loadMultiple($field_ids);
      foreach ($fields as $field) {
        // Do not show:
        // - locked fields,
        // - fields that should not be added via user interface.
        $field_type = $field->getType();
        $field_storage = $field->getFieldStorageDefinition();
        if (empty($field_storage->locked) && empty($field_types[$field_type]['no_ui'])) {
          $options[$field->getName()] = array(
            'type' => $field_type,
            'type_label' => $field_types[$field_type]['label'],
            'field' => $field->getName(),
            'label' => $field->getLabel(),
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
   *   The machine name, not prefixed.
   *
   * @return bool
   *   Whether or not the field machine name is taken.
   */
  public function fieldNameExists($value) {
    // Add the field prefix.
    $field_name = \Drupal::config('field_ui.settings')->get('field_prefix') . $value;

    $field_storage_definitions = \Drupal::entityManager()->getFieldStorageDefinitions($this->entity_type);
    return isset($field_storage_definitions[$field_name]);
  }

}
