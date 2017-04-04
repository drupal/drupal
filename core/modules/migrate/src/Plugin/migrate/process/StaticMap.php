<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Changes the source value based on a static lookup map.
 *
 * Maps the input value to another value using an associative array specified in
 * the configuration.
 *
 * Available configuration keys:
 * - source: The input value - either a scalar or an array.
 * - map: An array (of 1 or more dimensions) that identifies the mapping between
 *   source values and destination values.
 * - bypass: (optional) Whether the plugin should proceed when the source is not
 *   found in the map array. Defaults to FALSE.
 *   - TRUE: Return the unmodified input value, or another default value, if one
 *     is specified.
 *   - FALSE: Throw a MigrateSkipRowException.
 * - default_value: (optional) The value to return if the source is not found in
 *   the map array.
 *
 * Examples:
 *
 * @code
 * process:
 *   bar:
 *     plugin: static_map
 *     source: foo
 *     map:
 *       from: to
 *       this: that
 * @endcode
 *
 * If the value of the source property foo was "from" then the value of the
 * destination property bar will be "to". Similarly "this" becomes "that".
 * static_map can do a lot more than this: it supports a list of source
 * properties. This is super useful in module-delta to machine name conversions.
 *
 * @code
 * process:
 *   id:
 *     plugin: static_map
 *       source:
 *         - module
 *         - delta
 *        map:
 *          filter:
 *            0: filter_html_escape
 *            1: filter_autop
 *            2: filter_url
 *            3: filter_htmlcorrector
 *            4: filter_html_escape
 *          php:
 *            0: php_code
 * @endcode
 *
 * If the value of the source properties module and delta are "filter" and "2"
 * respectively, then the returned value will be "filter_url". By default, if a
 * value is not found in the map, an exception is thrown.
 *
 * When static_map is used to just rename a few things and leave the others, a
 * "bypass: true" option can be added. In this case, the source value is used
 * unchanged, e.g.:
 *
 * @code
 * process:
 *   bar:
 *     plugin: static_map
 *     source: foo
 *       map:
 *         from: to
 *         this: that
 *       bypass: TRUE
 * @endcode
 *
 * If the value of the source property "foo" is "from" then the returned value
 * will be "to", but if the value of "foo" is "another" (a value that is not in
 * the map) then the source value is used unchanged so the returned value will
 * be "from" because "bypass" is set to TRUE.
 *
 * @code
 * process:
 *   bar:
 *     plugin: static_map
 *     source: foo
 *       map:
 *         from: to
 *         this: that
 *       default_value: bar
 * @endcode
 *
 * If the value of the source property "foo" is "yet_another" (a value that is
 * not in the map) then the default_value is used so the returned value will
 * be "bar".
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "static_map"
 * )
 */
class StaticMap extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $new_value = $value;
    if (is_array($value)) {
      if (!$value) {
        throw new MigrateException('Can not lookup without a value.');
      }
    }
    else {
      $new_value = [$value];
    }
    $new_value = NestedArray::getValue($this->configuration['map'], $new_value, $key_exists);
    if (!$key_exists) {
      if (array_key_exists('default_value', $this->configuration)) {
        if (!empty($this->configuration['bypass'])) {
          throw new MigrateException('Setting both default_value and bypass is invalid.');
        }
        return $this->configuration['default_value'];
      }
      if (empty($this->configuration['bypass'])) {
        throw new MigrateSkipRowException();
      }
      else {
        return $value;
      }
    }
    return $new_value;
  }

}
