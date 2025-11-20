<?php

declare(strict_types=1);

namespace Drupal\file\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityLinkTargetInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\file\FileInterface;

/**
 * Provides a File link target handler.
 *
 * File entities are atypical because they are not served by Drupal, but by the
 * web server.
 *
 * @see \Drupal\file\FileInterface::createFileUrl()
 * @see \Drupal\Core\File\FileUrlGeneratorInterface
 */
class FileLinkTarget implements EntityLinkTargetInterface {

  /**
   * {@inheritdoc}
   */
  public function getLinkTarget(EntityInterface $entity): GeneratedUrl {
    assert($entity instanceof FileInterface);
    $url = $entity->createFileUrl(TRUE);
    // The $url is a string, which provides no cacheability metadata.
    assert(is_string($url));
    return (new GeneratedUrl())
      ->setGeneratedUrl($url)
      // No path & route processing means permanent cacheability.
      ->setCacheMaxAge(Cache::PERMANENT);
  }

}
