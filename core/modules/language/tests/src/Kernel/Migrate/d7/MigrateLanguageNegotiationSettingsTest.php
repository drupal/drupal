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
  public static $modules = ['language'];

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

  /**
   * Tests the migration with prefix negotiation.
   */
  public function testLanguageNegotiationWithPrefix() {
    $this->executeMigrations([
      'language',
      'd7_language_negotiation_settings',
      'language_prefixes_and_domains',
    ]);

    $config = $this->config('language.negotiation');
    $this->assertSame($config->get('session.parameter'), 'language');
    $this->assertSame($config->get('url.source'), LanguageNegotiationUrl::CONFIG_PATH_PREFIX);
    $this->assertSame($config->get('selected_langcode'), 'site_default');
    $expected_prefixes = [
      'en' => '',
      'is' => 'is',
    ];
    $this->assertSame($config->get('url.prefixes'), $expected_prefixes);
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
    $this->assertSame($config->get('session.parameter'), 'language');
    $this->assertSame($config->get('url.source'), LanguageNegotiationUrl::CONFIG_DOMAIN);
    $this->assertSame($config->get('selected_langcode'), 'site_default');
    $expected_domains = [
      'en' => parse_url($base_url, PHP_URL_HOST),
      'is' => 'is.drupal.org',
    ];
    $this->assertSame($config->get('url.domains'), $expected_domains);
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
    $this->assertSame($config->get('session.parameter'), 'language');
    $this->assertSame($config->get('url.source'), LanguageNegotiationUrl::CONFIG_PATH_PREFIX);
    $this->assertSame($config->get('selected_langcode'), 'site_default');
    $expected_prefixes = [
      'en' => '',
      'is' => 'is',
    ];
    $this->assertSame($config->get('url.prefixes'), $expected_prefixes);
  }

}
