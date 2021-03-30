<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;

/**
 * Installs Drupal in German and checks resulting site.
 *
 * @group Installer
 */
class InstallerTranslationTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

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
    // Place a custom local translation in the translations directory.
    mkdir($this->root . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents($this->root . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.de.po', $this->getPo('de'));

    parent::setUpLanguage();

    // After selecting a different language than English, all following screens
    // should be translated already.
    $this->assertSession()->buttonExists('Save and continue de');
    $this->translations['Save and continue'] = 'Save and continue de';

    // Check the language direction.
    $direction = current($this->xpath('/@dir'))->getText();
    $this->assertEqual('ltr', $direction);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSettings() {
    // We are creating a table here to force an error in the installer because
    // it will try and create the drupal_install_test table as this is part of
    // the standard database tests performed by the installer in
    // Drupal\Core\Database\Install\Tasks.
    $spec = [
      'fields' => [
        'id' => [
          'type' => 'int',
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['id'],
    ];

    Database::getConnection('default')->schema()->createTable('drupal_install_test', $spec);
    parent::setUpSettings();

    // Ensure that the error message translation is working.
    // cSpell:disable
    $this->assertRaw('Beheben Sie alle Probleme unten, um die Installation fortzusetzen. Informationen zur Konfiguration der Datenbankserver finden Sie in der <a href="https://www.drupal.org/docs/8/install">Installationshandbuch</a>, oder kontaktieren Sie Ihren Hosting-Anbieter.');
    $this->assertRaw('<strong>CREATE</strong> ein Test-Tabelle auf Ihrem Datenbankserver mit dem Befehl <em class="placeholder">CREATE TABLE {drupal_install_test} (id int NOT NULL PRIMARY KEY)</em> fehlgeschlagen.');
    // cSpell:enable

    // Now do it successfully.
    Database::getConnection('default')->schema()->dropTable('drupal_install_test');
    parent::setUpSettings();
  }

  /**
   * Verifies the expected behaviors of the installation result.
   */
  public function testInstaller() {
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);

    // Verify German was configured but not English.
    $this->drupalGet('admin/config/regional/language');
    $this->assertText('German');
    $this->assertNoText('English');

    // The current container still has the english as current language, rebuild.
    $this->rebuildContainer();
    /** @var \Drupal\user\Entity\User $account */
    $account = User::load(0);
    $this->assertEqual('en', $account->language()->getId(), 'Anonymous user is English.');
    $account = User::load(1);
    $this->assertEqual('en', $account->language()->getId(), 'Administrator user is English.');
    $account = $this->drupalCreateUser();
    $this->assertEqual('de', $account->language()->getId(), 'New user is German.');

    // Ensure that we can enable basic_auth on a non-english site.
    $this->drupalPostForm('admin/modules', ['modules[basic_auth][enable]' => TRUE], 'Install');
    $this->assertSession()->statusCodeEquals(200);

    // Assert that the theme CSS was added to the page.
    $edit = ['preprocess_css' => FALSE];
    $this->drupalPostForm('admin/config/development/performance', $edit, 'Save configuration');
    $this->drupalGet('<front>');
    $this->assertRaw('classy/css/components/action-links.css');

    // Verify the strings from the translation files were imported.
    $test_samples = ['Save and continue', 'Anonymous'];
    foreach ($test_samples as $sample) {
      $edit = [];
      $edit['langcode'] = 'de';
      $edit['translation'] = 'translated';
      $edit['string'] = $sample;
      $this->drupalPostForm('admin/config/regional/translate', $edit, 'Filter');
      $this->assertText($sample . ' de');
    }

    /** @var \Drupal\language\ConfigurableLanguageManager $language_manager */
    $language_manager = \Drupal::languageManager();

    // Installed in German, configuration should be in German. No German or
    // English overrides should be present.
    $config = \Drupal::config('user.settings');
    $override_de = $language_manager->getLanguageConfigOverride('de', 'user.settings');
    $override_en = $language_manager->getLanguageConfigOverride('en', 'user.settings');
    $this->assertEqual('Anonymous de', $config->get('anonymous'));
    $this->assertEqual('de', $config->get('langcode'));
    $this->assertTrue($override_de->isNew());
    $this->assertTrue($override_en->isNew());

    // Assert that adding English makes the English override available.
    $edit = ['predefined_langcode' => 'en'];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, 'Add language');
    $override_en = $language_manager->getLanguageConfigOverride('en', 'user.settings');
    $this->assertFalse($override_en->isNew());
    $this->assertEqual('Anonymous', $override_en->get('anonymous'));
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

msgid "Resolve all issues below to continue the installation. For help configuring your database server, see the <a href="https://www.drupal.org/docs/8/install">installation handbook</a>, or contact your hosting provider."
msgstr "Beheben Sie alle Probleme unten, um die Installation fortzusetzen. Informationen zur Konfiguration der Datenbankserver finden Sie in der <a href="https://www.drupal.org/docs/8/install">Installationshandbuch</a>, oder kontaktieren Sie Ihren Hosting-Anbieter."

msgid "Failed to <strong>CREATE</strong> a test table on your database server with the command %query. The server reports the following message: %error.<p>Are you sure the configured username has the necessary permissions to create tables in the database?</p>"
msgstr "<strong>CREATE</strong> ein Test-Tabelle auf Ihrem Datenbankserver mit dem Befehl %query fehlgeschlagen."
ENDPO;
  }

}
