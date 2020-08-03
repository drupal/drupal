<?php

namespace Drupal\Tests\language\Unit\process;

use Drupal\language\Plugin\migrate\process\LanguageDomains;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * @coversDefaultClass \Drupal\language\Plugin\migrate\process\LanguageDomains
 * @group language
 */
class LanguageDomainsTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected $backupGlobalsBlacklist = ['base_url'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $configuration = [
      'key' => 'language',
      'value' => 'domain',
    ];
    $this->plugin = new LanguageDomains($configuration, 'map', []);
    parent::setUp();

    // The language_domains plugin calls getSourceProperty() to check if domain
    // negotiation is used. If it is the values will be processed so we need it
    // to return TRUE to be able to test the process.
    $this->row->expects($this->once())
      ->method('getSourceProperty')
      ->will($this->returnValue(TRUE));

    // The language_domains plugin use $base_url to fill empty domains.
    global $base_url;
    $base_url = 'http://example.com';
  }

  /**
   * @covers ::transform
   */
  public function testTransform() {
    $source = [
      ['language' => 'en', 'domain' => ''],
      ['language' => 'fr', 'domain' => 'fr.example.com'],
      ['language' => 'es', 'domain' => 'http://es.example.com'],
      ['language' => 'hu', 'domain' => 'https://hu.example.com'],
    ];
    $expected = [
      'en' => 'example.com',
      'fr' => 'fr.example.com',
      'es' => 'es.example.com',
      'hu' => 'hu.example.com',
    ];
    $value = $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($value, $expected);
  }

}
