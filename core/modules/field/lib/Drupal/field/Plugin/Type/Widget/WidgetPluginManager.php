<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\Type\Widget\WidgetPluginManager.
 */

namespace Drupal\field\Plugin\Type\Widget;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\field\Plugin\Type\Widget\WidgetLegacyDiscoveryDecorator;

/**
 * Plugin type manager for field widgets.
 */
class WidgetPluginManager extends PluginManagerBase {

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase:$defaults.
   */
  protected $defaults = array(
    'field_types' => array(),
    'settings' => array(),
    'multiple_values' => FALSE,
    'default_value' => TRUE,
  );

  /**
   * Constructs a WidgetPluginManager object.
   */
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('field', 'widget');
    $this->discovery = new WidgetLegacyDiscoveryDecorator($this->discovery);
    $this->discovery = new ProcessDecorator($this->discovery, array($this, 'processDefinition'));
    $this->discovery = new AlterDecorator($this->discovery, 'field_widget_info');
    $this->discovery = new CacheDecorator($this->discovery, 'field_widget_types',  'field');

    $this->factory = new WidgetFactory($this);
  }

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase::getInstance().
   */
  public function getInstance(array $options) {
    $instance = $options['instance'];
    $type = $options['type'];

    $definition = $this->getDefinition($type);
    $field = field_info_field($instance['field_name']);

    // Switch back to default widget if either:
    // - $type_info doesn't exist (the widget type is unknown),
    // - the field type is not allowed for the widget.
    if (!isset($definition['class']) || !in_array($field['type'], $definition['field_types'])) {
      // Grab the default widget for the field type.
      $field_type_definition = field_info_field_types($field['type']);
      $type = $field_type_definition['default_widget'];
    }

    $configuration = array(
      'instance' => $instance,
      'settings' => $options['settings'],
      'weight' => $options['weight'],
    );
    return $this->createInstance($type, $configuration);
  }

}
