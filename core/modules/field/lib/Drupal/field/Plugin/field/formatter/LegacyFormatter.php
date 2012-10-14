<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\field\formatter\LegacyFormatter.
 */

namespace Drupal\field\Plugin\field\formatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;

/**
 * Plugin implementation for legacy formatters.
 *
 * This special implementation acts as a temporary BC layer for formatters that
 * have not been converted to Plugins, and bridges new methods to the old-style
 * hook_field_formatter_*() callbacks.
 *
 * This class is not discovered by the annotations reader, but referenced by
 * the Drupal\field\Plugin\Discovery\LegacyDiscoveryDecorator.
 *
 * @todo Remove once all core formatters have been converted.
 */
class LegacyFormatter extends FormatterBase {

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $definition = $this->getDefinition();
    $function = $definition['module'] . '_field_formatter_settings_form';

    // hook_field_formatter_settings_form() implementations read display
    // properties directly from $instance. Put the actual properties we use
    // here.
    $instance = clone $this->instance;
    $instance['display'][$this->viewMode] = array(
      'type' => $this->getPluginId(),
      'settings' => $this->getSettings(),
      'weight' => $this->weight,
      'label' => $this->label,
    );

    if (function_exists($function)) {
      return $function($this->field, $instance, $this->viewMode, $form, $form_state);
    }
    return array();
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsSummary().
   */
  public function settingsSummary() {
    $definition = $this->getDefinition();
    $function = $definition['module'] . '_field_formatter_settings_summary';

    // hook_field_formatter_settings_summary() implementations read display
    // properties directly from $instance. Put the actual properties we use
    // here.
    $instance = clone $this->instance;
    $instance['display'][$this->viewMode] = array(
      'type' => $this->getPluginId(),
      'settings' => $this->getSettings(),
      'weight' => $this->weight,
      'label' => $this->label,
    );

    if (function_exists($function)) {
      return $function($this->field, $instance, $this->viewMode);
    }
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::prepareView().
   */
  public function prepareView(array $entities, $langcode, array &$items) {
    $definition = $this->getDefinition();
    $function = $definition['module'] . '_field_formatter_prepare_view';
    if (function_exists($function)) {
      // Grab the entity type from the first entity.
      $entity = current($entities);
      $entity_type = $entity->entityType();

      // hook_field_formatter_prepare_view() received an array of display properties,
      // for each entity (the same hook could end up being called for different formatters,
      // since one hook implementation could provide several formatters).
      $display = array(
        'type' => $this->getPluginId(),
        'settings' => $this->getSettings(),
        'weight' => $this->weight,
        'label' => $this->label,
      );
      $displays = array();
      foreach ($entities as $entity) {
        $displays[$entity->id()] = $display;
      }

      $function($entity_type, $entities, $this->field, $this->instance, $langcode, $items, $displays);
    }
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $definition = $this->getDefinition();
    $function = $definition['module'] . '_field_formatter_view';
    if (function_exists($function)) {
      // hook_field_formatter_view() received an array of display properties,
      $display = array(
        'type' => $this->getPluginId(),
        'settings' => $this->getSettings(),
        'weight' => $this->weight,
        'label' => $this->label,
      );

      return $function($entity->entityType(), $entity, $this->field, $this->instance, $langcode, $items, $display);
    }
  }

}
