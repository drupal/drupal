<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleConfigManagerTest.
 */

namespace Drupal\locale\Tests;

use Drupal\locale\LocaleConfigManager;
use Drupal\Core\Language\Language;
use Drupal\Core\Config\StorageException;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Provides tests for \Drupal\locale\LocaleConfigManager
 */
class LocaleConfigManagerTest extends DrupalUnitTestBase {

  /**
   * A list of modules to install for this test.
   *
   * @var array
   */
  public static $modules = array('locale', 'locale_test');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Locale config manager',
      'description' => 'Tests that the locale config manager operates correctly.',
      'group' => 'Locale',
    );
  }

  /**
   * Tests hasTranslation().
   */
  public function testHasTranslation() {
    $locale_config_manager = new LocaleConfigManager(
      // In contrast to the actual configuration we use the installer storage
      // as the config storage. That way, we do not actually have to install
      // the module and can extend DrupalUnitTestBase.
      $this->container->get('config.storage.installer'),
      $this->container->get('config.storage.schema'),
      $this->container->get('config.storage.installer'),
      $this->container->get('locale.storage'),
      $this->container->get('cache.config'),
      $this->container->get('config.factory')
    );

    $language = new Language(array('id' => 'de'));
    // The installer storage throws an expcetion when requesting a non-existing
    // file.
    try {
      $locale_config_manager->hasTranslation('locale_test.no_translation', $language);
    }
    catch (StorageException $exception) {
      $result = FALSE;
    }
    $this->assertIdentical(FALSE, $result);

    $result = $locale_config_manager->hasTranslation('locale_test.translation', $language);
    $this->assertIdentical(TRUE, $result);
  }
}
