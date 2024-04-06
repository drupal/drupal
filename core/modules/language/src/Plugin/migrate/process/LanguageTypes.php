<?php

namespace Drupal\language\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Processes the array for the language types.
 */
#[MigrateProcess(
  id: "language_types",
  handle_multiples: TRUE,
)]
class LanguageTypes extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException('The input should be an array');
    }

    if (array_key_exists('language', $value)) {
      $value['language_interface'] = $value['language'];
      unset($value['language']);
    }

    if (!empty($this->configuration['filter_configurable'])) {
      $value = array_filter($value);
    }

    return array_keys($value);
  }

}
