<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\Type\Formatter\FormatterPluginManager..
 */

namespace Drupal\field\Plugin\Type\Formatter;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\field\Plugin\Type\Formatter\FormatterLegacyDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\AlterDecorator;

/**
 * Plugin type manager for field formatters.
 */
class FormatterPluginManager extends PluginManagerBase {

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase:$defaults.
   */
  protected $defaults = array(
    'field_types' => array(),
    'settings' => array(),
  );

  /**
   * Constructs a FormatterPluginManager object.
   */
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('field', 'formatter');
    $this->discovery = new FormatterLegacyDiscoveryDecorator($this->discovery);
    $this->discovery = new AlterDecorator($this->discovery, 'field_formatter_info');
    $this->discovery = new CacheDecorator($this->discovery, 'field_formatter_types', 'field');

    $this->factory = new FormatterFactory($this);
  }

  /**
   * Overrides PluginManagerBase::getInstance().
   */
  public function getInstance(array $options) {
    $instance = $options['instance'];
    $type = $options['type'];

    $definition = $this->getDefinition($type);
    $field = field_info_field($instance['field_name']);

    // Switch back to default formatter if either:
    // - $type_info doesn't exist (the widget type is unknown),
    // - the field type is not allowed for the widget.
    if (!isset($definition['class']) || !in_array($field['type'], $definition['field_types'])) {
      // Grab the default widget for the field type.
      $field_type_definition = field_info_field_types($field['type']);
      $type = $field_type_definition['default_formatter'];
    }

    $configuration = array(
      'instance' => $instance,
      'settings' => $options['settings'],
      'weight' => $options['weight'],
      'label' => $options['label'],
      'view_mode' => $options['view_mode'],
    );
    return $this->createInstance($type, $configuration);
  }

}
