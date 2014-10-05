<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerTranslationMultipleLanguageForeignTest.
 */

namespace Drupal\system\Tests\Installer;
use Drupal\simpletest\InstallerTestBase;

/**
 * Tests translation files for multiple languages get imported during install.
 *
 * @group Installer
 */
class InstallerTranslationMultipleLanguageKeepEnglishTest extends InstallerTestBase {

  /**
   * Overrides the language code in which to install Drupal.
   *
   * @var string
   */
  protected $langcode = 'de';

  /**
   * Switch to the multilingual testing profile
   *
   * @var string
   */
  protected $profile = 'testing_multilingual';

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    // Place custom local translations in the translations directory.
    mkdir(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.de.po', "msgid \"\"\nmsgstr \"\"\nmsgid \"Save and continue\"\nmsgstr \"Save and continue German\"");
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.es.po', "msgid \"\"\nmsgstr \"\"\nmsgid \"Save and continue\"\nmsgstr \"Save and continue Spanish\"");

    parent::setUpLanguage();
    $this->translations['Save and continue'] = 'Save and continue German';
  }

  /**
   * Tests that English is still present.
   */
  public function testKeepEnglish() {
    $this->assertTrue((bool) \Drupal::languageManager()->getLanguage('en'), 'English is present.');
  }

}
