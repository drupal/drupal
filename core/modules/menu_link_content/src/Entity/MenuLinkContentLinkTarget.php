<?php

declare(strict_types=1);

namespace Drupal\menu_link_content\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityLinkTargetInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\menu_link_content\MenuLinkContentInterface;

/**
 * Provides a MenuLinkContent link target handler.
 *
 * MenuLinkContent entities are atypical because they describe a link to
 * elsewhere and don't have a canonical route to view them. So when linking to
 * such an entity, the link target must be their destination.
 *
 * @see \Drupal\menu_link_content\MenuLinkContentInterface::getUrlObject()
 */
class MenuLinkContentLinkTarget implements EntityLinkTargetInterface {

  /**
   * {@inheritdoc}
   */
  public function getLinkTarget(EntityInterface $entity): GeneratedUrl {
    assert($entity instanceof MenuLinkContentInterface);
    return $entity->getUrlObject()->toString(TRUE);
  }

}
