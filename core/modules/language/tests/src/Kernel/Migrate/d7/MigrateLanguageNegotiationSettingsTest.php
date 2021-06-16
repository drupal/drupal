<?php

namespace Drupal\Tests\language\Kernel\Migrate\d7;

use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests the migration of language negotiation.
 *
 * @group migrate_drupal_7
 */
class MigrateLanguageNegotiationSettingsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  /**
   * Tests migration of language types variables to language.types.yml.
   */
  public function testLanguageTypes() {
    $this->executeMigrations([
      'language',
      'd7_language_negotiation_settings',
      'd7_language_types',
    ]);

    $config = $this->config('language.types');
    $this->assertSame(['language_content', 'language_url', 'language_interface'], $config->get('all'));
    $this->assertSame(['language_content', 'language_interface'], $config->get('configurable'));
    $this->assertSame(['enabled' => ['language-interface' => 0]], $config->get('negotiation.language_content'));
    $this->assertSame(['enabled' => ['language-url' => 0, 'language-url-fallback' => 1]], $config->get('negotiation.language_url'));
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
    $this->assertSame($expected_language_interface, $config->get('negotiation.language_interface'));
  }

  /**
   * Tests the migration with prefix negotiation.
   */
  public function testLanguageNegotiationWithPrefix() {
    $this->sourceDatabase->update('languages')
      ->fields(['domain' => ''])
      ->execute();

    $this->executeMigrations([
      'language',
      'd7_language_negotiation_settings',
      'language_prefixes_and_domains',
    ]);

    $config = $this->config('language.negotiation');
    $this->assertSame('language', $config->get('session.parameter'));
    $this->assertSame(LanguageNegotiationUrl::CONFIG_PATH_PREFIX, $config->get('url.source'));
    $this->assertSame('site_default', $config->get('selected_langcode'));
    $expected_prefixes = [
      'en' => '',
      'fr' => 'fr',
      'is' => 'is',
    ];
    $this->assertSame($expected_prefixes, $config->get('url.prefixes'));

    // If prefix negotiation is used, make sure that no domains are migrated.
    // Otherwise there will be validation errors when trying to save URL
    // language detection configuration from the UI.
    $expected_domains = [
      'en' => '',
      'fr' => '',
      'is' => '',
    ];
    $this->assertSame($expected_domains, $config->get('url.domains'));
  }

  /**
   * Tests the migration with domain negotiation.
   */
  public function testLanguageNegotiationWithDomain() {
    $this->sourceDatabase->update('variable')
      ->fields(['value' => serialize(1)])
      ->condition('name', 'locale_language_negotiation_url_part')
      ->execute();

    $this->executeMigrations([
      'language',
      'd7_language_negotiation_settings',
      'language_prefixes_and_domains',
    ]);

    global $base_url;
    $config = $this->config('language.negotiation');
    $this->assertSame('language', $config->get('session.parameter'));
    $this->assertSame(LanguageNegotiationUrl::CONFIG_DOMAIN, $config->get('url.source'));
    $this->assertSame('site_default', $config->get('selected_langcode'));
    $expected_domains = [
      'en' => parse_url($base_url, PHP_URL_HOST),
      'fr' => 'fr.drupal.org',
      'is' => 'is.drupal.org',
    ];
    $this->assertSame($expected_domains, $config->get('url.domains'));
  }

  /**
   * Tests the migration with non-existent variables.
   */
  public function testLanguageNegotiationWithNonExistentVariables() {
    $this->sourceDatabase->delete('variable')
      ->condition('name', ['local_language_negotiation_url_part', 'local_language_negotiation_session_param'], 'IN')
      ->execute();

    $this->executeMigrations([
      'language',
      'd6_language_negotiation_settings',
      'language_prefixes_and_domains',
    ]);

    $config = $this->config('language.negotiation');
    $this->assertSame('language', $config->get('session.parameter'));
    $this->assertSame(LanguageNegotiationUrl::CONFIG_PATH_PREFIX, $config->get('url.source'));
    $this->assertSame('site_default', $config->get('selected_langcode'));
    $expected_prefixes = [
      'en' => '',
      'fr' => 'fr',
      'is' => 'is',
    ];
    $this->assertSame($expected_prefixes, $config->get('url.prefixes'));
  }

}
