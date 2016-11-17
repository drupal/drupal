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
    $this->executeMigrations([
      'd7_language_negotiation_settings',
      'd7_language_types',
    ]);
  }

  /**
   * Tests migration of language negotiation variables to language.negotiation.yml.
   */
  public function testLanguageNegotiation() {
    $config = $this->config('language.negotiation');
    $this->assertIdentical($config->get('session.parameter'), 'language');
    $this->assertIdentical($config->get('url.source'), 'domain');
  }

  /**
   * Tests migration of language types variables to language.types.yml.
   */
  public function testLanguageTypes() {
    $config = $this->config('language.types');
    $this->assertSame($config->get('all'), ['language_content', 'language_url', 'language_interface']);
    $this->assertSame($config->get('configurable'), ['language_interface']);
    $this->assertSame($config->get('negotiation.language_content'), ['enabled' => ['language-interface' => 0]]);
    $this->assertSame($config->get('negotiation.language_url'), ['enabled' => ['language-url' => 0, 'language-url-fallback' => 1]]);
    $expected_language_interface = [
      'enabled' => [
        'language-url' => -9,
        'language-user' => -10,
        'language-selected' => -6,
      ],
      'method_weights' => [
        'language-url' => -9,
        'language-session' => -8,
        'language-user' => -10,
        'language-browser' => -7,
        'language-selected' => -6,
      ],
    ];
    $this->assertSame($config->get('negotiation.language_interface'), $expected_language_interface);
  }

}
