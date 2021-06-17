<?php

namespace Drupal\Tests\config_translation\Functional;

use Drupal\FunctionalTests\Installer\InstallerTestBase;

/**
 * Installs the config translation module on a site installed in non english.
 *
 * @group config_translation
 */
class ConfigTranslationInstallTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $langcode = 'eo';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    // Place custom local translations in the translations directory.
    mkdir(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.eo.po', $this->getPo('eo'));

    parent::setUpLanguage();

    $this->translations['Save and continue'] = 'Save and continue eo';
  }

  /**
   * Returns the string for the test .po file.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   Contents for the test .po file.
   */
  protected function getPo($langcode) {
    return <<<ENDPO
msgid ""
msgstr ""

msgid "Save and continue"
msgstr "Save and continue $langcode"

msgid "Anonymous"
msgstr "Anonymous $langcode"

msgid "Language"
msgstr "Language $langcode"
ENDPO;
  }

  public function testConfigTranslation() {
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm(['predefined_langcode' => 'en'], 'Add custom language');
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm(['predefined_langcode' => 'fr'], 'Add custom language');

    $edit = [
      'modules[config_translation][enable]' => TRUE,
    ];
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');

    $this->drupalGet('/admin/structure/types/manage/article/fields');
    $this->assertSession()->statusCodeEquals(200);
  }

}
