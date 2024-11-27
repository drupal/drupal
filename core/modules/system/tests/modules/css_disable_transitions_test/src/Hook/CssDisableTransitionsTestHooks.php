<?php

declare(strict_types=1);

namespace Drupal\css_disable_transitions_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for css_disable_transitions_test.
 */
class CssDisableTransitionsTestHooks {

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    // Unconditionally attach an asset to the page.
    $attachments['#attached']['library'][] = 'css_disable_transitions_test/testing.css_disable_transitions_test';
  }

}
