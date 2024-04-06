<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a Null Coalesce process plugin.
 *
 * Given a set of values provided to the plugin, the plugin will return the
 * first non-null value.
 *
 * Available configuration keys:
 * - source: The input array.
 * - default_value: (optional) The value to return if all values are NULL.
 *   if not provided, NULL is returned if all values are NULL.
 *
 * Example:
 * Given source keys of foo, bar, and baz:
 * @code
 * process_key:
 *   plugin: null_coalesce
 *   source:
 *     - foo
 *     - bar
 *     - baz
 * @endcode
 * This plugin will return the equivalent of `foo ?? bar ?? baz`
 */
#[MigrateProcess('null_coalesce')]
class NullCoalesce extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException("The input value should be an array.");
    }
    foreach ($value as $val) {
      if (NULL !== $val) {
        return $val;
      }
    }
    if (isset($this->configuration['default_value'])) {
      return $this->configuration['default_value'];
    }
    return NULL;
  }

}
