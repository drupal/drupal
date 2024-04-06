<?php

namespace Drupal\taxonomy\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts a Drupal 6 vocabulary ID to a target bundle array.
 */
#[MigrateProcess('target_bundle')]
class TargetBundle extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $target_bundle = [];
    $vid = $row->get('@_vid');
    $target_bundle[$vid] = $vid;
    return $target_bundle;
  }

}
