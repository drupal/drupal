<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigLanguageOverrideTest.
 */

namespace Drupal\config\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\KernelTestBase;

/**
 * Confirm that language overrides work.
 *
 * @group config
 */
class ConfigLanguageOverrideTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user', 'language', 'config_test',  'system', 'field');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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

    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();

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

    // Test how overrides react to base configuration changes. Set up some base
    // values.
    \Drupal::config('config_test.foo')
      ->set('value', array('key' => 'original'))
      ->set('label', 'Original')
      ->save();
    \Drupal::languageManager()
      ->getLanguageConfigOverride('de', 'config_test.foo')
      ->set('value', array('key' => 'override'))
      ->set('label', 'Override')
      ->save();
    \Drupal::languageManager()
      ->getLanguageConfigOverride('fr', 'config_test.foo')
      ->set('value', array('key' => 'override'))
      ->save();
    \Drupal::configFactory()->clearStaticCache();
    $config = \Drupal::config('config_test.foo');
    $this->assertIdentical($config->get('value'), array('key' => 'override'));

    // Ensure renaming the config will rename the override.
    \Drupal::configFactory()->rename('config_test.foo', 'config_test.bar');
    $config = \Drupal::config('config_test.bar');
    $this->assertEqual($config->get('value'), array('key' => 'original'));
    $override = \Drupal::languageManager()->getLanguageConfigOverride('de', 'config_test.foo');
    $this->assertTrue($override->isNew());
    $this->assertEqual($override->get('value'), NULL);
    $override = \Drupal::languageManager()->getLanguageConfigOverride('de', 'config_test.bar');
    $this->assertFalse($override->isNew());
    $this->assertEqual($override->get('value'), array('key' => 'override'));
    $override = \Drupal::languageManager()->getLanguageConfigOverride('fr', 'config_test.bar');
    $this->assertFalse($override->isNew());
    $this->assertEqual($override->get('value'), array('key' => 'override'));

    // Ensure changing data in the config will update the overrides.
    $config = \Drupal::config('config_test.bar')->clear('value.key')->save();
    $this->assertEqual($config->get('value'), array());
    $override = \Drupal::languageManager()->getLanguageConfigOverride('de', 'config_test.bar');
    $this->assertFalse($override->isNew());
    $this->assertEqual($override->get('value'), NULL);
    // The French override will become empty and therefore removed.
    $override = \Drupal::languageManager()->getLanguageConfigOverride('fr', 'config_test.bar');
    $this->assertTrue($override->isNew());
    $this->assertEqual($override->get('value'), NULL);

    // Ensure deleting the config will delete the override.
    \Drupal::configFactory()->get('config_test.bar')->delete();
    $override = \Drupal::languageManager()->getLanguageConfigOverride('de', 'config_test.bar');
    $this->assertTrue($override->isNew());
    $this->assertEqual($override->get('value'), NULL);
  }
}

