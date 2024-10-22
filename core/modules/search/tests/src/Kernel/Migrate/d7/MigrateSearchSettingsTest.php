<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Search variables to configuration.
 *
 * @group search
 */
class MigrateSearchSettingsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['search'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d7_search_settings');
  }

  /**
   * Tests the migration of Search's variables to configuration.
   */
  public function testSearchSettings(): void {
    $config = $this->config('search.settings');
    $this->assertSame('node_search', $config->get('default_page'));
    $this->assertSame(4, $config->get('index.minimum_word_size'));
    $this->assertTrue($config->get('index.overlap_cjk'));
    $this->assertSame(100, $config->get('index.cron_limit'));
    $this->assertSame(7, $config->get('and_or_limit'));
    $this->assertSame(25, $config->get('index.tag_weights.h1'));
    $this->assertSame(18, $config->get('index.tag_weights.h2'));
    $this->assertSame(15, $config->get('index.tag_weights.h3'));
    $this->assertSame(12, $config->get('index.tag_weights.h4'));
    $this->assertSame(9, $config->get('index.tag_weights.h5'));
    $this->assertSame(6, $config->get('index.tag_weights.h6'));
    $this->assertSame(3, $config->get('index.tag_weights.u'));
    $this->assertSame(3, $config->get('index.tag_weights.b'));
    $this->assertSame(3, $config->get('index.tag_weights.i'));
    $this->assertSame(3, $config->get('index.tag_weights.strong'));
    $this->assertSame(3, $config->get('index.tag_weights.em'));
    $this->assertSame(10, $config->get('index.tag_weights.a'));
    $this->assertTrue($config->get('logging'));
  }

}
