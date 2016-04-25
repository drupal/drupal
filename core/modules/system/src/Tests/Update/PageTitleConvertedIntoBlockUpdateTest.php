<?php

namespace Drupal\system\Tests\Update;

use Drupal\node\Entity\Node;

/**
 * Tests the upgrade path for page title being converted into a block.
 *
 * @see https://www.drupal.org/node/2476947
 *
 * @group system
 */
class PageTitleConvertedIntoBlockUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.page-title-into-block-2476947.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // @todo Remove in https://www.drupal.org/node/2568069.
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = \Drupal::service('theme_handler');
    $theme_handler->refreshInfo();
  }

  /**
   * Tests that page title is being converted into a block.
   */
  public function testUpdateHookN() {
    $this->runUpdates();

    /** @var \Drupal\block\BlockInterface $block_storage */
    $block_storage = \Drupal::entityManager()->getStorage('block');

    $this->assertRaw('Because your site has custom theme(s) installed, we have placed the page title block in the content region. Please manually review the block configuration and remove the page title variables from your page templates.');

    // Disable maintenance mode.
    // @todo Can be removed once maintenance mode is automatically turned off
    // after updates in https://www.drupal.org/node/2435135.
    \Drupal::state()->set('system.maintenance_mode', FALSE);

    // We finished updating so we can login the user now.
    $this->drupalLogin($this->rootUser);

    $page = Node::create([
      'type' => 'page',
      'title' => 'Page node',
    ]);
    $page->save();

    // Page title is visible on the home page.
    $this->drupalGet('/node');
    $this->assertRaw('page-title');

    // Page title is visible on a node page.
    $this->drupalGet('node/' . $page->id());
    $this->assertRaw('page-title');

    $this->drupalGet('admin/structure/block/list/bartik');

    /** @var \Drupal\Core\Config\StorageInterface $config_storage */
    $config_storage = \Drupal::service('config.storage');
    $this->assertTrue($config_storage->exists('block.block.test_theme_page_title'), 'Page title block has been created for the custom theme.');
  }

}
