<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldWidgetTypeForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\field\FieldInstanceInterface;

/**
 * Provides a form for the widget selection form.
 */
class FieldWidgetTypeForm extends FieldInstanceFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'field_ui_widget_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, FieldInstanceInterface $field_instance = NULL) {
    parent::buildForm($form, $form_state, $field_instance);

    drupal_set_title($this->instance['label']);

    $bundle = $this->instance['bundle'];
    $entity_type = $this->instance['entity_type'];
    $field_name = $this->instance['field_name'];

    $entity_form_display = entity_get_form_display($entity_type, $bundle, 'default');
    $field = $this->instance->getField();
    $bundles = entity_get_bundles();
    $bundle_label = $bundles[$entity_type][$bundle]['label'];

    $form = array(
      '#bundle' => $bundle,
      '#entity_type' => $entity_type,
      '#field_name' => $field_name,
      '#instance' => $this->instance,
    );

    $form['widget_type'] = array(
      '#type' => 'select',
      '#title' => t('Widget type'),
      '#required' => TRUE,
      '#options' => $this->widgetManager->getOptions($field['type']),
      '#default_value' => $entity_form_display->getWidget($field_name)->getPluginId(),
      '#description' => t('The type of form element you would like to present to the user when creating this field in the %type type.', array('%type' => $bundle_label)),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Continue'));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_values = $form_state['values'];
    $bundle = $form['#bundle'];
    $entity_type = $form['#entity_type'];
    $field_name = $form['#field_name'];
    $instance = $form['#instance'];

    $entity_form_display = entity_get_form_display($entity_type, $bundle, 'default')
      ->setComponent($field_name, array(
        'type' => $form_values['widget_type'],
      ));

    try {
      $entity_form_display->save();
      drupal_set_message(t('Changed the widget for field %label.', array('%label' => $instance['label'])));

      if ($instance['required'] && empty($instance['default_value']) && empty($instance['default_value_function']) && $instance['widget']['type'] == 'field_hidden') {
        drupal_set_message(t('Field %label is required and uses the "hidden" widget. You might want to configure a default value.', array('%label' => $instance['label'])), 'warning');
      }
    }
    catch (\Exception $e) {
      drupal_set_message(t('There was a problem changing the widget for field %label.', array('%label' => $instance['label'])), 'error');
    }

    $form_state['redirect'] = $this->getNextDestination();
  }

}
