<?php

/**
 * @file
 * Contains \Drupal\custom_block\Tests\CustomBlockCreationTest.
 */

namespace Drupal\custom_block\Tests;

use Drupal\Core\Database\Database;
use Drupal\Core\Language\Language;

/**
 * Tests creating and saving a block.
 */
class CustomBlockCreationTest extends CustomBlockTestBase {

  /**
   * Modules to enable.
   *
   * Enable dummy module that implements hook_block_insert() for exceptions.
   *
   * @var array
   */
  public static $modules = array('custom_block_test', 'dblog');

  /**
   * Declares test information.
   */
  public static function getInfo() {
    return array(
      'name' => 'Custom Block creation',
      'description' => 'Create a block and test saving it.',
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
   * Creates a "Basic page" block and verifies its consistency in the database.
   */
  public function testCustomBlockCreation() {
    // Create a block.
    $edit = array();
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $edit['info'] = $this->randomName(8);
    $edit["block_body[$langcode][0][value]"] = $this->randomName(16);
    $this->drupalPost('block/add/basic', $edit, t('Save'));

    // Check that the Basic block has been created.
    $this->assertRaw(format_string('!block %name has been created.', array(
      '!block' => 'Basic block',
      '%name' => $edit["info"]
    )), 'Basic block created.');

    // Check that the block exists in the database.
    $blocks = entity_load_multiple_by_properties('custom_block', array('info' => $edit['info']));
    $block = reset($blocks);
    $this->assertTrue($block, 'Custom Block found in database.');
  }

  /**
   * Verifies that a transaction rolls back the failed creation.
   */
  public function testFailedBlockCreation() {
    // Create a block.
    try {
      $this->createCustomBlock('fail_creation');
      $this->fail('Expected exception has not been thrown.');
    }
    catch (\Exception $e) {
      $this->pass('Expected exception has been thrown.');
    }

    if (Database::getConnection()->supportsTransactions()) {
      // Check that the block does not exist in the database.
      $id = db_select('custom_block', 'b')
        ->fields('b', array('id'))
        ->condition('info', 'fail_creation')
        ->execute()
        ->fetchField();
      $this->assertFalse($id, 'Transactions supported, and block not found in database.');
    }
    else {
      // Check that the block exists in the database.
      $id = db_select('custom_block', 'b')
        ->fields('b', array('id'))
        ->condition('info', 'fail_creation')
        ->execute()
        ->fetchField();
      $this->assertTrue($id, 'Transactions not supported, and block found in database.');

      // Check that the failed rollback was logged.
      $records = db_query("SELECT wid FROM {watchdog} WHERE message LIKE 'Explicit rollback failed%'")->fetchAll();
      $this->assertTrue(count($records) > 0, 'Transactions not supported, and rollback error logged to watchdog.');
    }
  }
}
