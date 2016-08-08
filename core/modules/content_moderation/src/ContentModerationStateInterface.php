<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * An interface for Content moderation state entity.
 *
 * Content moderation state entities track the moderation state of other content
 * entities.
 */
interface ContentModerationStateInterface extends ContentEntityInterface, EntityOwnerInterface {

}
