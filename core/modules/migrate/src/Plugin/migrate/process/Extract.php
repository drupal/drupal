<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Variable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Extracts a value from an array.
 *
 * The extract process plugin is used to pull data from an input array, which
 * may have multiple levels. One use case is extracting data from field arrays
 * in previous versions of Drupal. For instance, in Drupal 7, a field array
 * would be indexed first by language, then by delta, then finally a key such as
 * 'value'.
 *
 * Available configuration keys:
 * - source: The input value - must be an array.
 * - index: The array of keys to access the value.
 * - default: (optional) A default value to assign to the destination if the
 *   key does not exist.
 *
 * Examples:
 *
 * @code
 * process:
 *   new_text_field:
 *     plugin: extract
 *     source: some_text_field
 *     index:
 *       - und
 *       - 0
 *       - value
 * @endcode
 *
 * The PHP equivalent of this would be:
 * @code
 * $destination['new_text_field'] = $source['some_text_field']['und'][0]['value'];
 * @endcode
 * If a default value is specified, it will be returned if the index does not
 * exist in the input array.
 *
 * @code
 * plugin: extract
 * source: some_text_field
 * default: 'Default title'
 * index:
 *   - title
 * @endcode
 *
 * If $source['some_text_field']['title'] doesn't exist, then the plugin will
 * return "Default title".
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess(
  id: "extract",
  handle_multiples: TRUE,
)]
class Extract extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException(sprintf("Input should be an array, instead it was of type '%s'", gettype($value)));
    }
    $new_value = NestedArray::getValue($value, $this->configuration['index'], $key_exists);

    if (!$key_exists) {
      if (array_key_exists('default', $this->configuration)) {
        $new_value = $this->configuration['default'];
      }
      else {
        throw new MigrateException(sprintf("Array index missing, extraction failed for '%s'. Consider adding a `default` key to the configuration.", Variable::export($value)));
      }
    }
    return $new_value;
  }

}
