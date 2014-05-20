<?php

/**
 * @file
 * Contains \Drupal\custom_block\Tests\CustomBlockSaveTest.
 */

namespace Drupal\custom_block\Tests;

use Drupal\Core\Language\Language;
use Drupal\custom_block\Entity\CustomBlock;

/**
 * Tests block save related functionality.
 */
class CustomBlockSaveTest extends CustomBlockTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('custom_block_test');

  /**
   * Declares test information.
   */
  public static function getInfo() {
    return array(
      'name' => 'Custom Block save',
      'description' => 'Test $custom_block->save() for saving content.',
      'group' => 'Custom Block',
    );
  }

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Checks whether custom block IDs are saved properly during an import.
   */
  public function testImport() {
    // Custom block ID must be a number that is not in the database.
    $max_id = db_query('SELECT MAX(id) FROM {custom_block}')->fetchField();
    $test_id = $max_id + mt_rand(1000, 1000000);
    $info = $this->randomName(8);
    $block_array = array(
      'info' => $info,
      'body' => array('value' => $this->randomName(32)),
      'type' => 'basic',
      'id' => $test_id
    );
    $block = entity_create('custom_block', $block_array);
    $block->enforceIsNew(TRUE);
    $block->save();

    // Verify that block_submit did not wipe the provided id.
    $this->assertEqual($block->id(), $test_id, 'Block imported using provide id');

    // Test the import saved.
    $block_by_id = CustomBlock::load($test_id);
    $this->assertTrue($block_by_id, 'Custom block load by block ID.');
    $this->assertIdentical($block_by_id->body->value, $block_array['body']['value']);
  }

  /**
   * Tests determing changes in hook_block_presave().
   *
   * Verifies the static block load cache is cleared upon save.
   */
  public function testDeterminingChanges() {
    // Initial creation.
    $block = $this->createCustomBlock('test_changes');
    $this->assertEqual($block->getChangedTime(), REQUEST_TIME, 'Creating a block sets default "changed" timestamp.');

    // Update the block without applying changes.
    $block->save();
    $this->assertEqual($block->label(), 'test_changes', 'No changes have been determined.');

    // Apply changes.
    $block->setInfo('updated');
    $block->save();

    // The hook implementations custom_block_test_custom_block_presave() and
    // custom_block_test_custom_block_update() determine changes and change the
    // title as well as programatically set the 'changed' timestamp.
    $this->assertEqual($block->label(), 'updated_presave_update', 'Changes have been determined.');
    $this->assertEqual($block->getChangedTime(), 979534800, 'Saving a custom block uses "changed" timestamp set in presave hook.');

    // Test the static block load cache to be cleared.
    $block = CustomBlock::load($block->id());
    $this->assertEqual($block->label(), 'updated_presave', 'Static cache has been cleared.');
  }

  /**
   * Tests saving a block on block insert.
   *
   * This test ensures that a block has been fully saved when
   * hook_custom_block_insert() is invoked, so that the block can be saved again
   * in a hook implementation without errors.
   *
   * @see block_test_block_insert()
   */
  public function testCustomBlockSaveOnInsert() {
    // custom_block_test_custom_block_insert() triggers a save on insert if the
    // title equals 'new'.
    $block = $this->createCustomBlock('new');
    $this->assertEqual($block->label(), 'CustomBlock ' . $block->id(), 'Custom block saved on block insert.');
  }

}
