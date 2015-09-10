<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\LocalActionsAndTasksConvertedIntoBlocksUpdateTest.
 */

namespace Drupal\system\Tests\Update;

use Drupal\node\Entity\Node;

/**
 * Tests the upgrade path for local actions/tasks being converted into blocks.
 *
 * @see https://www.drupal.org/node/507488
 *
 * @group system
 */
class SiteBrandingConvertedIntoBlockUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.site-branding-into-block-2005546.php',
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
   * Tests that site branding elements are being converted into blocks.
   */
  public function testUpdateHookN() {
    $this->runUpdates();

    /** @var \Drupal\block\BlockInterface $block_storage */
    $block_storage = \Drupal::entityManager()->getStorage('block');

    $this->assertRaw('Because your site has custom theme(s) installed, we had to set the branding block into the content region. Please manually review the block configuration and remove the site name, slogan, and logo variables from your templates.');

    // Disable maintenance mode.
    // @todo Can be removed once maintenance mode is automatically turned off
    // after updates in https://www.drupal.org/node/2435135.
    \Drupal::state()->set('system.maintenance_mode', FALSE);

    // We finished updating so we can login the user now.
    $this->drupalLogin($this->rootUser);

    // Site branding is visible on the home page.
    $this->drupalGet('/node');
    $this->assertRaw('site-branding__logo');
    $this->assertRaw('site-branding__name');
    $this->assertNoRaw('site-branding__slogan');

    $this->drupalGet('admin/structure/block/list/bartik');

    /** @var \Drupal\Core\Config\StorageInterface $config_storage */
    $config_storage = \Drupal::service('config.storage');
    $this->assertTrue($config_storage->exists('block.block.test_theme_branding'), 'Site branding block has been created for the custom theme.');
  }

}
