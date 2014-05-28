<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigLanguageOverrideTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests language config override.
 */
class ConfigLanguageOverrideTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user', 'language', 'config_test',  'system', 'field');

  public static function getInfo() {
    return array(
      'name' => 'Language override',
      'description' => 'Confirm that language overrides work',
      'group' => 'Configuration',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->installConfig(array('config_test'));
  }

  /**
   * Tests locale override based on language.
   */
  function testConfigLanguageOverride() {
    // The language module implements a config factory override object that
    // overrides configuration when the Language module is enabled. This test ensures that
    // English overrides work.
    \Drupal::languageManager()->setConfigOverrideLanguage(language_load('en'));
    $config = \Drupal::config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'en bar');

    // Ensure that the raw data is not translated.
    $raw = $config->getRawData();
    $this->assertIdentical($raw['foo'], 'bar');

    language_save(new Language(array(
      'name' => 'French',
      'id' => 'fr',
    )));
    language_save(new Language(array(
      'name' => 'German',
      'id' => 'de',
    )));

    \Drupal::languageManager()->setConfigOverrideLanguage(language_load('fr'));
    $config = \Drupal::config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'fr bar');

    \Drupal::languageManager()->setConfigOverrideLanguage(language_load('de'));
    $config = \Drupal::config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'de bar');

    // Test overrides of completely new configuration objects. In normal runtime
    // this should only happen for configuration entities as we should not be
    // creating simple configuration objects on the fly.
    \Drupal::languageManager()
      ->getLanguageConfigOverride('de', 'config_test.new')
      ->set('language', 'override')
      ->save();
    $config = \Drupal::config('config_test.new');
    $this->assertTrue($config->isNew(), 'The configuration object config_test.new is new');
    $this->assertIdentical($config->get('language'), 'override');
    $old_state = \Drupal::configFactory()->getOverrideState();
    \Drupal::configFactory()->setOverrideState(FALSE);
    $config = \Drupal::config('config_test.new');
    $this->assertIdentical($config->get('language'), NULL);
    \Drupal::configFactory()->setOverrideState($old_state);
  }
}

