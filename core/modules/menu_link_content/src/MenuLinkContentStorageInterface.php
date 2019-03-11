<?php

namespace Drupal\menu_link_content;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for menu_link_content entity storage classes.
 */
interface MenuLinkContentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of menu link IDs with pending revisions.
   *
   * @return int[]
   *   An array of menu link IDs which have pending revisions, keyed by their
   *   revision IDs.
   *
   * @internal
   */
  public function getMenuLinkIdsWithPendingRevisions();

}
