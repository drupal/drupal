<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldInstanceEditForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\ControllerInterface;
use Drupal\Core\Language\Language;
use Drupal\field\Plugin\Core\Entity\FieldInstance;
use Drupal\field\Plugin\Type\Widget\WidgetPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the field instance settings form.
 */
class FieldInstanceEditForm implements FormInterface, ControllerInterface {

  /**
   * The field instance being edited.
   *
   * @var \Drupal\field\Plugin\Core\Entity\FieldInstance
   */
  protected $instance;

  /**
   * The field widget plugin manager.
   *
   * @var \Drupal\field\Plugin\Type\Widget\WidgetPluginManager
   */
  protected $widgetManager;

  /**
   * Constructs a new FieldInstanceEditForm object.
   *
   * @param \Drupal\field\Plugin\Type\Widget\WidgetPluginManager $widget_manager
   *   The field widget plugin manager.
   */
  public function __construct(WidgetPluginManager $widget_manager) {
    $this->widgetManager = $widget_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'field_ui_field_instance_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, FieldInstance $field_instance = NULL) {
    $this->instance = $form_state['instance'] = $field_instance;

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

    // Add additional field instance settings from the field module.
    $additions = \Drupal::moduleHandler()->invoke($field['module'], 'field_instance_settings_form', array($field, $this->instance, $form_state));
    if (is_array($additions)) {
      $form['instance']['settings'] = $additions;
      $form['instance']['settings']['#weight'] = 10;
    }

    // Add widget settings for the widget type.
    $additions = $entity_form_display->getWidget($this->instance->getField()->id)->settingsForm($form, $form_state);
    $form['instance']['widget']['settings'] = $additions ?: array('#type' => 'value', '#value' => array());
    $form['instance']['widget']['#weight'] = 20;

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
      $items = array();
      $entity_form_display->getWidget($this->instance->getField()->id)->extractFormValues($entity, Language::LANGCODE_NOT_SPECIFIED, $items, $element, $form_state);

      // Grab the field definition from $form_state.
      $field_state = field_form_get_state($element['#parents'], $field_name, Language::LANGCODE_NOT_SPECIFIED, $form_state);
      $field = $field_state['field'];

      // Validate the value.
      $errors = array();
      $function = $field['module'] . '_field_validate';
      if (function_exists($function)) {
        $function(NULL, $field, $this->instance, Language::LANGCODE_NOT_SPECIFIED, $items, $errors);
      }

      // Report errors.
      if (isset($errors[$field_name][Language::LANGCODE_NOT_SPECIFIED])) {
        // Store reported errors in $form_state.
        $field_state['errors'] = $errors[$field_name][Language::LANGCODE_NOT_SPECIFIED];
        field_form_set_state($element['#parents'], $field_name, Language::LANGCODE_NOT_SPECIFIED, $form_state, $field_state);

        // Assign reported errors to the correct form element.
        $entity_form_display->getWidget($this->instance->getField()->id)->flagErrors($entity, Language::LANGCODE_NOT_SPECIFIED, $items, $element, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    form_load_include($form_state, 'inc', 'field_ui', 'field_ui.admin');
    $entity = $form['#entity'];
    $entity_form_display = $form['#entity_form_display'];

    // Handle the default value.
    if (isset($form['instance']['default_value_widget'])) {
      $element = $form['instance']['default_value_widget'];

      // Extract field values.
      $items = array();
      $entity_form_display->getWidget($this->instance->getField()->id)->extractFormValues($entity, Language::LANGCODE_NOT_SPECIFIED, $items, $element, $form_state);

      $this->instance['default_value'] = $items ? $items : NULL;
    }

    // Handle widget settings.
    $options = $entity_form_display->getComponent($this->instance->getField()->id);
    $options['settings'] = $form_state['values']['instance']['widget']['settings'];
    $entity_form_display->setComponent($this->instance->getField()->id, $options)->save();
    unset($form_state['values']['instance']['widget']);

    // Merge incoming values into the instance.
    foreach ($form_state['values']['instance'] as $key => $value) {
      $this->instance[$key] = $value;
    }
    $this->instance->save();

    drupal_set_message(t('Saved %label configuration.', array('%label' => $this->instance->label())));

    if ($this->instance['required'] && empty($this->instance['default_value']) && empty($this->instance['default_value_function']) && $this->instance['widget']['type'] == 'field_hidden') {
      drupal_set_message(t('Field %label is required and uses the "hidden" widget. You might want to configure a default value.', array('%label' => $this->instance['label'])), 'warning');
    }

    $form_state['redirect'] = field_ui_next_destination($this->instance['entity_type'], $this->instance['bundle']);
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
    if ($this->instance['widget']['type'] == 'field_hidden') {
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
    $items = array();
    if (!empty($this->instance['default_value'])) {
      $items = (array) $this->instance['default_value'];
    }
    $element += $entity_form_display->getWidget($this->instance->getField()->id)->form($entity, Language::LANGCODE_NOT_SPECIFIED, $items, $element, $form_state);

    return $element;
  }

}
