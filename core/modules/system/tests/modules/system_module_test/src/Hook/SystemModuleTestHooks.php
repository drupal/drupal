<?php

declare(strict_types=1);

namespace Drupal\system_module_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for system_module_test.
 */
class SystemModuleTestHooks {

  /**
   * Implements hook_page_attachments_alter().
   */
  #[Hook('page_attachments_alter')]
  public function pageAttachmentsAlter(&$page): void {
    // Remove the HTML5 mobile meta-tags.
    $meta_tags_to_remove = ['MobileOptimized', 'HandheldFriendly', 'viewport', 'ClearType'];
    foreach ($page['#attached']['html_head'] as $index => $parts) {
      if (in_array($parts[1], $meta_tags_to_remove)) {
        unset($page['#attached']['html_head'][$index]);
      }
    }
  }

}
