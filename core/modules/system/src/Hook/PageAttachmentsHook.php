<?php

declare(strict_types=1);

namespace Drupal\system\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\BareHtmlPageRendererInterface;

/**
 * Implements hook_page_attachments() for the system module.
 */
final class PageAttachmentsHook {

  public function __construct(
    private readonly BareHtmlPageRendererInterface $bareHtmlPageRenderer,
  ) {}

  /**
   * Implements hook_page_attachments().
   *
   * @see \Drupal\Core\Theme\ThemePreprocess::preprocessMaintenancePage()
   * @see \Drupal\Core\EventSubscriber\ActiveLinkResponseFilter
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$page): void {
    $this->bareHtmlPageRenderer->systemPageAttachments($page);
  }

}
