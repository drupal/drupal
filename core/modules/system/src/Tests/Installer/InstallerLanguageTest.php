<?php

/**
 * @file
 * Contains Drupal\system\Tests\Installer\InstallerLanguageTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\KernelTestBase;
use Drupal\Core\StringTranslation\Translator\FileTranslation;

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

}
