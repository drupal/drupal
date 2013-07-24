<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldInstanceEditForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Language\Language;
use Drupal\field\FieldInstanceInterface;

/**
 * Provides a form for the field instance settings form.
 */
class FieldInstanceEditForm extends FieldInstanceFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'field_ui_field_instance_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, FieldInstanceInterface $field_instance = NULL) {
    parent::buildForm($form, $form_state, $field_instance);

    $bundle = $this->instance['bundle'];
    $entity_type = $this->instance['entity_type'];
    $field = $this->instance->getField();
    $entity_form_display = entity_get_form_display($entity_type, $bundle, 'default');
    $bundles = entity_get_bundles();

    drupal_set_title(t('%instance settings for %bundle', array(
      '%instance' => $this->instance->label(),
      '%bundle' => $bundles[$entity_type][$bundle]['label'],
    )), PASS_THROUGH);

    $form['#field'] = $field;
    $form['#entity_form_display'] = $entity_form_display;
    // Create an arbitrary entity object (used by the 'default value' widget).
    $ids = (object) array('entity_type' => $this->instance['entity_type'], 'bundle' => $this->instance['bundle'], 'entity_id' => NULL);
    $form['#entity'] = _field_create_entity_from_ids($ids);
    $form['#entity']->field_ui_default_value = TRUE;

    if (!empty($field['locked'])) {
      $form['locked'] = array(
        '#markup' => t('The field %field is locked and cannot be edited.', array('%field' => $this->instance->label())),
      );
      return $form;
    }

    // Create a form structure for the instance values.
    $form['instance'] = array(
      '#tree' => TRUE,
    );

    // Build the non-configurable instance values.
    $form['instance']['field_name'] = array(
      '#type' => 'value',
      '#value' => $this->instance['field_name'],
    );
    $form['instance']['entity_type'] = array(
      '#type' => 'value',
      '#value' => $entity_type,
    );
    $form['instance']['bundle'] = array(
      '#type' => 'value',
      '#value' => $bundle,
    );

    // Build the configurable instance values.
    $form['instance']['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $this->instance->label() ?: $field['field_name'],
      '#required' => TRUE,
      '#weight' => -20,
    );

    $form['instance']['description'] = array(
      '#type' => 'textarea',
      '#title' => t('Help text'),
      '#default_value' => !empty($this->instance['description']) ? $this->instance['description'] : '',
      '#rows' => 5,
      '#description' => t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', array('@tags' => _field_filter_xss_display_allowed_tags())) . '<br />' . t('This field supports tokens.'),
      '#weight' => -10,
    );

    $form['instance']['required'] = array(
      '#type' => 'checkbox',
      '#title' => t('Required field'),
      '#default_value' => !empty($this->instance['required']),
      '#weight' => -5,
    );

    // Add instance settings for the field type.
    $form['instance']['settings'] = $this->getFieldItem($form['#entity'], $this->instance['field_name'])->instanceSettingsForm($form, $form_state);
    $form['instance']['settings']['#weight'] = 10;

    // Add handling for default value if not provided by any other module.
    if (field_behaviors_widget('default_value', $this->instance) == FIELD_BEHAVIOR_DEFAULT && empty($this->instance['default_value_function'])) {
      $form['instance']['default_value_widget'] = $this->getDefaultValueWidget($field, $form, $form_state);
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save settings')
    );
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete field'),
      '#submit' => array(array($this, 'delete')),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // Take the incoming values as the $this->instance definition, so that the 'default
    // value' gets validated using the instance settings being submitted.
    $field_name = $this->instance['field_name'];
    $entity = $form['#entity'];
    $entity_form_display = $form['#entity_form_display'];

    if (isset($form['instance']['default_value_widget'])) {
      $element = $form['instance']['default_value_widget'];

      // Extract the 'default value'.
      $items = $entity->getNGEntity()->{$field_name};
      $entity_form_display->getRenderer($this->instance->getField()->id)->extractFormValues($entity, Language::LANGCODE_NOT_SPECIFIED, $items, $element, $form_state);
      $violations = $items->validate();

      // Report errors.
      if (count($violations)) {
        $field_state = field_form_get_state($element['#parents'], $field_name, Language::LANGCODE_NOT_SPECIFIED, $form_state);
        // Store reported errors in $form_state.
        $field_state['constraint_violations'] = $violations;
        field_form_set_state($element['#parents'], $field_name, Language::LANGCODE_NOT_SPECIFIED, $form_state, $field_state);

        // Assign reported errors to the correct form element.
        $entity_form_display->getRenderer($this->instance->getField()->id)->flagErrors($entity, Language::LANGCODE_NOT_SPECIFIED, $items, $element, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $field_name = $this->instance['field_name'];
    $entity = $form['#entity'];
    $entity_form_display = $form['#entity_form_display'];

    // Handle the default value.
    if (isset($form['instance']['default_value_widget'])) {
      $element = $form['instance']['default_value_widget'];

      // Extract field values.
      $items = $entity->getNGEntity()->{$field_name};
      $entity_form_display->getRenderer($this->instance->getField()->id)->extractFormValues($entity, Language::LANGCODE_NOT_SPECIFIED, $items, $element, $form_state);

      $this->instance['default_value'] = $items->getValue() ?: NULL;
    }

    // Merge incoming values into the instance.
    foreach ($form_state['values']['instance'] as $key => $value) {
      $this->instance[$key] = $value;
    }
    $this->instance->save();

    drupal_set_message(t('Saved %label configuration.', array('%label' => $this->instance->label())));

    $form_state['redirect'] = $this->getNextDestination();
  }

  /**
   * Redirects to the field instance deletion form.
   */
  public function delete(array &$form, array &$form_state) {
    $destination = array();
    if (isset($_GET['destination'])) {
      $destination = drupal_get_destination();
      unset($_GET['destination']);
    }
    $form_state['redirect'] = array('admin/structure/types/manage/' . $this->instance['bundle'] . '/fields/' . $this->instance->id() . '/delete', array('query' => $destination));
  }

  /**
   * Builds the default value widget for a given field instance.
   */
  protected function getDefaultValueWidget($field, array &$form, &$form_state) {
    $entity = $form['#entity'];
    $entity_form_display = $form['#entity_form_display'];

    $element = array(
      '#type' => 'details',
      '#title' => t('Default value'),
      '#tree' => TRUE,
      '#description' => t('The default value for this field, used when creating new content.'),
      // Stick to an empty 'parents' on this form in order not to breaks widgets
      // that do not use field_widget_[field|instance]() and still access
      // $form_state['field'] directly.
      '#parents' => array(),
    );

    // Adjust the instance definition used for the form element. We want a
    // non-required input and no description.
    $this->instance['required'] = FALSE;
    $this->instance['description'] = '';

    // Adjust the instance definition to use the default widget of this field type
    // instead of the hidden widget.
    // @todo Clean this up since we don't have $this->instance['widget'] anymore.
    //   see https://drupal.org/node/2028759
    if ($this->instance['widget']['type'] == 'hidden') {
      $field_type = field_info_field_types($field['type']);
      $default_widget = $this->widgetManager->getDefinition($field_type['default_widget']);

      $this->instance['widget'] = array(
        'type' => $default_widget['id'],
        'settings' => $default_widget['settings'],
        'weight' => 0,
      );
    }

    // Insert the widget. Since we do not use the "official" instance definition,
    // the whole flow cannot use field_invoke_method().
    $items = $entity->getNGEntity()->{$this->instance->getField()->id};
    if (!empty($this->instance['default_value'])) {
      $items->setValue((array) $this->instance['default_value']);
    }
    $element += $entity_form_display->getRenderer($this->instance->getField()->id)->form($entity, Language::LANGCODE_NOT_SPECIFIED, $items, $element, $form_state);

    return $element;
  }

  /**
   * Returns a FieldItem object for an entity.
   *
   * @todo Remove when all entity types extend EntityNG.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\field\Plugin\Type\FieldType\ConfigFieldItemInterface
   *   The field item object.
   */
  protected function getFieldItem(EntityInterface $entity, $field_name) {
    if ($entity instanceof EntityNG) {
      $item = $entity->get($field_name)->offsetGet(0);
    }
    else {
      $definitions = \Drupal::entityManager()->getFieldDefinitions($entity->entityType(), $entity->bundle());
      $item = \Drupal::typedData()->create($definitions[$field_name], array(), $field_name, $entity)->offsetGet(0);
    }
    return $item;
  }

}
