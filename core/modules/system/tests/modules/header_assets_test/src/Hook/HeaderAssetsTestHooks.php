<?php

declare(strict_types=1);

namespace Drupal\header_assets_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for header_assets_test.
 */
class HeaderAssetsTestHooks {

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    // Attach a header library so that its dependencies, which are not header
    // libraries themselves, are loaded in the header on every page.
    $attachments['#attached']['library'][] = 'header_assets_test/header';
  }

}
