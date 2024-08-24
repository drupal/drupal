<?php

declare(strict_types=1);

namespace Drupal\render_attached_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\render_attached_test\Controller\RenderAttachedTestController;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * A block we can use to test caching of #attached headers.
 *
 * @see \Drupal\system\Tests\Render\HtmlResponseAttachmentsTest
 */
#[Block(
  id: "attached_rendering_block",
  admin_label: new TranslatableMarkup("AttachedRenderingBlock")
)]
class AttachedRenderingBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Grab test attachment fixtures from
    // Drupal\render_attached_test\Controller\RenderAttachedTestController.
    $controller = new RenderAttachedTestController();
    $attached = BubbleableMetadata::mergeAttachments($controller->feed(), $controller->head());
    $attached = BubbleableMetadata::mergeAttachments($attached, $controller->header());
    $attached = BubbleableMetadata::mergeAttachments($attached, $controller->teapotHeaderStatus());

    // Return some arbitrary markup so the block doesn't disappear.
    $attached['#markup'] = 'Markup from attached_rendering_block.';
    return $attached;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
