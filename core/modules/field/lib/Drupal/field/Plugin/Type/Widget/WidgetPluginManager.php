<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\Type\Widget\WidgetPluginManager.
 */

namespace Drupal\field\Plugin\Type\Widget;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\field\Plugin\Type\LegacyDiscoveryDecorator;

/**
 * Plugin type manager for field widgets.
 */
class WidgetPluginManager extends PluginManagerBase {

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase:$defaults.
   */
  protected $defaults = array(
    'settings' => array(),
    'multiple_values' => FALSE,
    'default_value' => TRUE,
  );

  /**
   * The cache bin used for plugin definitions.
   *
   * @var string
   */
  protected $cache_bin = 'field';

  /**
   * The cache id used for plugin definitions.
   *
   * @var string
   */
  protected $cache_id = 'field_widget_types';

  /**
   * Constructs a WidgetPluginManager object.
   */
  public function __construct() {
    $this->baseDiscovery = new LegacyDiscoveryDecorator(new AnnotatedClassDiscovery('field', 'widget'));
    $this->discovery = new CacheDecorator($this->baseDiscovery, $this->cache_id, $this->cache_bin);

    $this->factory = new WidgetFactory($this);
  }

  /**
   * Clears cached definitions.
   *
   * @todo Remove when http://drupal.org/node/1764232 is fixed.
   */
  public function clearDefinitions() {
    // Clear 'static' data by creating a new object.
    $this->discovery = new CacheDecorator($this->baseDiscovery, $this->cache_id, $this->cache_bin);
    cache($this->cache_bin)->delete($this->cache_id);
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
