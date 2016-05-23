<?php

namespace Drupal\KernelTests\Core\Installer;

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
  function testInstallerTranslationFiles() {
    // Different translation files would be found depending on which language
    // we are looking for.
    $expected_translation_files = array(
      NULL => array('drupal-8.0.0-beta2.hu.po', 'drupal-8.0.0.de.po'),
      'de' => array('drupal-8.0.0.de.po'),
      'hu' => array('drupal-8.0.0-beta2.hu.po'),
      'it' => array(),
    );

    // Hardcode the simpletest module location as we don't yet know where it is.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    $file_translation = new FileTranslation('core/modules/simpletest/files/translations');
    foreach ($expected_translation_files as $langcode => $files_expected) {
      $files_found = $file_translation->findTranslationFiles($langcode);
      $this->assertTrue(count($files_found) == count($files_expected), format_string('@count installer languages found.', array('@count' => count($files_expected))));
      foreach ($files_found as $file) {
        $this->assertTrue(in_array($file->filename, $files_expected), format_string('@file found.', array('@file' => $file->filename)));
      }
    }
  }

  /**
   * Tests profile info caching in non-English languages.
   */
  function testInstallerTranslationCache() {
    require_once 'core/includes/install.inc';

    // Prime the drupal_get_filename() static cache with the location of the
    // testing profile as it is not the currently active profile and we don't
    // yet have any cached way to retrieve its location.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    drupal_get_filename('profile', 'testing', 'core/profiles/testing/testing.info.yml');

    $info_en = install_profile_info('testing', 'en');
    $info_nl = install_profile_info('testing', 'nl');

    $this->assertFalse(in_array('locale', $info_en['dependencies']), 'Locale is not set when installing in English.');
    $this->assertTrue(in_array('locale', $info_nl['dependencies']), 'Locale is set when installing in Dutch.');
  }

}
