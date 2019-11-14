<?php

namespace Drupal\block;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a block entity.
 */
interface BlockInterface extends ConfigEntityInterface {

  /**
   * Indicates the block label (title) should be displayed to end users.
   *
   * @deprecated in drupal:8.3.0 and is removed from drupal:9.0.0.
   *   Use \Drupal\Core\Block\BlockPluginInterface::BLOCK_LABEL_VISIBLE.
   *
   * @see https://www.drupal.org/node/2829775
   */
  const BLOCK_LABEL_VISIBLE = BlockPluginInterface::BLOCK_LABEL_VISIBLE;

  /**
   * Denotes that a block is not enabled in any region and should not be shown.
   *
   * @deprecated in drupal:8.?.? and is removed from drupal:9.0.0.
   */
  const BLOCK_REGION_NONE = -1;

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface
   *   The plugin instance for this block.
   */
  public function getPlugin();

  /**
   * Returns the plugin ID.
   *
   * @return string
   *   The plugin ID for this block.
   */
  public function getPluginId();

  /**
   * Returns the region this block is placed in.
   *
   * @return string
   *   The region this block is placed in.
   */
  public function getRegion();

  /**
   * Returns the theme ID.
   *
   * @return string
   *   The theme ID for this block instance.
   */
  public function getTheme();

  /**
   * Returns an array of visibility condition configurations.
   *
   * @return array
   *   An array of visibility condition configuration keyed by the condition ID.
   */
  public function getVisibility();

  /**
   * Gets conditions for this block.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]|\Drupal\Core\Condition\ConditionPluginCollection
   *   An array or collection of configured condition plugins.
   */
  public function getVisibilityConditions();

  /**
   * Gets a visibility condition plugin instance.
   *
   * @param string $instance_id
   *   The condition plugin instance ID.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   A condition plugin.
   */
  public function getVisibilityCondition($instance_id);

  /**
   * Sets the visibility condition configuration.
   *
   * @param string $instance_id
   *   The condition instance ID.
   * @param array $configuration
   *   The condition configuration.
   *
   * @return $this
   */
  public function setVisibilityConfig($instance_id, array $configuration);

  /**
   * Returns the weight of this block (used for sorting).
   *
   * @return int
   *   The block weight.
   */
  public function getWeight();

  /**
   * Sets the region this block is placed in.
   *
   * @param string $region
   *   The region to place this block in.
   *
   * @return $this
   */
  public function setRegion($region);

  /**
   * Sets the block weight.
   *
   * @param int $weight
   *   The desired weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Creates a duplicate of the block entity.
   *
   * @param string $new_id
   *   (optional) The new ID on the duplicate block.
   * @param string $new_theme
   *   (optional) The theme on the duplicate block.
   *
   * @return static
   *   A clone of $this with all identifiers unset, so saving it inserts a new
   *   entity into the storage system.
   */
  public function createDuplicateBlock($new_id = NULL, $new_theme = NULL);

}
