<?php

/**
 * @file
 * Contains \Drupal\link\LinkItemInterface.
 */

namespace Drupal\link;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines an interface for the link field item.
 */
interface LinkItemInterface extends FieldItemInterface {

  /**
   * Specifies whether the field supports only internal URLs.
   */
  const LINK_INTERNAL = 0x01;

  /**
   * Specifies whether the field supports only external URLs.
   */
  const LINK_EXTERNAL = 0x10;

  /**
   * Specifies whether the field supports both internal and external URLs.
   */
  const LINK_GENERIC = 0x11;

  /**
   * Determines if a link is external.
   *
   * @return bool
   *   TRUE if the link is external, FALSE otherwise.
   */
  public function isExternal();

  /**
   * Gets the URL object.
   *
   * @return \Drupal\Core\Url
   *   Returns a Url object.
   */
  public function getUrl();

}
