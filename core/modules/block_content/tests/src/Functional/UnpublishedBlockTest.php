<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests unpublishing of block_content entities.
 *
 * @group block_content
 */
class UnpublishedBlockTest extends BrowserTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_content'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests unpublishing of block_content entities.
   */
  public function testViewShowsCorrectStates(): void {
    $block_content = BlockContent::create([
      'info' => 'Test block',
      'type' => 'basic',
    ]);
    $block_content->save();

    $block = $this->placeBlock('block_content:' . $block_content->uuid());

    $this->drupalGet('<front>');
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->has('css', '#block-' . $block->id()));

    $block_content->setUnpublished();
    $block_content->save();

    $this->drupalGet('<front>');
    $page = $this->getSession()->getPage();
    $this->assertFalse($page->has('css', '#block-' . $block->id()));
  }

}
