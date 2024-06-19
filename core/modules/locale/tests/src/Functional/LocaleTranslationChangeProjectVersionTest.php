<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests how translations are handled when a project gets updated.
 *
 * @group locale
 */
class LocaleTranslationChangeProjectVersionTest extends LocaleUpdateBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.batch');
    ConfigurableLanguage::createFromLangcode('de')->save();

    \Drupal::state()->set('locale.test_projects_alter', TRUE);
    \Drupal::state()->set('locale.remove_core_project', TRUE);

    // Setup the environment.
    $config = $this->config('locale.settings');
    $public_path = PublicStream::basePath();
    $this->setTranslationsDirectory($public_path . '/local');
    $config
      ->set('translation.default_filename', '%project-%version.%language._po')
      ->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_LOCAL)
      ->save();

    // This test uses .po files for the old translation file instead of the ._po
    // files because locale_translate_get_interface_translation_files() (used to
    // delete old translation files) only works with .po files.
    // The new translation file uses _po.
    // Old version: 8.x-1.0; New version: 8.x-1.1.
    $this->makePoFile('remote/all/contrib_module_one', 'contrib_module_one-8.x-1.0.de.po', $this->timestampOld, []);
    $this->makePoFile('remote/all/contrib_module_one', 'contrib_module_one-8.x-1.1.de._po', $this->timestampNew, []);
    $this->makePoFile('local', 'contrib_module_one-8.x-1.0.de.po', $this->timestampOld, []);

    // Initialize the projects status and change the project version to the old
    // version. This makes the code update the module translation to the new
    // version when the (batch) update script is triggered.
    $status = locale_translation_get_status();
    $status['contrib_module_one']['de']->version = '8.x-1.0';
    \Drupal::keyValue('locale.translation_status')->setMultiple($status);
  }

  /**
   * Tests update translations when project version changes.
   */
  public function testUpdateImportSourceRemote(): void {

    // Verify that the project status has the old version.
    $status = locale_translation_get_status(['contrib_module_one']);
    $this->assertEquals('8.x-1.0', $status['contrib_module_one']['de']->version);

    // Verify that the old translation file exists and the new does not exist.
    $this->assertFileExists('translations://contrib_module_one-8.x-1.0.de.po');
    $this->assertFileDoesNotExist('translations://contrib_module_one-8.x-1.1.de._po');

    // Run batch tasks.
    $context = [];
    locale_translation_batch_version_check('contrib_module_one', 'de', $context);
    locale_translation_batch_status_check('contrib_module_one', 'de', [], $context);
    locale_translation_batch_fetch_download('contrib_module_one', 'de', $context);

    // Verify that the project status has the new version.
    $status = locale_translation_get_status(['contrib_module_one']);
    $this->assertEquals('8.x-1.1', $status['contrib_module_one']['de']->version);

    // Verify that the old translation file was removed and the new was
    // downloaded.
    $this->assertFileDoesNotExist('translations://contrib_module_one-8.x-1.0.de.po');
    $this->assertFileExists('translations://contrib_module_one-8.x-1.1.de._po');
  }

}
