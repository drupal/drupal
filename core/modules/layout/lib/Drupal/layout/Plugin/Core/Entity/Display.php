<?php

/**
 * @file
 * Definition of Drupal\layout\Plugin\Core\Entity\Display.
 */

namespace Drupal\layout\Plugin\Core\Entity;

use Drupal\layout\Config\DisplayBase;
use Drupal\layout\Config\BoundDisplayInterface;
use Drupal\layout\Config\UnboundDisplayInterface;
use Drupal\layout\Plugin\LayoutInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the display entity.
 *
 * @EntityType(
 *   id = "display",
 *   label = @Translation("Display"),
 *   module = "layout",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController"
 *   },
 *   config_prefix = "display.bound",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Display extends DisplayBase implements BoundDisplayInterface {

  /**
   * A two-level array expressing block ordering within regions.
   *
   * The outer array is associative, keyed on region name. Each inner array is
   * indexed, with the config address of a block as values and sorted according
   * to order in which those blocks should appear in that region.
   *
   * This property is not stored statically in config, but is derived at runtime
   * by DisplayBase::sortBlocks(). It is not stored statically because that
   * would make using weights for ordering more difficult, and weights make
   * external mass manipulation of displays much easier.
   *
   * @var array
   */
  protected $blocksInRegions;

  /**
   * The layout instance being used to serve this page.
   *
   * @var \Drupal\layout\Plugin\LayoutInterface
   */
  protected $layoutInstance;

  /**
   * The name of the layout plugin to use.
   *
   * @var string
   */
  public $layout;

  /**
   * The settings with which to instantiate the layout plugin.
   *
   * @var array
   */
  public $layoutSettings = array();

  /**
   * Implements BoundDisplayInterface::getSortedBlocksByRegion().
   *
   * @throws \Exception
   */
  public function getSortedBlocksByRegion($region) {
    if ($this->blocksInRegions === NULL) {
      $this->sortBlocks();
    }

    if (!isset($this->blocksInRegions[$region])) {
      throw new \Exception(sprintf("Region %region does not exist in layout %layout", array('%region' => $region, '%layout' => $this->getLayoutInstance()->name)), E_RECOVERABLE_ERROR);
    }

    return $this->blocksInRegions[$region];
  }

  /**
   * Implements BoundDisplayInterface::getAllSortedBlocks().
   */
  public function getAllSortedBlocks() {
    if ($this->blocksInRegions === NULL) {
      $this->sortBlocks();
    }

    return $this->blocksInRegions;
  }

  /**
   * Transform the stored blockConfig into a sorted, region-oriented array.
   */
  protected function sortBlocks() {
    $layout_instance = $this->getLayoutInstance();
    if ($this->layout !== $layout_instance->getPluginId()) {
      $block_config = $this->mapBlocksToLayout($layout_instance);
    }
    else {
      $block_config = $this->blockInfo;
    }

    $this->blocksInRegions = array();

    $regions = array_fill_keys(array_keys($layout_instance->getRegions()), array());
    foreach ($block_config as $config_name => $info) {
      $regions[$info['region']][$config_name] = $info;
    }

    foreach ($regions as $region_name => &$blocks) {
      uasort($blocks, 'drupal_sort_weight');
      $this->blocksInRegions[$region_name] = array_keys($blocks);
    }
  }

  /**
   * Implements BoundDisplayInterface::remapToLayout().
   */
  public function remapToLayout(LayoutInterface $layout) {
    $this->blockInfo = $this->mapBlocksToLayout($layout);
    $this->setLayout($layout->getPluginId());
  }

  /**
   * Set the contained layout plugin.
   *
   * @param string $plugin_id
   *   The plugin id of the desired layout plugin.
   */
  public function setLayout($plugin_id) {
    // @todo verification?
    $this->layout = $plugin_id;
    $this->layoutInstance = NULL;
    $this->blocksInRegions = NULL;
  }

  /**
   * Implements BoundDisplayInterface::generateUnboundDisplay().
   *
   * @throws \Exception
   */
  public function generateUnboundDisplay($id, $entity_type = 'unbound_display') {
    $block_info = $this->getAllBlockInfo();
    foreach ($block_info as &$info) {
      unset($info['region']);
    }

    $values = array(
      'blockInfo' => $block_info,
      'id' => $id,
    );

    $entity = entity_create($entity_type, $values);
    if (!$entity instanceof UnboundDisplayInterface) {
      throw new \Exception(sprintf('Attempted to create an unbound display using an invalid entity type.'), E_RECOVERABLE_ERROR);
    }

    return $entity;
  }

  /**
   * Returns the instantiated layout object.
   *
   * @throws \Exception
   */
  public function getLayoutInstance() {
    if ($this->layoutInstance === NULL) {
      if (empty($this->layout)) {
        throw new \Exception(sprintf('Display "%id" had no layout plugin attached.', array('%id' => $this->id())), E_RECOVERABLE_ERROR);
      }

      $this->layoutInstance = \Drupal::service('plugin.manager.layout')->createInstance($this->layout, $this->layoutSettings);
      // @todo add handling for remapping if the layout could not be found
    }

    return $this->layoutInstance;
  }
}
