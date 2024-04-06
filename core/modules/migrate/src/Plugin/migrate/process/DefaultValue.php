<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Returns a given default value if the input is empty.
 *
 * The default_value process plugin provides the ability to set a fixed default
 * value. The plugin returns a default value if the input value is considered
 * empty (NULL, FALSE, 0, '0', an empty string, or an empty array). The strict
 * configuration key can be used to set the default only when the incoming
 * value is NULL.
 *
 * Available configuration keys:
 * - default_value: The fixed default value to apply.
 * - strict: (optional) Use strict value checking. Defaults to false.
 *   - FALSE: Apply default when input value is empty().
 *   - TRUE: Apply default when input value is NULL.
 *
 * Example:
 *
 * @code
 * process:
 *   uid:
 *     -
 *       plugin: migration_lookup
 *       migration: users
 *       source: author
 *       no_stub: true
 *     -
 *       plugin: default_value
 *       default_value: 44
 * @endcode
 *
 * This will look up the source value of author in the users migration and if
 * not found, set the destination property uid to 44.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess(
  id: "default_value",
  handle_multiples: TRUE,
)]
class DefaultValue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($this->configuration['strict'])) {
      return $value ?? $this->configuration['default_value'];
    }
    return $value ?: $this->configuration['default_value'];
  }

}
