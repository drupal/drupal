<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldWidgetTypeForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\ControllerInterface;
use Drupal\field\Plugin\Core\Entity\FieldInstance;
use Drupal\field\Plugin\Type\Widget\WidgetPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the widget selection form.
 */
class FieldWidgetTypeForm implements FormInterface, ControllerInterface {

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
   * Constructs a new FieldWidgetTypeForm object.
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
    return 'field_ui_widget_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, FieldInstance $field_instance = NULL) {
    $this->instance = $form_state['instance'] = $field_instance;
    form_load_include($form_state, 'inc', 'field_ui', 'field_ui.admin');
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
      '#options' => field_ui_widget_type_options($field['type']),
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
    catch (Exception $e) {
      drupal_set_message(t('There was a problem changing the widget for field %label.', array('%label' => $instance['label'])), 'error');
    }

    $form_state['redirect'] = field_ui_next_destination($entity_type, $bundle);
  }

}
