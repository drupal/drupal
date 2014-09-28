<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerTranslationMultipleLanguageTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;

/**
 * Tests translation files for multiple languages get imported during install.
 *
 * @group Installer
 */
class InstallerTranslationMultipleLanguageTest extends InstallerTestBase {

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
  }

  /**
   * Tests that translations for each language were loaded.
   */
  public function testTranslationsLoaded() {
    // Verify German and Spanish were configured.
    $this->drupalGet('admin/config/regional/language');
    $this->assertText('German');
    $this->assertText('Spanish');
    // If the installer was English, we expect that configured also.
    if ($this->langcode == 'en') {
      $this->assertText('English');
    }

    // Verify the strings from the translation files were imported.
    $edit = array();
    $edit['langcode'] = 'de';
    $edit['translation'] = 'translated';
    $edit['string'] = 'Save and continue';
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Filter'));
    $this->assertText('Save and continue German');
    $edit = array();
    $edit['langcode'] = 'es';
    $edit['translation'] = 'translated';
    $edit['string'] = 'Save and continue';
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Filter'));
    $this->assertText('Save and continue Spanish');
  }

}
