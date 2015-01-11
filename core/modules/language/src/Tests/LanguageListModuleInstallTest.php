<?php

/**
 * @file
 * Contains \Drupal\language\Tests\LanguageListModuleInstallTest.
 */

namespace Drupal\language\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests enabling Language if a module exists that calls
 * LanguageManager::getLanguages() during installation.
 *
 * @group language
 */
class LanguageListModuleInstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language_test');

  /**
   * Tests enabling Language.
   */
  function testModuleInstallLanguageList() {
    // Since LanguageManager::getLanguages() uses static caches we need to do
    // this by enabling the module using the UI.
    $admin_user = $this->drupalCreateUser(array('access administration pages', 'administer modules'));
    $this->drupalLogin($admin_user);
    $edit = array();
    $edit['modules[Multilingual][language][enable]'] = 'language';
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));

    $this->assertEqual(\Drupal::state()->get('language_test.language_count_preinstall', 0), 1, 'Using LanguageManager::getLanguages() returns 1 language during Language installation.');

    // Get updated module list by rebuilding container.
    $this->rebuildContainer();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('language'), 'Language module is enabled');
  }
}
