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

  /**
   * Find a block instance on the page.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block entity to find on the page.
   *
   * @return array
   *   The result from the xpath query.
   *
   * @deprecated in drupal:9.5.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3293310
   */
  protected function findBlockInstance(Block $block) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:9.5.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3293310', E_USER_DEPRECATED);
    return $this->xpath('//div[@id = :id]', [':id' => 'block-' . $block->id()]);
  }

}
