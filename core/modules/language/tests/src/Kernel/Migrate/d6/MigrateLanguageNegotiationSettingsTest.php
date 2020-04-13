<?php

namespace Drupal\Tests\language\Kernel\Migrate\d6;

use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the migration of language negotiation and language types.
 *
 * @group migrate_drupal_6
 */
class MigrateLanguageNegotiationSettingsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  /**
   * Tests the migration with LANGUAGE_NEGOTIATION_PATH_DEFAULT.
   */
  public function testLanguageNegotiationWithDefaultPathPrefix() {
    $this->executeMigrations([
      'language',
      'd6_language_negotiation_settings',
      'language_prefixes_and_domains',
      'd6_language_types',
    ]);

    $config = $this->config('language.negotiation');
    $this->assertSame('language', $config->get('session.parameter'));
    $this->assertSame(LanguageNegotiationUrl::CONFIG_PATH_PREFIX, $config->get('url.source'));
    $this->assertSame('site_default', $config->get('selected_langcode'));
    $expected_prefixes = [
      'en' => '',
      'fr' => 'fr',
      'zu' => 'zu',
    ];
    $this->assertSame($expected_prefixes, $config->get('url.prefixes'));

    $config = $this->config('language.types');
    $this->assertSame(['language_interface', 'language_content', 'language_url'], $config->get('all'));
    $this->assertSame(['language_interface'], $config->get('configurable'));
    $this->assertSame(['language-interface' => 0], $config->get('negotiation.language_content.enabled'));
    $this->assertSame(['language-url' => 0, 'language-url-fallback' => 1], $config->get('negotiation.language_url.enabled'));
    $expected_language_interface = [
      'language-url' => 0,
      'language-selected' => 1,
    ];
    $this->assertSame($expected_language_interface, $config->get('negotiation.language_interface.enabled'));
  }

  /**
   * Tests the migration with LANGUAGE_NEGOTIATION_NONE.
   */
  public function testLanguageNegotiationWithNoNegotiation() {
    $this->sourceDatabase->update('variable')
      ->fields(['value' => serialize(0)])
      ->condition('name', 'language_negotiation')
      ->execute();

    $this->executeMigrations([
      'language',
      'd6_language_negotiation_settings',
      'language_prefixes_and_domains',
      'd6_language_types',
    ]);

    $config = $this->config('language.negotiation');
    $this->assertSame('language', $config->get('session.parameter'));
    $this->assertSame(LanguageNegotiationUrl::CONFIG_PATH_PREFIX, $config->get('url.source'));
    $this->assertSame('site_default', $config->get('selected_langcode'));

    $config = $this->config('language.types');
    $this->assertSame(['language_interface', 'language_content', 'language_url'], $config->get('all'));
    $this->assertSame(['language_interface'], $config->get('configurable'));
    $this->assertSame(['language-interface' => 0], $config->get('negotiation.language_content.enabled'));
    $this->assertSame(['language-url' => 0, 'language-url-fallback' => 1], $config->get('negotiation.language_url.enabled'));
    $expected_language_interface = [
      'language-selected' => 0,
    ];
    $this->assertSame($expected_language_interface, $config->get('negotiation.language_interface.enabled'));
  }

  /**
   * Tests the migration with LANGUAGE_NEGOTIATION_PATH.
   */
  public function testLanguageNegotiationWithPathPrefix() {
    $this->sourceDatabase->update('variable')
      ->fields(['value' => serialize(2)])
      ->condition('name', 'language_negotiation')
      ->execute();

    $this->executeMigrations([
      'language',
      'd6_language_negotiation_settings',
      'language_prefixes_and_domains',
      'd6_language_types',
    ]);

    $config = $this->config('language.negotiation');
    $this->assertSame('language', $config->get('session.parameter'));
    $this->assertSame(LanguageNegotiationUrl::CONFIG_PATH_PREFIX, $config->get('url.source'));
    $this->assertSame('site_default', $config->get('selected_langcode'));
    $expected_prefixes = [
      'en' => '',
      'fr' => 'fr',
      'zu' => 'zu',
    ];
    $this->assertSame($expected_prefixes, $config->get('url.prefixes'));

    $config = $this->config('language.types');
    $this->assertSame(['language_interface', 'language_content', 'language_url'], $config->get('all'));
    $this->assertSame(['language_interface'], $config->get('configurable'));
    $this->assertSame(['language-interface' => 0], $config->get('negotiation.language_content.enabled'));
    $this->assertSame(['language-url' => 0, 'language-url-fallback' => 1], $config->get('negotiation.language_url.enabled'));
    $expected_language_interface = [
      'language-url' => 0,
      'language-user' => 1,
      'language-browser' => 2,
      'language-selected' => 3,
    ];
    $this->assertSame($expected_language_interface, $config->get('negotiation.language_interface.enabled'));
  }

  /**
   * Tests the migration with LANGUAGE_NEGOTIATION_DOMAIN.
   */
  public function testLanguageNegotiationWithDomain() {
    $this->sourceDatabase->update('variable')
      ->fields(['value' => serialize(3)])
      ->condition('name', 'language_negotiation')
      ->execute();

    $this->executeMigrations([
      'language',
      'd6_language_negotiation_settings',
      'language_prefixes_and_domains',
      'd6_language_types',
    ]);

    global $base_url;
    $config = $this->config('language.negotiation');
    $this->assertSame('language', $config->get('session.parameter'));
    $this->assertSame(LanguageNegotiationUrl::CONFIG_DOMAIN, $config->get('url.source'));
    $this->assertSame('site_default', $config->get('selected_langcode'));
    $expected_domains = [
      'en' => parse_url($base_url, PHP_URL_HOST),
      'fr' => 'fr.drupal.org',
      'zu' => 'zu.drupal.org',
    ];
    $this->assertSame($expected_domains, $config->get('url.domains'));

    $config = $this->config('language.types');
    $this->assertSame(['language_interface', 'language_content', 'language_url'], $config->get('all'));
    $this->assertSame(['language_interface'], $config->get('configurable'));
    $this->assertSame(['language-interface' => 0], $config->get('negotiation.language_content.enabled'));
    $this->assertSame(['language-url' => 0, 'language-url-fallback' => 1], $config->get('negotiation.language_url.enabled'));
    $expected_language_interface = [
      'language-url' => 0,
      'language-selected' => 1,
    ];
    $this->assertSame($expected_language_interface, $config->get('negotiation.language_interface.enabled'));
  }

}
