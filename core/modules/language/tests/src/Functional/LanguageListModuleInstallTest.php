<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests enabling Language if a module exists that calls
 * LanguageManager::getLanguages() during installation.
 *
 * @group language
 */
class LanguageListModuleInstallTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['language_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests enabling Language.
   */
  public function testModuleInstallLanguageList() {
    // Since LanguageManager::getLanguages() uses static caches we need to do
    // this by enabling the module using the UI.
    $admin_user = $this->drupalCreateUser(['access administration pages', 'administer modules']);
    $this->drupalLogin($admin_user);
    $edit = [];
    $edit['modules[language][enable]'] = 'language';
    $this->drupalPostForm('admin/modules', $edit, t('Install'));

    $this->assertEqual(\Drupal::state()->get('language_test.language_count_preinstall', 0), 1, 'Using LanguageManager::getLanguages() returns 1 language during Language installation.');

    // Get updated module list by rebuilding container.
    $this->rebuildContainer();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('language'), 'Language module is enabled');
  }

}
