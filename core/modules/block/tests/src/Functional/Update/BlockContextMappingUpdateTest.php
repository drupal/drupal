<?php

namespace Drupal\Tests\block\Functional\Update;

use Drupal\block\Entity\Block;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests the upgrade path for block context mapping renames.
 *
 * @see https://www.drupal.org/node/2354889
 *
 * @group Update
 */
class BlockContextMappingUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_test', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.block-context-manager-2354889.php',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.language-enabled.php',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.block-test-enabled.php',
    ];
  }

  /**
   * Tests that block context mapping is updated properly.
   */
  public function testUpdateHookN() {
    $this->runUpdates();
    $this->assertRaw('Encountered an unknown context mapping key coming probably from a contributed or custom module: One or more mappings could not be updated. Please manually review your visibility settings for the following blocks, which are disabled now:<ul><li>User login (Visibility: Baloney spam)</li></ul>');

    // Disable maintenance mode.
    \Drupal::state()->set('system.maintenance_mode', FALSE);

    // We finished updating so we can log in the user now.
    $this->drupalLogin($this->rootUser);

    // The block that we are testing has the following visibility rules:
    // - only visible on node pages
    // - only visible to authenticated users.
    $block_title = 'Test for 2354889';

    // Create two nodes, a page and an article.
    $page = Node::create([
      'type' => 'page',
      'title' => 'Page node',
    ]);
    $page->save();

    $article = Node::create([
      'type' => 'article',
      'title' => 'Article node',
    ]);
    $article->save();

    // Check that the block appears only on Page nodes for authenticated users.
    $this->drupalGet('node/' . $page->id());
    $this->assertRaw($block_title, 'Test block is visible on a Page node as an authenticated user.');

    $this->drupalGet('node/' . $article->id());
    $this->assertNoRaw($block_title, 'Test block is not visible on a Article node as an authenticated user.');

    $this->drupalLogout();

    // Check that the block does not appear on any page for anonymous users.
    $this->drupalGet('node/' . $page->id());
    $this->assertNoRaw($block_title, 'Test block is not visible on a Page node as an anonymous user.');

    $this->drupalGet('node/' . $article->id());
    $this->assertNoRaw($block_title, 'Test block is not visible on a Article node as an anonymous user.');

    // Ensure that all the context mappings got updated properly.
    $block = Block::load('testfor2354889');
    $visibility = $block->get('visibility');
    $this->assertEqual('@node.node_route_context:node', $visibility['node_type']['context_mapping']['node']);
    $this->assertEqual('@user.current_user_context:current_user', $visibility['user_role']['context_mapping']['user']);
    $this->assertEqual('@language.current_language_context:language_interface', $visibility['language']['context_mapping']['language']);

    // Check that a block with invalid context is being disabled and that it can
    // still be edited afterward.
    $disabled_block = Block::load('thirdtestfor2354889');
    $this->assertFalse($disabled_block->status(), 'Block with invalid context is disabled');

    $this->assertEqual(['thirdtestfor2354889' => ['missing_context_ids' => ['baloney_spam' => ['node_type']], 'status' => TRUE]], \Drupal::keyValue('update_backup')->get('block_update_8001'));

    $disabled_block_visibility = $disabled_block->get('visibility');
    $this->assertTrue(!isset($disabled_block_visibility['node_type']), 'The problematic visibility condition has been removed.');

    $admin_user = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/structure/block/manage/thirdtestfor2354889');
    $this->assertResponse('200');
  }

}
