<?php

namespace Drupal\block_content;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface defining a block type entity.
 */
interface BlockContentTypeInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface {

  /**
   * Returns the description of the block type.
   *
   * @return string
   *   The description of the type of this block.
   */
  public function getDescription();

}
