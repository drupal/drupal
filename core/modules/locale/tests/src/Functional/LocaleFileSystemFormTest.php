<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the locale functionality in the altered file settings form.
 */
#[Group('locale')]
#[RunTestsInSeparateProcesses]
class LocaleFileSystemFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
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
  #[IgnoreDeprecations]
  public function testFileConfigurationPage(): void {
    // By default there should be no setting for the translation directory.
    $this->drupalGet('admin/config/media/file-system');
    $this->assertSession()->elementNotExists('css', '.form-item-translation-path');

    // With locale module installed, the setting should appear.
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['locale']);
    $this->rebuildContainer();
    $this->drupalGet('admin/config/media/file-system');
    $this->assertSession()->elementExists('css', '.form-item-translation-path');

    // The setting should be reported correctly.
    $translation_path = $this->publicFilesDirectory . '/translations_changed';
    $settings['settings']['locale_translation_path'] = (object) [
      'value' => $this->publicFilesDirectory . '/translations_changed',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    $this->drupalGet('admin/config/media/file-system');
    $this->assertSession()->elementContains('css', '.form-item-translation-path', $translation_path);

    // If set, the config is preferred over the setting.
    // @todo remove this part when BC support for the config is removed.
    $translation_path_config = $this->publicFilesDirectory . '/translations_changed_config';
    $this->config('locale.settings')->set('translation.path', $translation_path_config)->save();
    $this->drupalGet('admin/config/media/file-system');
    $this->assertSession()->elementContains('css', '.form-item-translation-path', $translation_path_config);
  }

}
