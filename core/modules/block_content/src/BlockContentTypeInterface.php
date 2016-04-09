<?php

namespace Drupal\block_content;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a custom block type entity.
 */
interface BlockContentTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the description of the block type.
   *
   * @return string
   *   The description of the type of this block.
   */
  public function getDescription();

  /**
   * Returns whether a new revision should be created by default.
   *
   * @return bool
   *   TRUE if a new revision should be created by default.
   */
  public function shouldCreateNewRevision();

}
