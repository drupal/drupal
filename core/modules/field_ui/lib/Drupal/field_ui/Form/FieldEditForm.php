<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldEditForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\field\Plugin\Core\Entity\FieldInstance;
use Drupal\field\Field;

/**
 * Provides a form for the field settings edit page.
 */
class FieldEditForm implements FormInterface {

  /**
   * The field instance being edited.
   *
   * @var \Drupal\field\Plugin\Core\Entity\FieldInstance
   */
  protected $instance;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'field_ui_field_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, FieldInstance $field_instance = NULL) {
    $this->instance = $form_state['instance'] = $field_instance;
    $field = $this->instance->getField();
    $form['#field'] = $field;

    drupal_set_title($this->instance->label());

    $description = '<p>' . t('These settings apply to the %field field everywhere it is used. These settings impact the way that data is stored in the database and cannot be changed once data has been created.', array('%field' => $this->instance->label())) . '</p>';

    // Create a form structure for the field values.
    $form['field'] = array(
      '#prefix' => $description,
      '#tree' => TRUE,
    );

    // See if data already exists for this field.
    // If so, prevent changes to the field settings.
    $has_data = field_has_data($field);
    if ($has_data) {
      $form['field']['#prefix'] = '<div class="messages error">' . t('There is data for this field in the database. The field settings can no longer be changed.') . '</div>' . $form['field']['#prefix'];
    }

    // Build the configurable field values.
    $cardinality = $field['cardinality'];
    $form['field']['container'] = array(
      // We can't use the container element because it doesn't support the title
      // or description properties.
      '#type' => 'item',
      '#field_prefix' => '<div class="container-inline">',
      '#field_suffix' => '</div>',
      '#title' => t('Maximum number of values users can enter'),
    );
    $form['field']['container']['cardinality'] = array(
      '#type' => 'select',
      '#options' => drupal_map_assoc(range(1, 5)) + array(FIELD_CARDINALITY_UNLIMITED => t('Unlimited')) + array('other' => t('More')),
      '#default_value' => ($cardinality < 6) ? $cardinality : 'other',
    );
    // @todo Convert when http://drupal.org/node/1207060 gets in.
    $form['field']['container']['cardinality_other'] = array(
      '#type' => 'number',
      '#default_value' => $cardinality > 5 ? $cardinality : 6,
      '#min' => 1,
      '#title' => t('Custom value'),
      '#title_display' => 'invisible',
      '#states' => array(
        'visible' => array(
         ':input[name="field[container][cardinality]"]' => array('value' => 'other'),
        ),
      ),
    );
    if (field_behaviors_widget('multiple values', $this->instance) == FIELD_BEHAVIOR_DEFAULT) {
      $form['field']['container']['#description'] = t('%unlimited will provide an %add-more button so users can add as many values as they like.', array(
        '%unlimited' => t('Unlimited'),
        '%add-more' => t('Add another item'),
      ));
    }

    // Build the non-configurable field values.
    $form['field']['field_name'] = array('#type' => 'value', '#value' => $field['field_name']);
    $form['field']['type'] = array('#type' => 'value', '#value' => $field['type']);
    $form['field']['module'] = array('#type' => 'value', '#value' => $field['module']);
    $form['field']['active'] = array('#type' => 'value', '#value' => $field['active']);

    // Add settings provided by the field module. The field module is
    // responsible for not returning settings that cannot be changed if
    // the field already has data.
    $form['field']['settings'] = array(
      '#weight' => 10,
    );
    $additions = \Drupal::moduleHandler()->invoke($field['module'], 'field_settings_form', array($field, $this->instance, $has_data));
    if (is_array($additions)) {
      $form['field']['settings'] += $additions;
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save field settings'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // Validate field cardinality.
    $cardinality = $form_state['values']['field']['container']['cardinality'];
    $cardinality_other = $form_state['values']['field']['container']['cardinality_other'];
    if ($cardinality == 'other' && empty($cardinality_other)) {
      form_error($form['field']['container']['cardinality_other'], t('Number of values is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    form_load_include($form_state, 'inc', 'field_ui', 'field_ui.admin');
    $form_values = $form_state['values'];
    $field_values = $form_values['field'];

    // Save field cardinality.
    $cardinality = $field_values['container']['cardinality'];
    $cardinality_other = $field_values['container']['cardinality_other'];
    $cardinality_other = $form_state['values']['field']['container']['cardinality_other'];
    if ($cardinality == 'other') {
      $cardinality = $cardinality_other;
    }
    $field_values['cardinality'] = $cardinality;
    unset($field_values['container']);

    // Merge incoming form values into the existing field.
    $field = Field::fieldInfo()->getField($field_values['field_name']);
    foreach ($field_values as $key => $value) {
      $field[$key] = $value;
    }

    // Update the field.
    try {
      $field->save();
      drupal_set_message(t('Updated field %label field settings.', array('%label' => $this->instance->label())));
      $form_state['redirect'] = field_ui_next_destination($this->instance->entity_type, $this->instance->bundle);
    }
    catch (Exception $e) {
      drupal_set_message(t('Attempt to update field %label failed: %message.', array('%label' => $this->instance->label(), '%message' => $e->getMessage())), 'error');
    }
  }

}
