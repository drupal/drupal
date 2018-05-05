<?php

namespace Drupal\Tests\block_content\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\simpletest\BlockCreationTrait;
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
  public static $modules = ['block_content'];

  /**
   * Tests unpublishing of block_content entities.
   */
  public function testViewShowsCorrectStates() {
    $block_content = BlockContent::create([
      'info' => 'Test block',
      'type' => 'basic',
    ]);
    $block_content->save();

    $this->placeBlock('block_content:' . $block_content->uuid());

    $this->drupalGet('<front>');
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->has('css', '.block-block-content' . $block_content->uuid()));

    $block_content->setUnpublished();
    $block_content->save();

    $this->drupalGet('<front>');
    $page = $this->getSession()->getPage();
    $this->assertFalse($page->has('css', '.block-block-content' . $block_content->uuid()));
  }

}
