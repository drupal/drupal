<?php

namespace Drupal\Tests\block_content\Functional\Views;

/**
 * Tests the block_content integration into views.
 *
 * @group block_content
 */
class BlockContentIntegrationTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_block_content_view'];

  /**
   * Tests basic block_content view with a block_content_type argument.
   */
  public function testBlockContentViewTypeArgument() {
    // Create two content types with three block_contents each.
    $types = [];
    $all_ids = [];
    $block_contents = [];
    for ($i = 0; $i < 2; $i++) {
      $type = $this->createBlockContentType();
      $types[] = $type;

      for ($j = 0; $j < 5; $j++) {
        // Ensure the right order of the block_contents.
        $block_content = $this->createBlockContent(['type' => $type->id()]);
        $block_contents[$type->id()][$block_content->id()] = $block_content;
        $all_ids[] = $block_content->id();
      }
    }

    $this->drupalGet('test-block_content-view');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalGet('test-block_content-view/all');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertIds($all_ids);
    /** @var \Drupal\block_content\Entity\BlockContentType[] $types*/
    foreach ($types as $type) {
      $this->drupalGet("test-block_content-view/{$type->id()}");
      $this->assertIds(array_keys($block_contents[$type->id()]));
    }
  }

  /**
   * Ensures that a list of block_contents appear on the page.
   *
   * @param array $expected_ids
   *   An array of block_content IDs.
   */
  protected function assertIds(array $expected_ids = []) {
    $result = $this->xpath('//span[@class="field-content"]');
    $ids = [];
    foreach ($result as $element) {
      $ids[] = $element->getText();
    }
    $this->assertEquals($expected_ids, $ids);
  }

}
