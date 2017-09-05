<?php

namespace Drupal\Tests\block\Functional;

use Drupal\block\Entity\Block;
use Drupal\Component\Render\FormattableMarkup;

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
    $this->assertTrue(!empty($result), new FormattableMarkup('The block @id appears on the page', ['@id' => $block->id()]));
  }

  /**
   * Checks to see whether a block does not appears on the page.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block entity to find on the page.
   */
  protected function assertNoBlockAppears(Block $block) {
    $result = $this->findBlockInstance($block);
    $this->assertFalse(!empty($result), new FormattableMarkup('The block @id does not appear on the page', ['@id' => $block->id()]));
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
