<?php

namespace Drupal\block_content;

use Drupal\block_content\Access\RefinableDependentAccessInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;

/**
 * Provides an interface defining a content block entity.
 */
interface BlockContentInterface extends ContentEntityInterface, EntityChangedInterface, RevisionLogInterface, EntityPublishedInterface, RefinableDependentAccessInterface {

  /**
   * Sets the block description.
   *
   * @param string $info
   *   The block description.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setInfo($info);

  /**
   * Determines if the block is reusable or not.
   *
   * @return bool
   *   Returns TRUE if reusable and FALSE otherwise.
   */
  public function isReusable();

  /**
   * Sets the block to be reusable.
   *
   * @return $this
   */
  public function setReusable();

  /**
   * Sets the block to be non-reusable.
   *
   * @return $this
   */
  public function setNonReusable();

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
   * @return $this
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
   * Gets the configured instances of this content block.
   *
   * @return array
   *   Array of Drupal\block\Core\Plugin\Entity\Block entities.
   */
  public function getInstances();

}
