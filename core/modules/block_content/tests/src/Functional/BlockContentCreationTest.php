<?php

namespace Drupal\Tests\block_content\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Database\Database;

/**
 * Create a block and test saving it.
 *
 * @group block_content
 */
class BlockContentCreationTest extends BlockContentTestBase {

  /**
   * Modules to enable.
   *
   * Enable dummy module that implements hook_block_insert() for exceptions and
   * field_ui to edit display settings.
   *
   * @var array
   */
  protected static $modules = ['block_content_test', 'dblog', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer blocks',
    'administer block_content display',
    'access block library',
    'administer block content',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Creates a "Basic block" block and verifies its consistency in the database.
   */
  public function testBlockContentCreation() {
    $this->drupalLogin($this->adminUser);

    // Create a block.
    $edit = [];
    $edit['info[0][value]'] = 'Test Block';
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalGet('block/add/basic');
    $this->submitForm($edit, 'Save');

    // Check that the Basic block has been created.
    $this->assertSession()->pageTextContains('basic ' . $edit['info[0][value]'] . ' has been created.');

    // Check that the view mode setting is hidden because only one exists.
    $this->assertSession()->fieldNotExists('settings[view_mode]');

    // Check that the block exists in the database.
    $blocks = \Drupal::entityTypeManager()
      ->getStorage('block_content')
      ->loadByProperties(['info' => $edit['info[0][value]']]);
    $block = reset($blocks);
    $this->assertNotEmpty($block, 'Content Block found in database.');
  }

  /**
   * Creates a "Basic page" block with multiple view modes.
   */
  public function testBlockContentCreationMultipleViewModes() {
    // Add a new view mode and verify if it is selected as expected.
    $this->drupalLogin($this->drupalCreateUser(['administer display modes']));
    $this->drupalGet('admin/structure/display-modes/view/add/block_content');
    $edit = [
      'id' => 'test_view_mode',
      'label' => 'Test View Mode',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the ' . $edit['label'] . ' view mode.');

    $this->drupalLogin($this->adminUser);

    // Create a block.
    $edit = [];
    $edit['info[0][value]'] = 'Test Block';
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalGet('block/add/basic');
    $this->submitForm($edit, 'Save');

    // Check that the Basic block has been created.
    $this->assertSession()->pageTextContains('basic ' . $edit['info[0][value]'] . ' has been created.');

    // Save our block permanently
    $this->submitForm(['region' => 'content'], 'Save block');

    // Set test_view_mode as a custom display to be available on the list.
    $this->drupalGet('admin/structure/block-content/manage/basic/display');
    $custom_view_mode = [
      'display_modes_custom[test_view_mode]' => 1,
    ];
    $this->submitForm($custom_view_mode, 'Save');

    // Go to the configure page and change the view mode.
    $this->drupalGet('admin/structure/block/manage/stark_testblock');

    // Test the available view mode options.
    // Verify that the default view mode is available.
    $this->assertSession()->optionExists('edit-settings-view-mode', 'default');
    // Verify that the test view mode is available.
    $this->assertSession()->optionExists('edit-settings-view-mode', 'test_view_mode');

    $view_mode['settings[view_mode]'] = 'test_view_mode';
    $this->submitForm($view_mode, 'Save block');

    // Check that the view mode setting is shown because more than one exists.
    $this->drupalGet('admin/structure/block/manage/stark_testblock');
    $this->assertSession()->fieldExists('settings[view_mode]');

    // Change the view mode.
    $view_mode['region'] = 'content';
    $view_mode['settings[view_mode]'] = 'test_view_mode';
    $this->submitForm($view_mode, 'Save block');

    // Go to the configure page and verify the view mode has changed.
    $this->drupalGet('admin/structure/block/manage/stark_testblock');
    $this->assertSession()->fieldValueEquals('settings[view_mode]', 'test_view_mode');

    // Check that the block exists in the database.
    $blocks = \Drupal::entityTypeManager()
      ->getStorage('block_content')
      ->loadByProperties(['info' => $edit['info[0][value]']]);
    $block = reset($blocks);
    $this->assertNotEmpty($block, 'Content Block found in database.');
  }

  /**
   * Create a default content block.
   *
   * Creates a content block from defaults and ensures that the 'basic block'
   * type is being used.
   */
  public function testDefaultBlockContentCreation() {
    $edit = [];
    $edit['info[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    // Don't pass the content block type in the URL so the default is forced.
    $this->drupalGet('block/add');
    $this->submitForm($edit, 'Save');

    // Check that the block has been created and that it is a basic block.
    $this->assertSession()->pageTextContains('basic ' . $edit['info[0][value]'] . ' has been created.');

    // Check that the block exists in the database.
    $blocks = \Drupal::entityTypeManager()
      ->getStorage('block_content')
      ->loadByProperties(['info' => $edit['info[0][value]']]);
    $block = reset($blocks);
    $this->assertNotEmpty($block, 'Default Content Block found in database.');
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
      // Expected exception; just continue testing.
    }

    $connection = Database::getConnection();

    // Check that the block does not exist in the database.
    $id = $connection->select('block_content_field_data', 'b')
      ->fields('b', ['id'])
      ->condition('info', 'fail_creation')
      ->execute()
      ->fetchField();
    $this->assertFalse($id);
  }

  /**
   * Tests deleting a block.
   */
  public function testBlockDelete() {
    // Create a block.
    $edit = [];
    $edit['info[0][value]'] = $this->randomMachineName(8);
    $body = $this->randomMachineName(16);
    $edit['body[0][value]'] = $body;
    $this->drupalGet('block/add/basic');
    $this->submitForm($edit, 'Save');

    // Place the block.
    $instance = [
      'id' => mb_strtolower($edit['info[0][value]']),
      'settings[label]' => $edit['info[0][value]'],
      'region' => 'sidebar_first',
    ];
    $block = BlockContent::load(1);
    $url = 'admin/structure/block/add/block_content:' . $block->uuid() . '/' . $this->config('system.theme')->get('default');
    $this->drupalGet($url);
    $this->submitForm($instance, 'Save block');

    $block = BlockContent::load(1);

    // Test getInstances method.
    $this->assertCount(1, $block->getInstances());

    // Navigate to home page.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains($body);

    // Delete the block.
    $this->drupalGet('admin/content/block/1/delete');
    $this->assertSession()->pageTextContains('This will also remove 1 placed block instance.');

    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('The content block ' . $edit['info[0][value]'] . ' has been deleted.');

    // Create another block and force the plugin cache to flush.
    $edit2 = [];
    $edit2['info[0][value]'] = $this->randomMachineName(8);
    $body2 = $this->randomMachineName(16);
    $edit2['body[0][value]'] = $body2;
    $this->drupalGet('block/add/basic');
    $this->submitForm($edit2, 'Save');

    $this->assertSession()->responseNotContains('Error message');

    // Create another block with no instances, and test we don't get a
    // confirmation message about deleting instances.
    $edit3 = [];
    $edit3['info[0][value]'] = $this->randomMachineName(8);
    $body = $this->randomMachineName(16);
    $edit3['body[0][value]'] = $body;
    $this->drupalGet('block/add/basic');
    $this->submitForm($edit3, 'Save');

    // Show the delete confirm form.
    $this->drupalGet('admin/content/block/3/delete');
    $this->assertSession()->pageTextNotContains('This will also remove');
  }

  /**
   * Tests placed content blocks create a dependency in the block placement.
   */
  public function testConfigDependencies() {
    $block = $this->createBlockContent();
    // Place the block.
    $block_placement_id = mb_strtolower($block->label());
    $instance = [
      'id' => $block_placement_id,
      'settings[label]' => $block->label(),
      'region' => 'sidebar_first',
    ];
    $block = BlockContent::load(1);
    $url = 'admin/structure/block/add/block_content:' . $block->uuid() . '/' . $this->config('system.theme')->get('default');
    $this->drupalGet($url);
    $this->submitForm($instance, 'Save block');

    $dependencies = \Drupal::service('config.manager')->findConfigEntityDependenciesAsEntities('content', [$block->getConfigDependencyName()]);
    $block_placement = reset($dependencies);
    $this->assertEquals($block_placement_id, $block_placement->id(), "The block placement config entity has a dependency on the block content entity.");
  }

}
