<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Builds an array based on the key and value configuration.
 *
 * The array_build plugin builds a single associative array by extracting keys
 * and values from each array in the input value, which is expected to be an
 * array of arrays. The keys of the returned array will be determined by the
 * 'key' configuration option, and the values will be determined by the 'value'
 * option.
 *
 * Available configuration keys
 *   - key: The key used to lookup a value in the source arrays to be used as
 *     a key in the destination array.
 *   - value: The key used to lookup a value in the source arrays to be used as
 *     a value in the destination array.
 *
 * Example:
 *
 * Consider the migration of language negotiation by domain.
 * The source is an array of all the languages:
 *
 * @code
 * languages: Array
 * (
 *   [0] => Array
 *     (
 *       [language] => en
 * ...
 *       [domain] => http://example.com
 *     )
 *   [1] => Array
 *     (
 *       [language] => fr
 * ...
 *       [domain] => http://fr.example.com
 *     )
 * ...
 * @endcode
 *
 * The destination should be an array of all the domains keyed by their
 * language code:
 *
 * @code
 * domains: Array
 * (
 *   [en] => http://example.com
 *   [fr] => http://fr.example.com
 * ...
 * @endcode
 *
 * The array_build process plugin would be used like this:
 *
 * @code
 * process:
 *   domains:
 *     plugin: array_build
 *     key: language
 *     value: domain
 *     source: languages
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess(
  id: "array_build",
  handle_multiples: TRUE,
)]
class ArrayBuild extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $new_value = [];

    foreach ((array) $value as $old_value) {
      // Checks that $old_value is an array.
      if (!is_array($old_value)) {
        throw new MigrateException("The input should be an array of arrays");
      }

      // Checks that the key exists.
      if (!array_key_exists($this->configuration['key'], $old_value)) {
        throw new MigrateException("The key '" . $this->configuration['key'] . "' does not exist");
      }

      // Checks that the value exists.
      if (!array_key_exists($this->configuration['value'], $old_value)) {
        throw new MigrateException("The key '" . $this->configuration['value'] . "' does not exist");
      }

      $new_value[$old_value[$this->configuration['key']]] = $old_value[$this->configuration['value']];
    }

    return $new_value;
  }

}
