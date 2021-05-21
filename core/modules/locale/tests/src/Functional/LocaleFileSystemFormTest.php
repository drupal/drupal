<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the locale functionality in the altered file settings form.
 *
 * @group locale
 */
class LocaleFileSystemFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($account);
  }

  /**
   * Tests translation directory settings on the file settings form.
   */
  public function testFileConfigurationPage() {
    // By default there should be no setting for the translation directory.
    $this->drupalGet('admin/config/media/file-system');
    $this->assertSession()->fieldNotExists('translation_path');

    // With locale module installed, the setting should appear.
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['locale']);
    $this->rebuildContainer();
    $this->drupalGet('admin/config/media/file-system');
    $this->assertSession()->fieldExists('translation_path');

    // The setting should persist.
    $translation_path = $this->publicFilesDirectory . '/translations_changed';
    $fields = [
      'translation_path' => $translation_path,
    ];
    $this->submitForm($fields, 'Save configuration');
    $this->drupalGet('admin/config/media/file-system');
    $this->assertSession()->fieldValueEquals('translation_path', $translation_path);
    $this->assertEquals($this->config('locale.settings')->get('translation.path'), $translation_path);
  }

}
