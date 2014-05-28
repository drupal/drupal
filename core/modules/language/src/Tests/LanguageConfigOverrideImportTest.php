<?php

/**
 * @file
 * Contains \Drupal\language\Tests\LanguageConfigOverrideImportTest.
 */

namespace Drupal\language\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests synchronization of language configuration overrides.
 */
class LanguageConfigOverrideImportTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'config', 'locale', 'config_translation');

  public static function getInfo() {
    return array(
      'name' => 'Language config override synchronize',
      'description' => 'Ensures the language config overrides can be synchronized.',
      'group' => 'Language',
    );
  }

  /**
   * Tests that language can be enabled and overrides are created during a sync.
   */
  public function testConfigOverrideImport() {
    language_save(new Language(array(
      'name' => 'French',
      'id' => 'fr',
    )));
    /* @var \Drupal\Core\Config\StorageInterface $staging */
    $staging = \Drupal::service('config.storage.staging');
    $this->copyConfig(\Drupal::service('config.storage'), $staging);

    // Uninstall the language module and its dependencies so we can test
    // enabling the language module and creating overrides at the same time
    // during a configuration synchronisation.
    \Drupal::moduleHandler()->uninstall(array('language'));
    // Ensure that the current site has no overrides registered to the
    // ConfigFactory.
    $this->rebuildContainer();

    /* @var \Drupal\Core\Config\StorageInterface $override_staging */
    $override_staging = $staging->createCollection('language.fr');
    // Create some overrides in staging.
    $override_staging->write('system.site', array('name' => 'FR default site name'));
    $override_staging->write('system.maintenance', array('message' => 'FR message: @site is currently under maintenance. We should be back shortly. Thank you for your patience'));

    $this->configImporter()->import();
    $this->rebuildContainer();
    \Drupal::service('router.builder')->rebuild();

    $override = \Drupal::languageManager()->getLanguageConfigOverride('fr', 'system.site');
    $this->assertEqual('FR default site name', $override->get('name'));
    $this->drupalGet('fr');
    $this->assertText('FR default site name');

    $this->drupalLogin($this->root_user);
    $this->drupalGet('admin/config/development/maintenance/translate/fr/edit');
    $this->assertText('FR message: @site is currently under maintenance. We should be back shortly. Thank you for your patience');
  }

  /**
   * Tests that configuration events are not fired during a sync of overrides.
   */
  public function testConfigOverrideImportEvents() {
    // Enable the config_events_test module so we can record events occurring.
    \Drupal::moduleHandler()->install(array('config_events_test'));
    $this->rebuildContainer();

    language_save(new Language(array(
      'name' => 'French',
      'id' => 'fr',
    )));
    /* @var \Drupal\Core\Config\StorageInterface $staging */
    $staging = \Drupal::service('config.storage.staging');
    $this->copyConfig(\Drupal::service('config.storage'), $staging);

    /* @var \Drupal\Core\Config\StorageInterface $override_staging */
    $override_staging = $staging->createCollection('language.fr');
    // Create some overrides in staging.
    $override_staging->write('system.site', array('name' => 'FR default site name'));
    \Drupal::state()->set('config_events_test.event', FALSE);

    $this->configImporter()->import();
    $this->rebuildContainer();
    \Drupal::service('router.builder')->rebuild();

    // Test that no config save event has been fired during the import because
    // language configuration overrides do not fire events.
    $event_recorder = \Drupal::state()->get('config_events_test.event', FALSE);
    $this->assertFalse($event_recorder);

    $this->drupalGet('fr');
    $this->assertText('FR default site name');
  }

}
