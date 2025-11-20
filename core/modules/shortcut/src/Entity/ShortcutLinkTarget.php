<?php

declare(strict_types=1);

namespace Drupal\shortcut\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityLinkTargetInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\shortcut\ShortcutInterface;

/**
 * Provides a Shortcut link target handler.
 *
 * Shortcut entities are atypical because they describe a link to elsewhere and
 * don't have a canonical route to view them. So when linking to such an entity,
 * the link target must be their destination.
 *
 * @see \Drupal\shortcut\ShortcutInterface::getUrl()
 */
class ShortcutLinkTarget implements EntityLinkTargetInterface {

  /**
   * {@inheritdoc}
   */
  public function getLinkTarget(EntityInterface $entity): GeneratedUrl {
    assert($entity instanceof ShortcutInterface);
    return $entity->getUrl()->toString(TRUE);
  }

}
