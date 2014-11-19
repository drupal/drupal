<?php

/**
 * @file
 * Contains \Drupal\block\Entity\BlockInterface.
 */

namespace Drupal\block;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a block entity.
 */
interface BlockInterface extends ConfigEntityInterface {

  /**
   * Indicates the block label (title) should be displayed to end users.
   */
  const BLOCK_LABEL_VISIBLE = 'visible';

  /**
   * Denotes that a block is not enabled in any region and should not be shown.
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
   * Get all available contexts.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface[]
   *   An array of set contexts, keyed by context name.
   */
  public function getContexts();

  /**
   * Set the contexts that are available for use within the block entity.
   *
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts to set on the block.
   *
   * @return $this
   */
  public function setContexts(array $contexts);

}
