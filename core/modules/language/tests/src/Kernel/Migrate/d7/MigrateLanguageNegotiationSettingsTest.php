<?php

namespace Drupal\Tests\language\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

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
