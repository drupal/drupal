<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemSiteTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade site variables to system.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemSiteTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_system_site');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6SystemSite.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of system (site) variables to system.site.yml.
   */
  public function testSystemSite() {
    $config = $this->config('system.site');
    $this->assertIdentical($config->get('name'), 'site_name');
    $this->assertIdentical($config->get('mail'), 'site_mail@example.com');
    $this->assertIdentical($config->get('slogan'), 'Migrate rocks');
    $this->assertIdentical($config->get('page.403'), 'user');
    $this->assertIdentical($config->get('page.404'), 'page-not-found');
    $this->assertIdentical($config->get('page.front'), 'node');
    $this->assertIdentical($config->get('admin_compact_mode'), FALSE);
  }

}
