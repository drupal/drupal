<?php

/**
 * @file
 * Contains \Drupal\block_content\Entity\BlockContentInterface.
 */

namespace Drupal\block_content;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a custom block entity.
 */
interface BlockContentInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Returns the block revision log message.
   *
   * @return string
   *   The revision log message.
   */
  public function getRevisionLog();

  /**
   * Sets the block description.
   *
   * @param string $info
   *   The block description.
   *
   * @return \Drupal\block_content\BlockContentInterface
   *   The class instance that this method is called on.
   */
  public function setInfo($info);

  /**
   * Sets the block revision log message.
   *
   * @param string $revision_log
   *   The revision log message.
   *
   * @return \Drupal\block_content\BlockContentInterface
   *   The class instance that this method is called on.
   */
  public function setRevisionLog($revision_log);

  /**
   * Sets the theme value.
   *
   * When creating a new block content block from the block library, the user is
   * redirected to the configure form for that block in the given theme. The
   * theme is stored against the block when the block content add form is shown.
   *
   * @param string $theme
   *   The theme name.
   *
   * @return \Drupal\block_content\BlockContentInterface
   *   The class instance that this method is called on.
   */
  public function setTheme($theme);

  /**
   * Gets the theme value.
   *
   * When creating a new block content block from the block library, the user is
   * redirected to the configure form for that block in the given theme. The
   * theme is stored against the block when the block content add form is shown.
   *
   * @return string
   *   The theme name.
   */
  public function getTheme();

  /**
   * Gets the configured instances of this custom block.
   *
   * @return array
   *   Array of Drupal\block\Core\Plugin\Entity\Block entities.
   */
  public function getInstances();

}
