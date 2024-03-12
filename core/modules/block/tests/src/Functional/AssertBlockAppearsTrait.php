<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Functional;

use Drupal\block\Entity\Block;

/**
 * Provides test assertions for testing block appearance.
 *
 * Can be used by test classes that extend \Drupal\Tests\BrowserTestBase.
 */
trait AssertBlockAppearsTrait {

  /**
   * Checks to see whether a block appears on the page.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block entity to find on the page.
   */
  protected function assertBlockAppears(Block $block) {
    $this->assertSession()->elementExists('xpath', "//div[@id = 'block-{$block->id()}']");
  }

  /**
   * Checks to see whether a block does not appears on the page.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block entity to find on the page.
   */
  protected function assertNoBlockAppears(Block $block) {
    $this->assertSession()->elementNotExists('xpath', "//div[@id = 'block-{$block->id()}']");
  }

}
