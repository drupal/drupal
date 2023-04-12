<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the language list is not empty when language is installed.
 *
 * @group language
 */
class LanguageListModuleInstallTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language_test'];

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
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer modules',
    ]);
    $this->drupalLogin($admin_user);
    $edit = [];
    $edit['modules[language][enable]'] = 'language';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');

    $this->assertEquals(1, \Drupal::state()->get('language_test.language_count_preinstall', 0), 'Using LanguageManager::getLanguages() returns 1 language during Language installation.');

    // Get updated module list by rebuilding container.
    $this->rebuildContainer();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('language'), 'Language module is enabled');
  }

}
