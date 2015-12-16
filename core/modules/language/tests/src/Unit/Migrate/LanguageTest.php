<?php

/**
 * @file
 * Contains \Drupal\Tests\language\Unit\Migrate\LanguageTest.
 */

namespace Drupal\Tests\language\Unit\Migrate;

use Drupal\language\Plugin\migrate\source\Language;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * @coversDefaultClass \Drupal\language\Plugin\migrate\source\Language
 * @group language
 */
class LanguageTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = Language::class;

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'language',
    ),
  );

  protected $databaseContents = array(
    'languages' => array(
      array(
        'language' => 'en',
        'name' => 'English',
        'native' => 'English',
        'direction' => '0',
        'enabled' => '1',
        'plurals' => '0',
        'formula' => '',
        'domain' => '',
        'prefix' => '',
        'weight' => '0',
        'javascript' => '',
      ),
      array(
        'language' => 'fr',
        'name' => 'French',
        'native' => 'Français',
        'direction' => '0',
        'enabled' => '0',
        'plurals' => '2',
        'formula' => '($n>1)',
        'domain' => '',
        'prefix' => 'fr',
        'weight' => '0',
        'javascript' => '',
      ),
    ),
  );

  protected $expectedResults = array(
    array(
      'language' => 'en',
      'name' => 'English',
      'native' => 'English',
      'direction' => '0',
      'enabled' => '1',
      'plurals' => '0',
      'formula' => '',
      'domain' => '',
      'prefix' => '',
      'weight' => '0',
      'javascript' => '',
    ),
    array(
      'language' => 'fr',
      'name' => 'French',
      'native' => 'Français',
      'direction' => '0',
      'enabled' => '0',
      'plurals' => '2',
      'formula' => '($n>1)',
      'domain' => '',
      'prefix' => 'fr',
      'weight' => '0',
      'javascript' => '',
    ),
  );

}
