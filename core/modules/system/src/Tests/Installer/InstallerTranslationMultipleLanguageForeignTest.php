<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerTranslationMultipleLanguageForeignTest.
 */

namespace Drupal\system\Tests\Installer;

/**
 * Tests translation files for multiple languages get imported during install.
 *
 * @group Installer
 */
class InstallerTranslationMultipleLanguageForeignTest extends InstallerTranslationMultipleLanguageTest {

  /**
   * Overrides the language code in which to install Drupal.
   *
   * @var string
   */
  protected $langcode = 'de';

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    parent::setUpLanguage();
    $this->translations['Save and continue'] = 'Save and continue de';
  }

}
