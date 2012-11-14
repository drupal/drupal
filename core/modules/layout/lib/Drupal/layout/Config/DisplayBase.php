<?php

/**
 * @file
 * Definition of Drupal\layout\Config\DisplayBase.
 */

namespace Drupal\layout\Config;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\layout\Plugin\LayoutInterface;

/**
 * Base class for 'display' and 'unbound_display' configuration entities.
 *
 * @see \Drupal\layout\Config\DisplayInterface
 */
abstract class DisplayBase extends ConfigEntityBase implements DisplayInterface {

  /**
   * The ID (config name) identifying a specific display object.
   *
   * @var string
   */
  public $id;

  /**
   * The UUID identifying a specific display object.
   *
   * @var string
   */
  public $uuid;

  /**
   * Contains all block configuration.
   *
   * There are two levels to the configuration contained herein: display-level
   * block configuration, and then block instance configuration.
   *
   * Block instance configuration is stored in a separate config object. This
   * array is keyed by the config name that uniquely identifies each block
   * instance. At runtime, various object methods will retrieve this additional
   * config and return it to calling code.
   *
   * Display-level block configuration is data that determines the behavior of
   * a block *in this display*. The most important examples of this are the
   * region to which the block is assigned, and its weighting in that region.
   *
   * @code
   *    array(
   *      'block1-configkey' => array(
   *        'region' => 'content',
   *        // store the region type name here so that we can do type conversion w/out
   *        // needing to have access to the original layout plugin
   *        'region-type' => 'content',
   *        // increment by 100 so there is ALWAYS plenty of space for manual insertion
   *        'weight' => -100,
   *      ),
   *      'block2-configkey' => array(
   *        'region' => 'sidebar_first',
   *        'region-type' => 'aside',
   *        'weight' => -100,
   *      ),
   *      'block2-configkey' => array(
   *        'region' => 'sidebar_first',
   *        'region-type' => 'aside',
   *        'weight' => 0,
   *      ),
   *      'maincontent' => array(
   *        'region' => 'content',
   *        'region-type' => 'content',
   *        'weight' => -200,
   *      ),
   *    );
   * @endcode
   *
   * @var array
   */
  protected $blockInfo = array();

  /**
   * Implements DisplayInterface::getAllBlockInfo().
   */
  public function getAllBlockInfo() {
    return $this->blockInfo;
  }

  /**
   * Implements DisplayInterface::mapBlocksToLayout().
   *
   * @todo Decouple this implementation from this class, so that it could be
   *   more easily customized.
   */
  public function mapBlocksToLayout(LayoutInterface $layout) {
    $types = array();

    $layout_regions = $layout->getRegions();
    $layout_regions_indexed = array_keys($layout_regions);
    foreach ($layout_regions as $name => $info) {
      $types[$info['type']][] = $name;
    }

    $remapped_config = array();
    foreach ($this->blockInfo as $name => $info) {
      // First, if there's a direct region name match, use that.
      if (!empty($info['region']) && isset($layout_regions[$info['region']])) {
        // No need to do anything.
      }
      // Then, try to remap using region types.
      else if (!empty($types[$info['region-type']])) {
        $info['region'] = reset($types[$info['region-type']]);
      }
      // Finally, fall back to dumping everything in the layout's first region.
      else {
        if (!isset($first_region)) {
          reset($layout_regions);
          $first_region = key($layout_regions);
        }
        $info['region'] = $first_region;
      }

      $remapped_config[$name] = $info;
    }

    return $remapped_config;
  }

  /**
   * Implements DisplayInterface::getAllRegionTypes().
   */
  public function getAllRegionTypes() {
    $types = array();
    foreach ($this->blockInfo as $info) {
      $types[] = $info['region-type'];
    }
    return array_unique($types);
  }
}
