<?php

declare(strict_types=1);

namespace Drupal\media\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\media\MediaInterface;

/**
 * Provides a Media link target handler that prefers the standalone URL.
 *
 * @see \Drupal\media\Entity\MediaLinkTarget
 */
class MediaLinkTargetStandaloneWhenAvailable extends MediaLinkTarget {

  /**
   * {@inheritdoc}
   */
  public function getLinkTarget(EntityInterface $entity): GeneratedUrl {
    assert($entity instanceof MediaInterface);

    // Default to the standalone URL if it is enabled.
    // @see media_entity_type_alter()
    if (\Drupal::config('media.settings')->get('standalone_url')) {
      return $entity->toUrl('canonical')->toString(TRUE);
    }

    return parent::getLinkTarget($entity);
  }

}
