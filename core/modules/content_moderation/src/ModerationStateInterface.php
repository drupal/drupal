<?php

namespace Drupal\content_moderation;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Moderation state entities.
 */
interface ModerationStateInterface extends ConfigEntityInterface {

  /**
   * Determines if content updated to this state should be published.
   *
   * @return bool
   *   TRUE if content updated to this state should be published.
   */
  public function isPublishedState();

  /**
   * Determines if content updated to this state should be the default revision.
   *
   * @return bool
   *   TRUE if content in this state should be the default revision.
   */
  public function isDefaultRevisionState();

}
