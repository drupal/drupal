<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Variable;
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
 * - map: An array (of 1 or more dimensions) that defines the mapping between
 *   source values and destination values.
 * - bypass: (optional) Whether the plugin should proceed when the source is not
 *   found in the map array, defaults to FALSE.
 *   - TRUE: Return the unmodified input value, or another default value, if one
 *     is specified.
 *   - FALSE: Throw a MigrateSkipRowException.
 * - default_value: (optional) The value to return if the source is not found in
 *   the map array.
 *
 * Examples:
 *
 * If the value of the source property 'foo' is 'from' then the value of the
 * destination property bar will be 'to'. Similarly 'this' becomes 'that'.
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
 * The static_map process plugin supports a list of source properties. This is
 * useful in module-delta to machine name conversions. In the example below,
 * value 'filter_url' is returned if the source property 'module' is 'filter'
 * and the source property 'delta' is '2'.
 * @code
 * process:
 *   id:
 *     plugin: static_map
 *     source:
 *       - module
 *       - delta
 *     map:
 *       filter:
 *         0: filter_html_escape
 *         1: filter_autop
 *         2: filter_url
 *         3: filter_htmlcorrector
 *         4: filter_html_escape
 *       php:
 *         0: php_code
 * @endcode
 *
 * When static_map is used to just rename a few values and leave the others
 * unchanged, a 'bypass: true' option can be used. See the example below. If the
 * value of the source property 'foo' is 'from', 'to' will be returned. If the
 * value of the source property 'foo' is 'another' (a value that is not in the
 * map), 'another' will be returned unchanged.
 * @code
 * process:
 *   bar:
 *     plugin: static_map
 *     source: foo
 *     map:
 *       from: to
 *       this: that
 *     bypass: TRUE
 * @endcode
 *
 * A default value can be defined for all values that are not included in the
 * map. See the example below. If the value of the source property 'foo' is
 * 'yet_another' (a value that is not in the map), 'bar' will be returned.
 * @code
 * process:
 *   bar:
 *     plugin: static_map
 *     source: foo
 *     map:
 *       from: to
 *       this: that
 *     default_value: bar
 * @endcode
 *
 * If your source data has boolean values as strings, you need to use single
 * quotes in the map. See the example below.
 * @code
 * process:
 *   bar:
 *     plugin: static_map
 *     source: foo
 *     map:
 *       'TRUE': to
 * @endcode
 *
 * A NULL can be mapped. If the value of the source property 'foo' is NULL then
 * the value of the destination property bar will be 'to'.
 *
 * @code
 * process:
 *   bar:
 *     plugin: static_map
 *     source: foo
 *     map:
 *       NULL: to
 * @endcode
 *
 * If your source data contains booleans, the boolean is treated as a numeric 0
 * or 1. If the value of the source property 'foo' is TRUE then the value of the
 * destination property bar will be 'bar'. And if the value of the source
 * property 'foo' is FALSE then the value of the destination property bar will
 * be 'bar'.
 *
 * @code
 * process:
 *   bar:
 *     plugin: static_map
 *     source: foo
 *     map:
 *       0: foo
 *       1: bar
 * @endcode
 *
 * Mapping from a string which contains a period is not supported. A custom
 * process plugin can be written to handle this kind of a transformation.
 * Another option which may be feasible in certain use cases is to first pass
 * the value through the machine_name process plugin.
 *
 * @see https://www.drupal.org/project/drupal/issues/2827897
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
        throw new MigrateSkipRowException(sprintf("No static mapping found for '%s' and no default value provided for destination '%s'.", Variable::export($value), $destination_property));
      }
      else {
        return $value;
      }
    }
    return $new_value;
  }

}
