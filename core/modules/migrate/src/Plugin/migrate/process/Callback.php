<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Passes the source value to a callback.
 *
 * The callback process plugin allows simple processing of the value, such as
 * strtolower(). To pass more than one argument, pass an array as the source
 * and set the unpack_source option.
 *
 * Available configuration keys:
 * - callable: The name of the callable method.
 * - unpack_source: (optional) Whether to interpret the source as an array of
 *   arguments.
 *
 * Examples:
 *
 * @code
 * process:
 *   destination_field:
 *     plugin: callback
 *     callable: mb_strtolower
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
 *       - ucfirst
 *     source: source_field
 * @endcode
 *
 * An example where the callback accepts no arguments:
 *
 * @code
 * process:
 *   time:
 *     plugin: callback
 *     callable: time
 *     unpack_source: true
 *     source: [  ]
 * @endcode
 *
 * An example where the callback accepts more than one argument:
 *
 * @code
 * source:
 *   plugin: source_plugin_goes_here
 *   constants:
 *     slash: /
 * process:
 *   field_link_url:
 *     plugin: callback
 *     callable: rtrim
 *     unpack_source: true
 *     source:
 *       - url
 *       - constants/slash
 * @endcode
 *
 * This will remove the trailing '/', if any, from a URL.
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
    if (!empty($this->configuration['unpack_source'])) {
      if (!is_array($value)) {
        throw new MigrateException(sprintf("When 'unpack_source' is set, the source must be an array. Instead it was of type '%s'", gettype($value)));
      }
      return call_user_func($this->configuration['callable'], ...$value);
    }
    return call_user_func($this->configuration['callable'], $value);
  }

}
