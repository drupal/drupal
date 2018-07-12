<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests the upgrade path for local actions/tasks being converted into blocks.
 *
 * @see https://www.drupal.org/node/507488
 *
 * @group system
 * @group legacy
 */
class LocalActionsAndTasksConvertedIntoBlocksUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.local-actions-tasks-into-blocks-507488.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = \Drupal::service('theme_handler');
    $theme_handler->refreshInfo();
  }

  /**
   * Tests that local actions/tasks are being converted into blocks.
   */
  public function testUpdateHookN() {
    $this->runUpdates();

    /** @var \Drupal\block\BlockInterface $block_storage */
    $block_storage = \Drupal::entityManager()->getStorage('block');
    /* @var \Drupal\block\BlockInterface[] $help_blocks */
    $help_blocks = $block_storage->loadByProperties(['theme' => 'bartik', 'region' => 'help']);

    $this->assertRaw('Because your site has custom theme(s) installed, we had to set local actions and tasks blocks into the content region. Please manually review the block configurations and remove the removed variables from your templates.');

    // Disable maintenance mode.
    // @todo Can be removed once maintenance mode is automatically turned off
    // after updates in https://www.drupal.org/node/2435135.
    \Drupal::state()->set('system.maintenance_mode', FALSE);

    // We finished updating so we can log in the user now.
    $this->drupalLogin($this->rootUser);

    $page = Node::create([
      'type' => 'page',
      'title' => 'Page node',
    ]);
    $page->save();

    // Ensures that blocks inside help region has been moved to content region.
    foreach ($help_blocks as $block) {
      $new_block = $block_storage->load($block->id());
      $this->assertEqual($new_block->getRegion(), 'content');
    }

    // Local tasks are visible on the node page.
    $this->drupalGet('node/' . $page->id());
    $this->assertText(t('Edit'));

    // Local actions are visible on the content listing page.
    $this->drupalGet('admin/content');
    $action_link = $this->cssSelect('.action-links');
    $this->assertTrue($action_link);

    $this->drupalGet('admin/structure/block/list/seven');

    /** @var \Drupal\Core\Config\StorageInterface $config_storage */
    $config_storage = \Drupal::service('config.storage');
    $this->assertTrue($config_storage->exists('block.block.test_theme_local_tasks'), 'Local task block has been created for the custom theme.');
    $this->assertTrue($config_storage->exists('block.block.test_theme_local_actions'), 'Local action block has been created for the custom theme.');
  }

}
