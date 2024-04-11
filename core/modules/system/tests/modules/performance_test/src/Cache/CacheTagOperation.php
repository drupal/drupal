<?php

declare(strict_types=1);

namespace Drupal\performance_test\Cache;

/**
 * The cache tag operations we are tracking as part of our performance data.
 *
 * @see \Drupal\Core\Cache\CacheTagsChecksumInterface
 * @see \Drupal\Core\Cache\CacheTagsInvalidatorInterface
 */
enum CacheTagOperation {
  case GetCurrentChecksum;
  case InvalidateTags;
  case IsValid;
}
