<?php

declare(strict_types=1);

namespace Drupal\js_testing_ajax_request_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for js_testing_ajax_request_test.
 */
class JsTestingAjaxRequestTestHooks {

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    // Unconditionally attach an asset to the page.
    $attachments['#attached']['library'][] = 'js_testing_ajax_request_test/track_ajax_requests';
  }

}
