<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerTranslationTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;

/**
 * Selects German as the installation language and verifies the following page
 * is not in English.
 *
 * @group Installer
 */
class InstallerTranslationTest extends InstallerTestBase {

  /**
   * Overrides the language code in which to install Drupal.
   *
   * @var string
   */
  protected $langcode = 'de';

  /**
   * Overrides InstallerTest::setUpLanguage().
   */
  protected function setUpLanguage() {
    // Place a custom local translation in the translations directory.
    mkdir(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.de.po', "msgid \"\"\nmsgstr \"\"\nmsgid \"Save and continue\"\nmsgstr \"Save and continue German\"");

    parent::setUpLanguage();
    // After selecting a different language than English, all following screens
    // should be translated already.
    $elements = $this->xpath('//input[@type="submit"]/@value');
    $this->assertEqual((string) current($elements), 'Save and continue German');
    $this->translations['Save and continue'] = 'Save and continue German';

    // Check the language direction.
    $direction = (string) current($this->xpath('/html/@dir'));
    $this->assertEqual($direction, 'ltr');
  }

  /**
   * Verifies that installation succeeded.
   */
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);

    // Ensure that we can enable basic_auth on a non-english site.
    $this->drupalPostForm('admin/modules', array('modules[Web services][basic_auth][enable]' => TRUE), t('Save configuration'));
    $this->assertResponse(200);

    // Assert that the theme CSS was added to the page.
    $edit = array('preprocess_css' => FALSE);
    $this->drupalPostForm('admin/config/development/performance', $edit, t('Save configuration'));
    $this->drupalGet('<front>');
    $this->assertRaw('stark/css/layout.css');
  }

}
