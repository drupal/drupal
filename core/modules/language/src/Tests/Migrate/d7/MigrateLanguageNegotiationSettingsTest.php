<?php

/**
 * @file
 * Contains \Drupal\language\Tests\Migrate\d7\MigrateLanguageNegotiationSettingsTest.
 */

namespace Drupal\language\Tests\Migrate\d7;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of language negotiation variables.
 *
 * @group language
 */
class MigrateLanguageNegotiationSettingsTest extends MigrateDrupal7TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d7_language_negotiation_settings');
  }

  /**
   * Tests migration of language negotiation variables to language.negotiation.yml.
   */
  public function testLanguageNegotiation() {
    $config = $this->config('language.negotiation');
    $this->assertIdentical($config->get('session.parameter'), 'language');
    $this->assertIdentical($config->get('url.source'), 'domain');
  }

}
