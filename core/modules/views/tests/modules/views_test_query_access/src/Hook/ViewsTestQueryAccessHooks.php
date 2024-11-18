<?php

declare(strict_types=1);

namespace Drupal\views_test_query_access\Hook;

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_test_query_access.
 */
class ViewsTestQueryAccessHooks {

  /**
   * Implements hook_query_TAG_alter() for the 'media_access' query tag.
   */
  #[Hook('query_media_access_alter')]
  public function queryMediaAccessAlter(AlterableInterface $query): void {
    _views_test_query_access_restrict_by_uuid($query);
  }

  /**
   * Implements hook_query_TAG_alter() for the 'block_content_access' query tag.
   */
  #[Hook('query_block_content_access_alter')]
  public function queryBlockContentAccessAlter(AlterableInterface $query): void {
    _views_test_query_access_restrict_by_uuid($query);
  }

}
