<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\SevenSecondaryLocalTasksConvertedIntoBlockUpdateTest.
 */

namespace Drupal\system\Tests\Update;

/**
 * Tests the upgrade path for converting seven secondary local tasks into a block.
 *
 * @see https://www.drupal.org/node/2569529
 *
 * @group system
 */
class SevenSecondaryLocalTasksConvertedIntoBlockUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.seven-secondary-local-tasks-block-2569529.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
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

    // Disable maintenance mode.
    // @todo Can be removed once maintenance mode is automatically turned off
    // after updates in https://www.drupal.org/node/2435135.
    \Drupal::state()->set('system.maintenance_mode', FALSE);

    // We finished updating so we can login the user now.
    $this->drupalLogin($this->rootUser);

    // Local actions are visible on the content listing page.
    $this->drupalGet('admin/structure/block');
    $action_link = $this->cssSelect('#secondary-tabs-title');
    $this->assertTrue($action_link);
  }

}
