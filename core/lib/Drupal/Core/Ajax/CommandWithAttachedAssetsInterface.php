<?php

namespace Drupal\Core\Ajax;

/**
 * Interface for Ajax commands that render content and attach assets.
 *
 * All Ajax commands that render HTML should implement these methods
 * to be able to return attached assets to the calling AjaxResponse object.
 *
 * @ingroup ajax
 */
interface CommandWithAttachedAssetsInterface {

  /**
   * Gets the attached assets.
   *
   * @return \Drupal\Core\Asset\AttachedAssets|null
   *   The attached assets for this command.
   */
  public function getAttachedAssets();

}
