<?php

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
    $result = $this->findBlockInstance($block);
    $this->assertNotEmpty($result, sprintf('The block %s should appear on the page.', $block->id()));
  }

  /**
   * Checks to see whether a block does not appears on the page.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block entity to find on the page.
   */
  protected function assertNoBlockAppears(Block $block) {
    $result = $this->findBlockInstance($block);
    $this->assertEmpty($result, sprintf('The block %s should not appear on the page.', $block->id()));
  }

  /**
   * Find a block instance on the page.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block entity to find on the page.
   *
   * @return array
   *   The result from the xpath query.
   */
  protected function findBlockInstance(Block $block) {
    return $this->xpath('//div[@id = :id]', [':id' => 'block-' . $block->id()]);
  }

}
