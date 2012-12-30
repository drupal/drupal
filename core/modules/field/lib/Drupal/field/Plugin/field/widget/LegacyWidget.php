<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\field\widget\LegacyWidget.
 */

namespace Drupal\field\Plugin\field\widget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation for legacy widgets.
 *
 * This special implementation acts as a temporary BC layer for widgets that
 * have not been converted to Plugins, and bridges new methods to the old-style
 * hook_field_widget_*() callbacks.
 *
 * This class is not discovered by the annotations reader, but referenced by
 * the Drupal\field\Plugin\Discovery\LegacyDiscoveryDecorator.
 *
 * @todo Remove once all core widgets have been converted.
 */
class LegacyWidget extends WidgetBase {

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $definition = $this->getDefinition();
    $function = $definition['module'] . '_field_widget_settings_form';
    if (function_exists($function)) {
      return $function($this->field, $this->instance);
    }
    return array();
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $definition = $this->getDefinition();
    $function = $definition['module'] . '_field_widget_form';

    if (function_exists($function)) {
      // hook_field_widget_form() implementations read widget properties directly
      // from $instance. Put the actual properties we use here, which might have
      // been altered by hook_field_widget_property().
      $instance = clone $this->instance;
      $instance['widget']['type'] = $this->getPluginId();
      $instance['widget']['settings'] = $this->getSettings();

      return $function($form, $form_state, $this->field, $instance, $langcode, $items, $delta, $element);
    }
    return array();
  }

  /**
   * Overrides Drupal\field\Plugin\Type\Widget\WidgetBase::flagErrors().
   *
   * In D7, hook_field_widget_error() was supposed to call form_error() itself,
   * whereas the new errorElement() method simply returns the element to flag.
   * So we override the flagError() method to be more similar to the previous
   * code in field_default_form_errors().
   */
  public function flagErrors(EntityInterface $entity, $langcode, array $items, array $form, array &$form_state) {
    $field_name = $this->field['field_name'];

    $field_state = field_form_get_state($form['#parents'], $field_name, $langcode, $form_state);

    if (!empty($field_state['errors'])) {
      // Locate the correct element in the form.
      $element = NestedArray::getValue($form_state['complete_form'], $field_state['array_parents']);
      // Only set errors if the element is accessible.
      if (!isset($element['#access']) || $element['#access']) {
        $definition = $this->getDefinition();
        $is_multiple = $definition['multiple_values'];
        $function = $definition['module'] . '_field_widget_error';
        $function_exists = function_exists($function);

        foreach ($field_state['errors'] as $delta => $delta_errors) {
          // For multiple single-value widgets, pass errors by delta.
          // For a multiple-value widget, pass all errors to the main widget.
          $error_element = $is_multiple ? $element : $element[$delta];
          foreach ($delta_errors as $error) {
            if ($function_exists) {
              $function($error_element, $error, $form, $form_state);
            }
            else {
              // Make sure that errors are reported (even incorrectly flagged) if
              // the widget module fails to implement hook_field_widget_error().
              form_error($error_element, $error['message']);
            }
          }
        }
        // Reinitialize the errors list for the next submit.
        $field_state['errors'] = array();
        field_form_set_state($form['#parents'], $field_name, $langcode, $form_state, $field_state);
      }
    }
  }

}
