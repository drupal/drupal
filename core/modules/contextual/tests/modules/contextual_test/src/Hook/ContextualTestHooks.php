<?php

declare(strict_types=1);

namespace Drupal\contextual_test\Hook;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for contextual_test.
 */
class ContextualTestHooks {

  /**
   * Implements hook_block_view_alter().
   */
  #[Hook('block_view_alter')]
  public function blockViewAlter(array &$build, BlockPluginInterface $block): void {
    $build['#contextual_links']['contextual_test'] = ['route_parameters' => []];
  }

  /**
   * Implements hook_contextual_links_view_alter().
   *
   * @todo Apparently this too late to attach the library?
   * It won't work without contextual_test_page_attachments_alter()
   * Is that a problem? Should the contextual module itself do the attaching?
   */
  #[Hook('contextual_links_view_alter')]
  public function contextualLinksViewAlter(&$element, $items): void {
    if (isset($element['#links']['contextual-test-ajax'])) {
      $element['#attached']['library'][] = 'core/drupal.dialog.ajax';
    }
  }

  /**
   * Implements hook_page_attachments_alter().
   */
  #[Hook('page_attachments_alter')]
  public function pageAttachmentsAlter(array &$attachments): void {
    $attachments['#attached']['library'][] = 'core/drupal.dialog.ajax';
  }

}
