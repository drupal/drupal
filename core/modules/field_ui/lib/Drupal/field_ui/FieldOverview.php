<?php

/**
 * @file
 * Definition of Drupal\field_ui\FieldOverview.
 */

namespace Drupal\field_ui;

use Drupal\field_ui\OverviewBase;

/**
 * Field UI field overview form.
 */
class FieldOverview extends OverviewBase {

  /**
   * Implements Drupal\field_ui\OverviewBase::getRegions().
   */
  public function getRegions() {
    return array(
      'content' => array(
        'title' => t('Content'),
        'invisible' => TRUE,
        'message' => t('No fields are present yet.'),
      ),
      'hidden' => array(
        'title' => t('Hidden'),
        'invisible' => TRUE,
        'message' => t('No fields.'),
      ),
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'field_ui_field_overview_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state, $entity_type = NULL, $bundle = NULL, $form_mode = NULL) {
    parent::buildForm($form, $form_state, $entity_type, $bundle);

    $this->mode = (isset($form_mode) ? $form_mode : 'default');
    // When displaying the form, make sure the list of fields is up-to-date.
    if (empty($form_state['post'])) {
      field_info_cache_clear();
    }

    // Gather bundle information.
    $instances = field_info_instances($this->entity_type, $this->bundle);
    $field_types = field_info_field_types();
    $widget_types = field_info_widget_types();
    $extra_fields = field_info_extra_fields($this->entity_type, $this->bundle, 'form');
    $entity_form_display = entity_get_form_display($this->entity_type, $this->bundle, $this->mode);

    $form += array(
      '#entity_type' => $this->entity_type,
      '#bundle' => $this->bundle,
      '#fields' => array_keys($instances),
      '#extra' => array_keys($extra_fields),
    );

    $table = array(
      '#type' => 'field_ui_table',
      '#tree' => TRUE,
      '#header' => array(
        t('Label'),
        t('Weight'),
        t('Parent'),
        t('Machine name'),
        t('Field type'),
        t('Widget'),
        t('Operations'),
      ),
      '#parent_options' => array(),
      '#regions' => $this->getRegions(),
      '#attributes' => array(
        'class' => array('field-ui-overview'),
        'id' => 'field-overview',
      ),
    );

    // Fields.
    foreach ($instances as $name => $instance) {
      $field = field_info_field($instance['field_name']);
      $widget_configuration = $entity_form_display->getComponent($instance['field_name']);
      $admin_field_path = $this->adminPath . '/fields/' . $instance->id();
      $table[$name] = array(
        '#attributes' => array('class' => array('draggable', 'tabledrag-leaf')),
        '#row_type' => 'field',
        '#region_callback' => 'field_ui_field_overview_row_region',
        'label' => array(
          '#markup' => check_plain($instance['label']),
        ),
        'weight' => array(
          '#type' => 'textfield',
          '#title' => t('Weight for @title', array('@title' => $instance['label'])),
          '#title_display' => 'invisible',
          '#default_value' => $widget_configuration ? $widget_configuration['weight'] : '0',
          '#size' => 3,
          '#attributes' => array('class' => array('field-weight')),
         ),
        'parent_wrapper' => array(
          'parent' => array(
            '#type' => 'select',
            '#title' => t('Parent for @title', array('@title' => $instance['label'])),
            '#title_display' => 'invisible',
            '#options' => $table['#parent_options'],
            '#empty_value' => '',
            '#attributes' => array('class' => array('field-parent')),
            '#parents' => array('fields', $name, 'parent'),
          ),
          'hidden_name' => array(
            '#type' => 'hidden',
            '#default_value' => $name,
            '#attributes' => array('class' => array('field-name')),
          ),
        ),
        'field_name' => array(
          '#markup' => $instance['field_name'],
        ),
        'type' => array(
          '#type' => 'link',
          '#title' => $field_types[$field['type']]['label'],
          '#href' => $admin_field_path . '/field',
          '#options' => array('attributes' => array('title' => t('Edit field settings.'))),
        ),
        'widget_type' => array(
          '#type' => 'link',
          '#title' => $widget_configuration ? $widget_types[$widget_configuration['type']]['label'] : $widget_types['hiden']['label'],
          '#href' => $admin_field_path . '/widget-type',
          '#options' => array('attributes' => array('title' => t('Change widget type.'))),
        ),
      );

      $links = array();
      $links['edit'] = array(
        'title' => t('Edit'),
        'href' => $admin_field_path,
        'attributes' => array('title' => t('Edit instance settings.')),
      );
      $links['delete'] = array(
        'title' => t('Delete'),
        'href' => "$admin_field_path/delete",
        'attributes' => array('title' => t('Delete instance.')),
      );
      $table[$name]['operations']['data'] = array(
        '#type' => 'operations',
        '#links' => $links,
      );

      if (!empty($field['locked'])) {
        $table[$name]['operations'] = array('#markup' => t('Locked'));
        $table[$name]['#attributes']['class'][] = 'menu-disabled';
      }
    }

    // Non-field elements.
    foreach ($extra_fields as $name => $extra_field) {
      $table[$name] = array(
        '#attributes' => array('class' => array('draggable', 'tabledrag-leaf')),
        '#row_type' => 'extra_field',
        '#region_callback' => 'field_ui_field_overview_row_region',
        'label' => array(
          '#markup' => check_plain($extra_field['label']),
        ),
        'weight' => array(
          '#type' => 'textfield',
          '#default_value' => $extra_field['weight'],
          '#size' => 3,
          '#attributes' => array('class' => array('field-weight')),
          '#title_display' => 'invisible',
          '#title' => t('Weight for @title', array('@title' => $extra_field['label'])),
        ),
        'parent_wrapper' => array(
          'parent' => array(
            '#type' => 'select',
            '#title' => t('Parent for @title', array('@title' => $extra_field['label'])),
            '#title_display' => 'invisible',
            '#options' => $table['#parent_options'],
            '#empty_value' => '',
            '#attributes' => array('class' => array('field-parent')),
            '#parents' => array('fields', $name, 'parent'),
          ),
          'hidden_name' => array(
            '#type' => 'hidden',
            '#default_value' => $name,
            '#attributes' => array('class' => array('field-name')),
          ),
        ),
        'field_name' => array(
          '#markup' => $name,
        ),
        'type' => array(
          '#markup' => isset($extra_field['description']) ? $extra_field['description'] : '',
          '#cell_attributes' => array('colspan' => 2),
        ),
        'operations' => array(
          '#markup' => '',
        ),
      );
    }

    // Additional row: add new field.
    $max_weight = $entity_form_display->getHighestWeight();
    $field_type_options = field_ui_field_type_options();
    $widget_type_options = field_ui_widget_type_options(NULL, TRUE);
    if ($field_type_options && $widget_type_options) {
      $name = '_add_new_field';
      $table[$name] = array(
        '#attributes' => array('class' => array('draggable', 'tabledrag-leaf', 'add-new')),
        '#row_type' => 'add_new_field',
        '#region_callback' => 'field_ui_field_overview_row_region',
        'label' => array(
          '#type' => 'textfield',
          '#title' => t('New field label'),
          '#title_display' => 'invisible',
          '#size' => 15,
          '#description' => t('Label'),
          '#prefix' => '<div class="label-input"><div class="add-new-placeholder">' . t('Add new field') .'</div>',
          '#suffix' => '</div>',
        ),
        'weight' => array(
          '#type' => 'textfield',
          '#default_value' => $max_weight + 1,
          '#size' => 3,
          '#title_display' => 'invisible',
          '#title' => t('Weight for new field'),
          '#attributes' => array('class' => array('field-weight')),
          '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
        ),
        'parent_wrapper' => array(
          'parent' => array(
            '#type' => 'select',
            '#title' => t('Parent for new field'),
            '#title_display' => 'invisible',
            '#options' => $table['#parent_options'],
            '#empty_value' => '',
            '#attributes' => array('class' => array('field-parent')),
            '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
            '#parents' => array('fields', $name, 'parent'),
          ),
          'hidden_name' => array(
            '#type' => 'hidden',
            '#default_value' => $name,
            '#attributes' => array('class' => array('field-name')),
          ),
        ),
        'field_name' => array(
          '#type' => 'machine_name',
          '#title' => t('New field name'),
          '#title_display' => 'invisible',
          // This field should stay LTR even for RTL languages.
          '#field_prefix' => '<span dir="ltr">field_',
          '#field_suffix' => '</span>&lrm;',
          '#size' => 15,
          '#description' => t('A unique machine-readable name containing letters, numbers, and underscores.'),
          // 32 characters minus the 'field_' prefix.
          '#maxlength' => 26,
          '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
          '#machine_name' => array(
            'source' => array('fields', $name, 'label'),
            'exists' => '_field_ui_field_name_exists',
            'standalone' => TRUE,
            'label' => '',
          ),
          '#required' => FALSE,
        ),
        'type' => array(
          '#type' => 'select',
          '#title' => t('Type of new field'),
          '#title_display' => 'invisible',
          '#options' => $field_type_options,
          '#empty_option' => t('- Select a field type -'),
          '#description' => t('Type of data to store.'),
          '#attributes' => array('class' => array('field-type-select')),
          '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
        ),
        'widget_type' => array(
          '#type' => 'select',
          '#title' => t('Widget for new field'),
          '#title_display' => 'invisible',
          '#options' => $widget_type_options,
          '#empty_option' => t('- Select a widget -'),
          '#description' => t('Form element to edit the data.'),
          '#attributes' => array('class' => array('widget-type-select')),
          '#cell_attributes' => array('colspan' => 3),
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
    $existing_fields = field_ui_existing_field_options($this->entity_type, $this->bundle);
    if ($existing_fields && $widget_type_options) {
      // Build list of options.
      $existing_field_options = array();
      foreach ($existing_fields as $field_name => $info) {
        $text = t('@type: @field (@label)', array(
          '@type' => $info['type_label'],
          '@label' => $info['label'],
          '@field' => $info['field'],
        ));
        $existing_field_options[$field_name] = truncate_utf8($text, 80, FALSE, TRUE);
      }
      asort($existing_field_options);
      $name = '_add_existing_field';
      $table[$name] = array(
        '#attributes' => array('class' => array('draggable', 'tabledrag-leaf', 'add-new')),
        '#row_type' => 'add_new_field',
        '#region_callback' => 'field_ui_field_overview_row_region',
        'label' => array(
          '#type' => 'textfield',
          '#title' => t('Existing field label'),
          '#title_display' => 'invisible',
          '#size' => 15,
          '#description' => t('Label'),
          '#attributes' => array('class' => array('label-textfield')),
          '#prefix' => '<div class="label-input"><div class="add-new-placeholder">' . t('Re-use existing field') .'</div>',
          '#suffix' => '</div>',
        ),
        'weight' => array(
          '#type' => 'textfield',
          '#default_value' => $max_weight + 2,
          '#size' => 3,
          '#title_display' => 'invisible',
          '#title' => t('Weight for added field'),
          '#attributes' => array('class' => array('field-weight')),
          '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
        ),
        'parent_wrapper' => array(
          'parent' => array(
            '#type' => 'select',
            '#title' => t('Parent for existing field'),
            '#title_display' => 'invisible',
            '#options' => $table['#parent_options'],
            '#empty_value' => '',
            '#attributes' => array('class' => array('field-parent')),
            '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
            '#parents' => array('fields', $name, 'parent'),
          ),
          'hidden_name' => array(
            '#type' => 'hidden',
            '#default_value' => $name,
            '#attributes' => array('class' => array('field-name')),
          ),
        ),
        'field_name' => array(
          '#type' => 'select',
          '#title' => t('Existing field to share'),
          '#title_display' => 'invisible',
          '#options' => $existing_field_options,
          '#empty_option' => t('- Select an existing field -'),
          '#description' => t('Field to share'),
          '#attributes' => array('class' => array('field-select')),
          '#cell_attributes' => array('colspan' => 2),
          '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
        ),
        'widget_type' => array(
          '#type' => 'select',
          '#title' => t('Widget for existing field'),
          '#title_display' => 'invisible',
          '#options' => $widget_type_options,
          '#empty_option' => t('- Select a widget -'),
          '#description' => t('Form element to edit the data.'),
          '#attributes' => array('class' => array('widget-type-select')),
          '#cell_attributes' => array('colspan' => 3),
          '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
        ),
      );
    }
    $form['fields'] = $table;

    // Add AJAX wrapper.
    $form['fields']['#prefix'] = '<div id="field-display-overview-wrapper">';
    $form['fields']['#suffix'] = '</div>';

    // This key is used to store the current updated field.
    $form_state += array(
      'formatter_settings_edit' => NULL,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save'));

    $form['#attached']['library'][] = array('field_ui', 'drupal.field_ui');

    // Add settings for the update selects behavior.
    $js_fields = array();
    foreach ($existing_fields as $field_name => $info) {
      $js_fields[$field_name] = array('label' => $info['label'], 'type' => $info['type'], 'widget' => $info['widget_type']);
    }

    $form['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('fields' => $js_fields, 'fieldWidgetTypes' => field_ui_widget_type_options()),
    );

    // Add tabledrag behavior.
    $form['#attached']['drupal_add_tabledrag'][] = array('field-overview', 'order', 'sibling', 'field-weight');
    $form['#attached']['drupal_add_tabledrag'][] = array('field-overview', 'match', 'parent', 'field-parent', 'field-parent', 'field-name');

    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
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
   * @see Drupal\field_ui\FieldOverview::validateForm()
   */
  protected function validateAddNew(array $form, array &$form_state) {
    $field = $form_state['values']['fields']['_add_new_field'];

    // Validate if any information was provided in the 'add new field' row.
    if (array_filter(array($field['label'], $field['field_name'], $field['type'], $field['widget_type']))) {
      // Missing label.
      if (!$field['label']) {
        form_set_error('fields][_add_new_field][label', t('Add new field: you need to provide a label.'));
      }

      // Missing field name.
      if (!$field['field_name']) {
        form_set_error('fields][_add_new_field][field_name', t('Add new field: you need to provide a field name.'));
      }
      // Field name validation.
      else {
        $field_name = $field['field_name'];

        // Add the 'field_' prefix.
        $field_name = 'field_' . $field_name;
        form_set_value($form['fields']['_add_new_field']['field_name'], $field_name, $form_state);
      }

      // Missing field type.
      if (!$field['type']) {
        form_set_error('fields][_add_new_field][type', t('Add new field: you need to select a field type.'));
      }

      // Missing widget type.
      if (!$field['widget_type']) {
        form_set_error('fields][_add_new_field][widget_type', t('Add new field: you need to select a widget.'));
      }
      // Wrong widget type.
      elseif ($field['type']) {
        $widget_types = field_ui_widget_type_options($field['type']);
        if (!isset($widget_types[$field['widget_type']])) {
          form_set_error('fields][_add_new_field][widget_type', t('Add new field: invalid widget.'));
        }
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
   * @see Drupal\field_ui\FieldOverview::validate()
   */
  protected function validateAddExisting(array $form, array &$form_state) {
    // The form element might be absent if no existing fields can be added to
    // this bundle.
    if (isset($form_state['values']['fields']['_add_existing_field'])) {
      $field = $form_state['values']['fields']['_add_existing_field'];

      // Validate if any information was provided in the
      // 're-use existing field' row.
      if (array_filter(array($field['label'], $field['field_name'], $field['widget_type']))) {
        // Missing label.
        if (!$field['label']) {
          form_set_error('fields][_add_existing_field][label', t('Re-use existing field: you need to provide a label.'));
        }

        // Missing existing field name.
        if (!$field['field_name']) {
          form_set_error('fields][_add_existing_field][field_name', t('Re-use existing field: you need to select a field.'));
        }

        // Missing widget type.
        if (!$field['widget_type']) {
          form_set_error('fields][_add_existing_field][widget_type', t('Re-use existing field: you need to select a widget.'));
        }
        // Wrong widget type.
        elseif ($field['field_name'] && ($existing_field = field_info_field($field['field_name']))) {
          $widget_types = field_ui_widget_type_options($existing_field['type']);
          if (!isset($widget_types[$field['widget_type']])) {
            form_set_error('fields][_add_existing_field][widget_type', t('Re-use existing field: invalid widget.'));
          }
        }
      }
    }
  }

  /**
   * Overrides \Drupal\field_ui\OverviewBase::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_values = $form_state['values']['fields'];
    $entity_form_display = entity_get_form_display($this->entity_type, $this->bundle, $this->mode);

    // Collect data for 'regular' fields.
    foreach ($form['#fields'] as $field_name) {
      $options = $entity_form_display->getComponent($field_name);
      $options['weight'] = $form_values[$field_name]['weight'];

      $entity_form_display->setComponent($field_name, $options);
    }

    // Collect data for 'extra' fields.
    foreach ($form['#extra'] as $field_name) {
      $entity_form_display->setComponent($field_name, array(
        'weight' => $form_values[$field_name]['weight'],
      ));
    }

    // Save the form display.
    $entity_form_display->save();

    $destinations = array();

    // Create new field.
    if (!empty($form_values['_add_new_field']['field_name'])) {
      $values = $form_values['_add_new_field'];

      $field = array(
        'field_name' => $values['field_name'],
        'type' => $values['type'],
        'translatable' => $values['translatable'],
      );
      $instance = array(
        'field_name' => $field['field_name'],
        'entity_type' => $this->entity_type,
        'bundle' => $this->bundle,
        'label' => $values['label'],
      );

      // Create the field and instance.
      try {
        $this->entityManager->getStorageController('field_entity')->create($field)->save();
        $new_instance = $this->entityManager->getStorageController('field_instance')->create($instance);
        $new_instance->save();

        // Make sure the field is displayed in the 'default' form mode (using
        // the configured widget and default settings).
        entity_get_form_display($this->entity_type, $this->bundle, 'default')
          ->setComponent($field['field_name'], array(
            'type' => $values['widget_type'],
            'weight' => $values['weight'],
          ))
          ->save();

        // Make sure the field is displayed in the 'default' view mode (using
        // default formatter and settings). It stays hidden for other view
        // modes until it is explicitly configured.
        entity_get_display($this->entity_type, $this->bundle, 'default')
          ->setComponent($field['field_name'])
          ->save();

        // Always show the field settings step, as the cardinality needs to be
        // configured for new fields.
        $destinations[] = $this->adminPath. '/fields/' . $new_instance->id() . '/field';
        $destinations[] = $this->adminPath . '/fields/' . $new_instance->id();

        // Store new field information for any additional submit handlers.
        $form_state['fields_added']['_add_new_field'] = $field['field_name'];
      }
      catch (\Exception $e) {
        drupal_set_message(t('There was a problem creating field %label: !message', array('%label' => $instance['label'], '!message' => $e->getMessage())), 'error');
      }
    }

    // Re-use existing field.
    if (!empty($form_values['_add_existing_field']['field_name'])) {
      $values = $form_values['_add_existing_field'];
      $field = field_info_field($values['field_name']);
      if (!empty($field['locked'])) {
        drupal_set_message(t('The field %label cannot be added because it is locked.', array('%label' => $values['label'])), 'error');
      }
      else {
        $instance = array(
          'field_name' => $field['field_name'],
          'entity_type' => $this->entity_type,
          'bundle' => $this->bundle,
          'label' => $values['label'],
        );

        try {
          $new_instance = $this->entityManager->getStorageController('field_instance')->create($instance);
          $new_instance->save();

          // Make sure the field is displayed in the 'default' form mode (using
          // the configured widget and default settings).
          entity_get_form_display($this->entity_type, $this->bundle, 'default')
            ->setComponent($field['field_name'], array(
              'type' => $values['widget_type'],
              'weight' => $values['weight'],
            ))
            ->save();

          // Make sure the field is displayed in the 'default' view mode (using
          // default formatter and settings). It stays hidden for other view
          // modes until it is explicitly configured.
          entity_get_display($this->entity_type, $this->bundle, 'default')
            ->setComponent($field['field_name'])
            ->save();

          $destinations[] = $this->adminPath . '/fields/' . $new_instance->id();
          // Store new field information for any additional submit handlers.
          $form_state['fields_added']['_add_existing_field'] = $instance['field_name'];
        }
        catch (\Exception $e) {
          drupal_set_message(t('There was a problem creating field instance %label: @message.', array('%label' => $instance['label'], '@message' => $e->getMessage())), 'error');
        }
      }
    }

    if ($destinations) {
      $destination = drupal_get_destination();
      $destinations[] = $destination['destination'];
      unset($_GET['destination']);
      $form_state['redirect'] = field_ui_get_destinations($destinations);
    }
    else {
      drupal_set_message(t('Your settings have been saved.'));
    }
  }
}
