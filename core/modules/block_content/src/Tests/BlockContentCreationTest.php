<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\BlockContentCreationTest.
 */

namespace Drupal\block_content\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Database;
use Drupal\block_content\Entity\BlockContent;

/**
 * Create a block and test saving it.
 *
 * @group block_content
 */
class BlockContentCreationTest extends BlockContentTestBase {

  /**
   * Modules to enable.
   *
   * Enable dummy module that implements hook_block_insert() for exceptions.
   *
   * @var array
   */
  public static $modules = array('block_content_test', 'dblog', 'field_ui');

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
  public function testBlockContentCreation() {
    // Add a new view mode and verify if it is selected as expected.
    $this->drupalLogin($this->drupalCreateUser(array('administer display modes')));
    $this->drupalGet('admin/structure/display-modes/view/add/block_content');
    $edit = array(
      'id' => 'test_view_mode',
      'label' => 'Test View Mode',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('Saved the %label view mode.', array('%label' => $edit['label'])));

    $this->drupalLogin($this->adminUser);

    // Create a block.
    $edit = array();
    $edit['info[0][value]'] = 'Test Block';
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm('block/add/basic', $edit, t('Save'));

    // Check that the Basic block has been created.
    $this->assertRaw(format_string('!block %name has been created.', array(
      '!block' => 'Basic block',
      '%name' => $edit['info[0][value]']
    )), 'Basic block created.');

    // Change the view mode.
    $view_mode['settings[view_mode]'] = 'test_view_mode';
    $this->drupalPostForm(NULL, $view_mode, t('Save block'));

    // Go to the configure page and verify that the new view mode is correct.
    $this->drupalGet('admin/structure/block/manage/testblock');
    $this->assertFieldByXPath('//select[@name="settings[view_mode]"]/option[@selected="selected"]/@value', 'test_view_mode', 'View mode changed to Test View Mode');

    // Test the available view mode options.
    $this->assertOption('edit-settings-view-mode', 'default', 'The default view mode is available.');

    // Check that the block exists in the database.
    $blocks = entity_load_multiple_by_properties('block_content', array('info' => $edit['info[0][value]']));
    $block = reset($blocks);
    $this->assertTrue($block, 'Custom Block found in database.');

    // Check that attempting to create another block with the same value for
    // 'info' returns an error.
    $this->drupalPostForm('block/add/basic', $edit, t('Save'));

    // Check that the Basic block has been created.
    $this->assertRaw(format_string('A block with description %name already exists.', array(
      '%name' => $edit['info[0][value]']
    )));
    $this->assertResponse(200);
  }

  /**
   * Create a default custom block.
   *
   * Creates a custom block from defaults and ensures that the 'basic block'
   * type is being used.
   */
  public function testDefaultBlockContentCreation() {
    $edit = array();
    $edit['info[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    // Don't pass the custom block type in the url so the default is forced.
    $this->drupalPostForm('block/add', $edit, t('Save'));

    // Check that the block has been created and that it is a basic block.
    $this->assertRaw(format_string('!block %name has been created.', array(
      '!block' => 'Basic block',
      '%name' => $edit['info[0][value]'],
    )), 'Basic block created.');

    // Check that the block exists in the database.
    $blocks = entity_load_multiple_by_properties('block_content', array('info' => $edit['info[0][value]']));
    $block = reset($blocks);
    $this->assertTrue($block, 'Default Custom Block found in database.');
  }

  /**
   * Verifies that a transaction rolls back the failed creation.
   */
  public function testFailedBlockCreation() {
    // Create a block.
    try {
      $this->createBlockContent('fail_creation');
      $this->fail('Expected exception has not been thrown.');
    }
    catch (\Exception $e) {
      $this->pass('Expected exception has been thrown.');
    }

    if (Database::getConnection()->supportsTransactions()) {
      // Check that the block does not exist in the database.
      $id = db_select('block_content_field_data', 'b')
        ->fields('b', array('id'))
        ->condition('info', 'fail_creation')
        ->execute()
        ->fetchField();
      $this->assertFalse($id, 'Transactions supported, and block not found in database.');
    }
    else {
      // Check that the block exists in the database.
      $id = db_select('block_content_field_data', 'b')
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

  /**
   * Test deleting a block.
   */
  public function testBlockDelete() {
    // Create a block.
    $edit = array();
    $edit['info[0][value]'] = $this->randomMachineName(8);
    $body = $this->randomMachineName(16);
    $edit['body[0][value]'] = $body;
    $this->drupalPostForm('block/add/basic', $edit, t('Save'));

    // Place the block.
    $instance = array(
      'id' => drupal_strtolower($edit['info[0][value]']),
      'settings[label]' => $edit['info[0][value]'],
      'region' => 'sidebar_first',
    );
    $block = entity_load('block_content', 1);
    $url = 'admin/structure/block/add/block_content:' . $block->uuid() . '/' . \Drupal::config('system.theme')->get('default');
    $this->drupalPostForm($url, $instance, t('Save block'));

    $block = BlockContent::load(1);

    // Test getInstances method.
    $this->assertEqual(1, count($block->getInstances()));

    // Navigate to home page.
    $this->drupalGet('');
    $this->assertText($body);

    // Delete the block.
    $this->drupalGet('block/1/delete');
    $this->assertText(format_plural(1, 'This will also remove 1 placed block instance.', 'This will also remove @count placed block instance.'));

    $this->drupalPostForm(NULL, array(), 'Delete');
    $this->assertRaw(t('Custom block %name has been deleted.', array('%name' => $edit['info[0][value]'])));

    // Create another block and force the plugin cache to flush.
    $edit2 = array();
    $edit2['info[0][value]'] = $this->randomMachineName(8);
    $body2 = $this->randomMachineName(16);
    $edit2['body[0][value]'] = $body2;
    $this->drupalPostForm('block/add/basic', $edit2, t('Save'));

    $this->assertNoRaw('Error message');

    // Create another block with no instances, and test we don't get a
    // confirmation message about deleting instances.
    $edit3 = array();
    $edit3['info[0][value]'] = $this->randomMachineName(8);
    $body = $this->randomMachineName(16);
    $edit3['body[0][value]'] = $body;
    $this->drupalPostForm('block/add/basic', $edit3, t('Save'));

    // Show the delete confirm form.
    $this->drupalGet('block/3/delete');
    $this->assertNoText('This will also remove');
  }

  /**
   * Test that placed content blocks create a dependency in the block placement.
   */
  public function testConfigDependencies() {
    $block = $this->createBlockContent();
    // Place the block.
    $block_placement_id = Unicode::strtolower($block->label());
    $instance = array(
      'id' => $block_placement_id,
      'settings[label]' => $block->label(),
      'region' => 'sidebar_first',
    );
    $block = entity_load('block_content', 1);
    $url = 'admin/structure/block/add/block_content:' . $block->uuid() . '/' . \Drupal::config('system.theme')->get('default');
    $this->drupalPostForm($url, $instance, t('Save block'));

    $dependencies = \Drupal::service('config.manager')->findConfigEntityDependentsAsEntities('content', array($block->getConfigDependencyName()));
    $block_placement = reset($dependencies);
    $this->assertEqual($block_placement_id, $block_placement->id(), "The block placement config entity has a dependency on the block content entity.");
  }

}
