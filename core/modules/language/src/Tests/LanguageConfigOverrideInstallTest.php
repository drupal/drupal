<?php

/**
 * @file
 * Contains Drupal\language\Tests\LanguageConfigOverrideInstallTest.
 */
namespace Drupal\language\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\KernelTestBase;

/**
 * Ensures the language config overrides can be installed.
 *
 * @group language
 */
class LanguageConfigOverrideInstallTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'config_events_test');

  /**
   * Tests the configuration events are not fired during install of overrides.
   */
  public function testLanguageConfigOverrideInstall() {
    language_save(new Language(array('id' => 'de')));
    // Need to enable test module after creating the language otherwise saving
    // the language will install the configuration.
    $this->enableModules(array('language_config_override_test'));
    \Drupal::state()->set('config_events_test.event', FALSE);
    $this->installConfig(array('language_config_override_test'));
    $event_recorder = \Drupal::state()->get('config_events_test.event', FALSE);
    $this->assertFalse($event_recorder);
    $config = \Drupal::service('language.config_factory_override')->getOverride('de', 'language_config_override_test.settings');
    $this->assertEqual($config->get('name'), 'Deutsch');
  }

}
