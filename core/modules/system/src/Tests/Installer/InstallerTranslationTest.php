<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerTranslationTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;
use Drupal\user\Entity\User;

/**
 * Installs Drupal in German and checks resulting site.
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
    mkdir(\Drupal::root() . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents(\Drupal::root() . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.de.po', $this->getPo('de'));

    parent::setUpLanguage();

    // After selecting a different language than English, all following screens
    // should be translated already.
    $elements = $this->xpath('//input[@type="submit"]/@value');
    $this->assertEqual((string) current($elements), 'Save and continue de');
    $this->translations['Save and continue'] = 'Save and continue de';

    // Check the language direction.
    $direction = (string) current($this->xpath('/html/@dir'));
    $this->assertEqual($direction, 'ltr');
  }

  /**
   * Verifies the expected behaviors of the installation result.
   */
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);

    // Verify German was configured but not English.
    $this->drupalGet('admin/config/regional/language');
    $this->assertText('German');
    $this->assertNoText('English');

    // The current container still has the english as current language, rebuild.
    $this->rebuildContainer();
    /** @var \Drupal\user\Entity\User $account */
    $account = User::load(0);
    $this->assertEqual($account->language()->getId(), 'en', 'Anonymous user is English.');
    $account = User::load(1);
    $this->assertEqual($account->language()->getId(), 'en', 'Administrator user is English.');
    $account = $this->drupalCreateUser();
    $this->assertEqual($account->language()->getId(), 'de', 'New user is German.');

    // Ensure that we can enable basic_auth on a non-english site.
    $this->drupalPostForm('admin/modules', array('modules[Web services][basic_auth][enable]' => TRUE), t('Install'));
    $this->assertResponse(200);

    // Assert that the theme CSS was added to the page.
    $edit = array('preprocess_css' => FALSE);
    $this->drupalPostForm('admin/config/development/performance', $edit, t('Save configuration'));
    $this->drupalGet('<front>');
    $this->assertRaw('classy/css/layout.css');

    // Verify the strings from the translation files were imported.
    $test_samples = ['Save and continue', 'Anonymous'];
    foreach($test_samples as $sample) {
      $edit = array();
      $edit['langcode'] = 'de';
      $edit['translation'] = 'translated';
      $edit['string'] = $sample;
      $this->drupalPostForm('admin/config/regional/translate', $edit, t('Filter'));
      $this->assertText($sample . ' de');
    }

    /** @var \Drupal\language\ConfigurableLanguageManager $language_manager */
    $language_manager = \Drupal::languageManager();

    // Installed in German, configuration should be in German. No German or
    // English overrides should be present.
    $config = \Drupal::config('user.settings');
    $override_de = $language_manager->getLanguageConfigOverride('de', 'user.settings');
    $override_en = $language_manager->getLanguageConfigOverride('en', 'user.settings');
    $this->assertEqual($config->get('anonymous'), 'Anonymous de');
    $this->assertEqual($config->get('langcode'), 'de');
    $this->assertTrue($override_de->isNew());
    $this->assertTrue($override_en->isNew());

    // Assert that adding English makes the English override available.
    $edit = ['predefined_langcode' => 'en'];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    $override_en = $language_manager->getLanguageConfigOverride('en', 'user.settings');
    $this->assertFalse($override_en->isNew());
    $this->assertEqual($override_en->get('anonymous'), 'Anonymous');
  }

  /**
   * Returns the string for the test .po file.
   *
   * @param string $langcode
   *   The language code.
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
ENDPO;
  }

}
