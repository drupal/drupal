<?php

/**
 * @file
 * Contains \Drupal\search\Tests\Migrate\d7\MigrateSearchSettingsTest.
 */

namespace Drupal\search\Tests\Migrate\d7;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Search variables to configuration.
 *
 * @group search
 */
class MigrateSearchSettingsTest extends MigrateDrupal7TestBase {

  public static $modules = ['search'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d7_search_settings');
  }

  /**
   * Tests the migration of Search's variables to configuration.
   */
  public function testSearchSettings() {
    $config = $this->config('search.settings');
    $this->assertIdentical(4, $config->get('index.minimum_word_size'));
    $this->assertTrue($config->get('index.overlap_cjk'));
    $this->assertIdentical(100, $config->get('index.cron_limit'));
    $this->assertIdentical(7, $config->get('and_or_limit'));
    $this->assertIdentical(25, $config->get('index.tag_weights.h1'));
    $this->assertIdentical(18, $config->get('index.tag_weights.h2'));
    $this->assertIdentical(15, $config->get('index.tag_weights.h3'));
    $this->assertIdentical(12, $config->get('index.tag_weights.h4'));
    $this->assertIdentical(9, $config->get('index.tag_weights.h5'));
    $this->assertIdentical(6, $config->get('index.tag_weights.h6'));
    $this->assertIdentical(3, $config->get('index.tag_weights.u'));
    $this->assertIdentical(3, $config->get('index.tag_weights.b'));
    $this->assertIdentical(3, $config->get('index.tag_weights.i'));
    $this->assertIdentical(3, $config->get('index.tag_weights.strong'));
    $this->assertIdentical(3, $config->get('index.tag_weights.em'));
    $this->assertIdentical(10, $config->get('index.tag_weights.a'));
    $this->assertTrue($config->get('logging'));
  }

}
