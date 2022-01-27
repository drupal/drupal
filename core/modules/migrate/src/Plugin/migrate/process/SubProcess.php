<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Runs an array of arrays through its own process pipeline.
 *
 * The sub_process plugin accepts an array of associative arrays and runs each
 * one through its own process pipeline, producing a newly keyed associative
 * array of transformed values.
 *
 * Available configuration keys:
 *   - process: the plugin(s) that will process each element of the source.
 *   - key: runs the process pipeline for the key to determine a new dynamic
 *     name. If the new dynamic name is NULL then the result of the sub_process
 *     pipeline is ignored.
 *   - include_source: (optional) If TRUE, all source plugin configuration and
 *     values will be copied into the sub-processed row in a new property named
 *     for the source_key configuration value (see below). Defaults to FALSE.
 *   - source_key: (optional) If include_source is TRUE, this
 *     is the name of the property of the sub-processed row which will contain
 *     the source configuration and values. Ignored if include_source is
 *     FALSE. Defaults to 'source' if no value is provided.
 *
 * Example 1:
 *
 * This example demonstrates how migration_lookup process plugin can be applied
 * on the following source data.
 * @code
 * source: Array
 * (
 *   [upload] => Array
 *     (
 *       [0] => Array
 *         (
 *           [fid] => 1
 *           [list] => 0
 *           [description] => "File number 1"
 *         )
 *       [1] => Array
 *         (
 *           [fid] => 2
 *           [list] => 1
 *           [description] => "File number 2"
 *         )
 *     )
 * )
 * ...
 * @endcode
 * The sub_process process plugin will take these arrays one at a time and run
 * its own process for each of them:
 * @code
 * process:
 *   upload:
 *     plugin: sub_process
 *     source: upload
 *     process:
 *       target_id:
 *         plugin: migration_lookup
 *         migration: d6_file
 *         source: fid
 *       display: list
 *       description: description
 * @endcode
 * In this case, each item in the upload array will be processed by the
 * sub_process process plugin. The target_id will be found by looking up the
 * destination value from a previous migration using the migration_lookup
 * process plugin. The display and description fields will be mapped directly.
 *
 * Example 2.
 *
 * Drupal 6 filter formats contain a list of filters belonging to that format
 * identified by a numeric delta. A delta of 1 indicates automatic linebreaks,
 * delta of 2 indicates the URL filter and so on. This example demonstrates how
 * static_map process plugin can be applied on the following source data.
 * @code
 * source: Array
 * (
 *   [format] => 1
 *   [name] => Filtered HTML
 * ...
 *   [filters] => Array
 *     (
 *       [0] => Array
 *         (
 *           [module] => filter
 *           [delta] => 2
 *           [weight] => 0
 *         )
 *       [1] => Array
 *         (
 *           [module] => filter
 *           [delta] => 0
 *           [weight] => 1
 *         )
 *    )
 * )
 * ...
 * @endcode
 * The sub_process will take these arrays one at a time and run its own process
 * for each of them:
 * @code
 * process:
 *   filters:
 *     plugin: sub_process
 *     source: filters
 *     process:
 *       id:
 *         plugin: static_map
 *         source:
 *           - module
 *           - delta
 *         map:
 *           filter:
 *             0: filter_html_escape
 *             1: filter_autop
 *             2: filter_url
 *             3: filter_htmlcorrector
 *             4: filter_html_escape
 *           php:
 *             0: php_code
 * @endcode
 * The example above means that we take each array element ([0], [1], etc.) from
 * the source filters field and apply the static_map plugin on it. Let's have a
 * closer look at the first array at index 0:
 * @code
 * Array
 * (
 *    [module] => filter
 *    [delta] => 2
 *    [weight] => 0
 * )
 * @endcode
 * The static_map process plugin results to value 'filter_url' for this input
 * based on the 'module' and 'delta' map.
 *
 * Example 3.
 *
 * Normally the array returned from sub_process will have its original keys. If
 * you need to change the key, it is possible for the returned array to be keyed
 * by one of the transformed values in the sub-array. For the same source data
 * used in the previous example, the migration below would result to keys
 * 'filter_2' and 'filter_0'. If the value for 'id' is NULL the result of the
 * sub_process pipeline is ignored.
 * @code
 * process:
 *   filters:
 *     plugin: sub_process
 *     source: filters
 *     key: "@id"
 *     process:
 *       id:
 *         plugin: concat
 *         source:
 *           - module
 *           - delta
 *         delimiter: _
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\migrate\process\MigrationLookup
 * @see \Drupal\migrate\Plugin\migrate\process\StaticMap
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "sub_process",
 *   handle_multiples = TRUE
 * )
 */
class SubProcess extends ProcessPluginBase {

  /**
   * SubProcess constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $configuration += [
      'include_source' => FALSE,
      'source_key' => 'source',
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $return = $source = [];

    if ($this->configuration['include_source']) {
      $key = $this->configuration['source_key'];
      $source[$key] = $row->getSource();
    }

    if (is_array($value) || $value instanceof \Traversable) {
      foreach ($value as $key => $new_value) {
        if (!is_array($new_value)) {
          throw new MigrateException(sprintf("Input array should hold elements of type array, instead element was of type '%s'", gettype($new_value)));
        }
        $new_row = new Row($new_value + $source);
        $migrate_executable->processRow($new_row, $this->configuration['process']);
        $destination = $new_row->getDestination();
        if (array_key_exists('key', $this->configuration)) {
          $key = $this->transformKey($key, $migrate_executable, $new_row);
        }
        // Do not save the result if the key is NULL. The configured process
        // pipeline used in transformKey() will return NULL if a
        // MigrateSkipProcessException is thrown.
        // @see \Drupal\filter\Plugin\migrate\process\FilterID
        if ($key !== NULL) {
          $return[$key] = $destination;
        }
      }
    }
    return $return;
  }

  /**
   * Runs the process pipeline for the key to determine its dynamic name.
   *
   * @param string|int $key
   *   The current key.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migrate executable helper class.
   * @param \Drupal\migrate\Row $row
   *   The current row after processing.
   *
   * @return mixed
   *   The transformed key.
   */
  protected function transformKey($key, MigrateExecutableInterface $migrate_executable, Row $row) {
    $process = ['key' => $this->configuration['key']];
    $migrate_executable->processRow($row, $process, $key);
    return $row->getDestinationProperty('key');
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
