<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Field\PrepareCacheInterface.
 */

namespace Drupal\Core\Entity\Field;

/**
 * Interface for preparing field values before they enter cache.
 *
 * If a field type implements this interface, the prepareCache() method will be
 * invoked before field values get cached.
 */
interface PrepareCacheInterface {

  /**
   * Massages loaded field values before they enter the field cache.
   *
   * You should never load fieldable entities within this method, since this is
   * likely to cause infinite recursions. Use the prepareView() method instead.
   *
   * Also note that the method is not called on field values displayed during
   * entity preview. If the method adds elements that might be needed during
   * display, you might want to also use prepareView() to add those elements in
   * case they are not present.
   */
  public function prepareCache();

}
