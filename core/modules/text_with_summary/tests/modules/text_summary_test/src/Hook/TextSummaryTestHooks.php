<?php

declare(strict_types=1);

namespace Drupal\text_summary_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;

/**
 * Hook implementations for text_summary_test.
 */
class TextSummaryTestHooks {

  public function __construct(protected readonly StateInterface $state) {}

  /**
 * Implements hook_page_attachments_alter().
 */
  #[Hook('page_attachments_alter')]
  public function pageAttachmentsAlter(array &$attachments) : void {
    $attachments['#attached']['library'][] = 'text_summary_test/text-summary-override';
  }

}
