<?php

namespace Drupal\KernelTests\Core\Installer;

use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\StringTranslation\Translator\FileTranslation;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for installer language support.
 *
 * @group Installer
 */
class InstallerLanguageTest extends KernelTestBase {

  /**
   * Tests that the installer can find translation files.
   */
  public function testInstallerTranslationFiles() {
    // Different translation files would be found depending on which language
    // we are looking for.
    $expected_translation_files = [
      NULL => ['drupal-8.0.0-beta2.hu.po', 'drupal-8.0.0.de.po', 'drupal-8.0.x.fr-CA.po'],
      'de' => ['drupal-8.0.0.de.po'],
      'fr-CA' => ['drupal-8.0.x.fr-CA.po'],
      'hu' => ['drupal-8.0.0-beta2.hu.po'],
      'it' => [],
    ];

    // Hardcode the fixtures location as we don't yet know where it is.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    $file_translation = new FileTranslation('core/tests/fixtures/files/translations', $this->container->get('file_system'));
    foreach ($expected_translation_files as $langcode => $files_expected) {
      $files_found = $file_translation->findTranslationFiles($langcode);
      $this->assertSameSize($files_expected, $files_found, count($files_expected) . ' installer languages found.');
      foreach ($files_found as $file) {
        $this->assertContains($file->filename, $files_expected, $file->filename . ' found.');
      }
    }
  }

  /**
   * Tests profile info caching in non-English languages.
   */
  public function testInstallerTranslationCache() {
    require_once 'core/includes/install.inc';

    // Prime the \Drupal\Core\Extension\ExtensionList::getPathname() static
    // cache with the location of the testing profile as it isn't the currently
    // active profile and we don't yet have any cached way to retrieve its
    // location.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    $profile_list = \Drupal::service('extension.list.profile');
    assert($profile_list instanceof ProfileExtensionList);
    $profile_list->setPathname('testing', 'core/profiles/testing/testing.info.yml');

    $info_en = install_profile_info('testing', 'en');
    $info_nl = install_profile_info('testing', 'nl');

    $this->assertNotContains('locale', $info_en['install'], 'Locale is not set when installing in English.');
    $this->assertContains('locale', $info_nl['install'], 'Locale is set when installing in Dutch.');
  }

}
