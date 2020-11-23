<?php

namespace Drupal\Tests\block_content\Functional;

use Drupal\block_content\Entity\BlockContent;

/**
 * Create a block and test block edit functionality.
 *
 * @group block_content
 */
class PageEditTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Checks block edit functionality.
   */
  public function testPageEdit() {
    $this->drupalLogin($this->adminUser);

    $title_key = 'info[0][value]';
    $body_key = 'body[0][value]';
    // Create block to edit.
    $edit = [];
    $edit['info[0][value]'] = mb_strtolower($this->randomMachineName(8));
    $edit[$body_key] = $this->randomMachineName(16);
    $this->drupalPostForm('block/add/basic', $edit, 'Save');

    // Check that the block exists in the database.
    $blocks = \Drupal::entityQuery('block_content')->condition('info', $edit['info[0][value]'])->execute();
    $block = BlockContent::load(reset($blocks));
    $this->assertNotEmpty($block, 'Custom block found in database.');

    // Load the edit page.
    $this->drupalGet('block/' . $block->id());
    $this->assertSession()->fieldValueEquals($title_key, $edit[$title_key]);
    $this->assertSession()->fieldValueEquals($body_key, $edit[$body_key]);

    // Edit the content of the block.
    $edit = [];
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    // Stay on the current page, without reloading.
    $this->submitForm($edit, 'Save');

    // Edit the same block, creating a new revision.
    $this->drupalGet("block/" . $block->id());
    $edit = [];
    $edit['info[0][value]'] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit['revision'] = TRUE;
    $this->submitForm($edit, 'Save');

    // Ensure that the block revision has been created.
    \Drupal::entityTypeManager()->getStorage('block_content')->resetCache([$block->id()]);
    $revised_block = BlockContent::load($block->id());
    $this->assertNotIdentical($block->getRevisionId(), $revised_block->getRevisionId(), 'A new revision has been created.');

    // Test deleting the block.
    $this->drupalGet("block/" . $revised_block->id());
    $this->clickLink(t('Delete'));
    $this->assertText('Are you sure you want to delete the custom block ' . $revised_block->label() . '?');
  }

}
