<?php

/**
 * @file
 * Contains \Drupal\custom_block\Tests\PageEditTest.
 */

namespace Drupal\custom_block\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests the block edit functionality.
 */
class PageEditTest extends CustomBlockTestBase {

  /**
   * Declares test information.
   */
  public static function getInfo() {
    return array(
      'name' => 'Custom Block edit',
      'description' => 'Create a block and test block edit functionality.',
      'group' => 'Custom Block',
    );
  }

  /**
   * Checks block edit functionality.
   */
  public function testPageEdit() {
    $this->drupalLogin($this->adminUser);

    $title_key = 'info';
    $body_key = 'body[0][value]';
    // Create block to edit.
    $edit = array();
    $edit['info'] = drupal_strtolower($this->randomName(8));
    $edit[$body_key] = $this->randomName(16);
    $this->drupalPostForm('block/add/basic', $edit, t('Save'));

    // Check that the block exists in the database.
    $blocks = \Drupal::entityQuery('custom_block')->condition('info', $edit['info'])->execute();
    $block = entity_load('custom_block', reset($blocks));
    $this->assertTrue($block, 'Custom block found in database.');

    // Load the edit page.
    $this->drupalGet('block/' . $block->id());
    $this->assertFieldByName($title_key, $edit[$title_key], 'Title field displayed.');
    $this->assertFieldByName($body_key, $edit[$body_key], 'Body field displayed.');

    // Edit the content of the block.
    $edit = array();
    $edit[$title_key] = $this->randomName(8);
    $edit[$body_key] = $this->randomName(16);
    // Stay on the current page, without reloading.
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Edit the same block, creating a new revision.
    $this->drupalGet("block/" . $block->id());
    $edit = array();
    $edit['info'] = $this->randomName(8);
    $edit[$body_key] = $this->randomName(16);
    $edit['revision'] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Ensure that the block revision has been created.
    $revised_block = entity_load('custom_block', $block->id(), TRUE);
    $this->assertNotIdentical($block->getRevisionId(), $revised_block->getRevisionId(), 'A new revision has been created.');

    // Test deleting the block.
    $this->drupalGet("block/" . $revised_block->id());
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertText(format_string('Are you sure you want to delete !label?', array('!label' => $revised_block->label())));
  }

}
