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
  public static $modules = ['language'];

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
    $this->assertSame($config->get('session.parameter'), 'language');
    $this->assertSame($config->get('url.source'), LanguageNegotiationUrl::CONFIG_PATH_PREFIX);
    $this->assertSame($config->get('selected_langcode'), 'site_default');
    $expected_prefixes = [
      'en' => '',
      'fr' => 'fr',
      'zu' => 'zu',
    ];
    $this->assertSame($config->get('url.prefixes'), $expected_prefixes);

    $config = $this->config('language.types');
    $this->assertSame($config->get('all'), ['language_interface', 'language_content', 'language_url']);
    $this->assertSame($config->get('configurable'), ['language_interface']);
    $this->assertSame($config->get('negotiation.language_content.enabled'), ['language-interface' => 0]);
    $this->assertSame($config->get('negotiation.language_url.enabled'), ['language-url' => 0, 'language-url-fallback' => 1]);
    $expected_language_interface = [
      'language-url' => 0,
      'language-selected' => 1,
    ];
    $this->assertSame($config->get('negotiation.language_interface.enabled'), $expected_language_interface);
  }

  /**
   * Tests the migration with LANGUAGE_NEGOTIATION_NONE.
   */
  public function testLanguageNegotiationWithNoNegotiation() {
    $this->sourceDatabase->update('variable')
      ->fields(array('value' => serialize(0)))
      ->condition('name', 'language_negotiation')
      ->execute();

    $this->executeMigrations([
      'language',
      'd6_language_negotiation_settings',
      'language_prefixes_and_domains',
      'd6_language_types',
    ]);

    $config = $this->config('language.negotiation');
    $this->assertSame($config->get('session.parameter'), 'language');
    $this->assertSame($config->get('url.source'), LanguageNegotiationUrl::CONFIG_PATH_PREFIX);
    $this->assertSame($config->get('selected_langcode'), 'site_default');

    $config = $this->config('language.types');
    $this->assertSame($config->get('all'), ['language_interface', 'language_content', 'language_url']);
    $this->assertSame($config->get('configurable'), ['language_interface']);
    $this->assertSame($config->get('negotiation.language_content.enabled'), ['language-interface' => 0]);
    $this->assertSame($config->get('negotiation.language_url.enabled'), ['language-url' => 0, 'language-url-fallback' => 1]);
    $expected_language_interface = [
      'language-selected' => 0,
    ];
    $this->assertSame($config->get('negotiation.language_interface.enabled'), $expected_language_interface);
  }

  /**
   * Tests the migration with LANGUAGE_NEGOTIATION_PATH.
   */
  public function testLanguageNegotiationWithPathPrefix() {
    $this->sourceDatabase->update('variable')
      ->fields(array('value' => serialize(2)))
      ->condition('name', 'language_negotiation')
      ->execute();

    $this->executeMigrations([
      'language',
      'd6_language_negotiation_settings',
      'language_prefixes_and_domains',
      'd6_language_types',
    ]);

    $config = $this->config('language.negotiation');
    $this->assertSame($config->get('session.parameter'), 'language');
    $this->assertSame($config->get('url.source'), LanguageNegotiationUrl::CONFIG_PATH_PREFIX);
    $this->assertSame($config->get('selected_langcode'), 'site_default');
    $expected_prefixes = [
      'en' => '',
      'fr' => 'fr',
      'zu' => 'zu',
    ];
    $this->assertSame($config->get('url.prefixes'), $expected_prefixes);

    $config = $this->config('language.types');
    $this->assertSame($config->get('all'), ['language_interface', 'language_content', 'language_url']);
    $this->assertSame($config->get('configurable'), ['language_interface']);
    $this->assertSame($config->get('negotiation.language_content.enabled'), ['language-interface' => 0]);
    $this->assertSame($config->get('negotiation.language_url.enabled'), ['language-url' => 0, 'language-url-fallback' => 1]);
    $expected_language_interface = [
      'language-url' => 0,
      'language-user' => 1,
      'language-browser' => 2,
      'language-selected' => 3,
    ];
    $this->assertSame($config->get('negotiation.language_interface.enabled'), $expected_language_interface);
  }

  /**
   * Tests the migration with LANGUAGE_NEGOTIATION_DOMAIN.
   */
  public function testLanguageNegotiationWithDomain() {
    $this->sourceDatabase->update('variable')
      ->fields(array('value' => serialize(3)))
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
    $this->assertSame($config->get('session.parameter'), 'language');
    $this->assertSame($config->get('url.source'), LanguageNegotiationUrl::CONFIG_DOMAIN);
    $this->assertSame($config->get('selected_langcode'), 'site_default');
    $expected_domains = [
      'en' => parse_url($base_url, PHP_URL_HOST),
      'fr' => 'fr.drupal.org',
      'zu' => 'zu.drupal.org',
    ];
    $this->assertSame($config->get('url.domains'), $expected_domains);

    $config = $this->config('language.types');
    $this->assertSame($config->get('all'), ['language_interface', 'language_content', 'language_url']);
    $this->assertSame($config->get('configurable'), ['language_interface']);
    $this->assertSame($config->get('negotiation.language_content.enabled'), ['language-interface' => 0]);
    $this->assertSame($config->get('negotiation.language_url.enabled'), ['language-url' => 0, 'language-url-fallback' => 1]);
    $expected_language_interface = [
      'language-url' => 0,
      'language-selected' => 1,
    ];
    $this->assertSame($config->get('negotiation.language_interface.enabled'), $expected_language_interface);
  }

}
