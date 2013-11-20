<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\CopyFromSource.
 */

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Row;

/**
 * This plugin copies from the source to the destination.
 *
 * @PluginId("get")
 */
class Get extends PluginBase implements MigrateProcessInterface {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    $source = $this->configuration['source'];
    $properties = is_string($source) ? array($source) : $source;
    $return = array();
    foreach ($properties as $property) {
      if (empty($property)) {
        $return[] = $value;
      }
      else {
        $is_source = TRUE;
        if ($property[0] == '@') {
          $property = preg_replace_callback('/^(@?)((?:@@)*)([^@]|$)/', function ($matches) use (&$is_source) {
            // If there are an odd number of @ in the beginning, it's a
            // destination.
            $is_source = empty($matches[1]);
            // Remove the possible escaping and do not lose the terminating
            // non-@ either.
            return str_replace('@@', '@', $matches[2]) . $matches[3];
          }, $property);
        }
        if ($is_source) {
          $return[] = $row->getSourceProperty($property);
        }
        else {
          $return[] = $row->getDestinationProperty($property);
        }
      }
    }
    return is_string($source) ? $return[0] : $return;
  }
}
