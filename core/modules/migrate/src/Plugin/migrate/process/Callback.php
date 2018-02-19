<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Passes the source value to a callback.
 *
 * The callback process plugin allows simple processing of the value, such as
 * strtolower(). The callable takes the source value as the single mandatory
 * argument. No additional arguments can be passed to the callback.
 *
 * Available configuration keys:
 * - callable: The name of the callable method.
 *
 * Examples:
 *
 * @code
 * process:
 *   destination_field:
 *     plugin: callback
 *     callable: strtolower
 *     source: source_field
 * @endcode
 *
 * An example where the callable is a static method in a class:
 *
 * @code
 * process:
 *   destination_field:
 *     plugin: callback
 *     callable:
 *       - '\Drupal\Component\Utility\Unicode'
 *       - strtolower
 *     source: source_field
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "callback"
 * )
 */
class Callback extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    if (!isset($configuration['callable'])) {
      throw new \InvalidArgumentException('The "callable" must be set.');
    }
    elseif (!is_callable($configuration['callable'])) {
      throw new \InvalidArgumentException('The "callable" must be a valid function or method.');
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return call_user_func($this->configuration['callable'], $value);
  }

}
