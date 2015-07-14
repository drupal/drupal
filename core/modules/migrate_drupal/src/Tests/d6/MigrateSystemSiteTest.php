<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemSiteTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

/**
 * Upgrade site variables to system.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemSiteTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d6_system_site');
  }

  /**
   * Tests migration of system (site) variables to system.site.yml.
   */
  public function testSystemSite() {
    $config = $this->config('system.site');
    $this->assertIdentical('site_name', $config->get('name'));
    $this->assertIdentical('site_mail@example.com', $config->get('mail'));
    $this->assertIdentical('Migrate rocks', $config->get('slogan'));
    $this->assertIdentical('/user', $config->get('page.403'));
    $this->assertIdentical('/page-not-found', $config->get('page.404'));
    $this->assertIdentical('/node', $config->get('page.front'));
    $this->assertIdentical(FALSE, $config->get('admin_compact_mode'));
  }

}
