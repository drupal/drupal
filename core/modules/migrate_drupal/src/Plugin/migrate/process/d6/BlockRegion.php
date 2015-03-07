<?php
/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\process\d6\BlockRegion.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_block_region"
 * )
 */
class BlockRegion extends ProcessPluginBase {
  /**
   * {@inheritdoc}
   *
   * Set the destination block region, based on the source region and theme as
   * well as the current destination default theme.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($region, $source_theme, $destination_theme) = $value;

    // Theme is the same on both source and destination, we will assume they
    // have the same regions.
    if (strtolower($source_theme) == strtolower($destination_theme)) {
      return $region;
    }

    // If the source and destination theme are different, try to use the
    // mappings defined in the configuration.
    $region_map = $this->configuration['region_map'];
    if (isset($region_map[$region])) {
      return $region_map[$region];
    }

    // Oh well, we tried. Put the block in the main content region.
    return 'content';
  }

}
