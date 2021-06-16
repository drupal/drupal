<?php

namespace Drupal\content_moderation\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * An interface for Content moderation state entity.
 *
 * Content moderation state entities track the moderation state of other content
 * entities.
 *
 * @internal
 */
interface ContentModerationStateInterface extends ContentEntityInterface, EntityOwnerInterface {

}
