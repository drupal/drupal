<?php

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the import of locale files.
 *
 * @group locale
 */
class LocaleImportFunctionalTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale', 'dblog');

  /**
   * A user able to create languages and import translations.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * A user able to create languages, import translations and access site
   * reports.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUserAccessSiteReports;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Copy test po files to the translations directory.
    file_unmanaged_copy(__DIR__ . '/../../tests/test.de.po', 'translations://', FILE_EXISTS_REPLACE);
    file_unmanaged_copy(__DIR__ . '/../../tests/test.xx.po', 'translations://', FILE_EXISTS_REPLACE);

    $this->adminUser = $this->drupalCreateUser(array('administer languages', 'translate interface', 'access administration pages'));
    $this->adminUserAccessSiteReports = $this->drupalCreateUser(array('administer languages', 'translate interface', 'access administration pages', 'access site reports'));
    $this->drupalLogin($this->adminUser);

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->save();
  }

  /**
   * Test import of standalone .po files.
   */
  public function testStandalonePoFile() {
    // Try importing a .po file.
    $this->importPoFile($this->getPoFile(), array(
      'langcode' => 'fr',
    ));
    $this->config('locale.settings');
    // The import should automatically create the corresponding language.
    $this->assertRaw(t('The language %language has been created.', array('%language' => 'French')), 'The language has been automatically created.');

    // The import should have created 8 strings.
    $this->assertRaw(t('One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.', array('%number' => 8, '%update' => 0, '%delete' => 0)), 'The translation file was successfully imported.');

    // This import should have saved plural forms to have 2 variants.
    $locale_plurals = \Drupal::service('locale.plural.formula')->getNumberOfPlurals('fr');
    $this->assertEqual(2, $locale_plurals, 'Plural number initialized.');

    // Ensure we were redirected correctly.
    $this->assertUrl(\Drupal::url('locale.translate_page', [], ['absolute' => TRUE]), [], 'Correct page redirection.');

    // Try importing a .po file with invalid tags.
    $this->importPoFile($this->getBadPoFile(), array(
      'langcode' => 'fr',
    ));

    // The import should have created 1 string and rejected 2.
    $this->assertRaw(t('One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.', array('%number' => 1, '%update' => 0, '%delete' => 0)), 'The translation file was successfully imported.');

    $skip_message = \Drupal::translation()->formatPlural(2, 'One translation string was skipped because of disallowed or malformed HTML. <a href=":url">See the log</a> for details.', '@count translation strings were skipped because of disallowed or malformed HTML. See the log for details.', array(':url' => \Drupal::url('dblog.overview')));
    $this->assertRaw($skip_message, 'Unsafe strings were skipped.');

    // Repeat the process with a user that can access site reports, and this
    // time the different warnings must contain links to the log.
    $this->drupalLogin($this->adminUserAccessSiteReports);

    // Try importing a .po file with invalid tags.
    $this->importPoFile($this->getBadPoFile(), array(
      'langcode' => 'fr',
    ));

    $skip_message = \Drupal::translation()->formatPlural(2, 'One translation string was skipped because of disallowed or malformed HTML. <a href=":url">See the log</a> for details.', '@count translation strings were skipped because of disallowed or malformed HTML. <a href=":url">See the log</a> for details.', array(':url' => \Drupal::url('dblog.overview')));
    $this->assertRaw($skip_message, 'Unsafe strings were skipped.');

    // Check empty files import with a user that cannot access site reports..
    $this->drupalLogin($this->adminUser);
    // Try importing a zero byte sized .po file.
    $this->importPoFile($this->getEmptyPoFile(), array(
      'langcode' => 'fr',
    ));
    // The import should have created 0 string and rejected 0.
    $this->assertRaw(t('One translation file could not be imported. See the log for details.'), 'The empty translation file import reported no translations imported.');

    // Repeat the process with a user that can access site reports, and this
    // time the different warnings must contain links to the log.
    $this->drupalLogin($this->adminUserAccessSiteReports);
    // Try importing a zero byte sized .po file.
    $this->importPoFile($this->getEmptyPoFile(), array(
      'langcode' => 'fr',
    ));
    // The import should have created 0 string and rejected 0.
    $this->assertRaw(t('One translation file could not be imported. <a href=":url">See the log</a> for details.', array(':url' => \Drupal::url('dblog.overview'))), 'The empty translation file import reported no translations imported.');

    // Try importing a .po file which doesn't exist.
    $name = $this->randomMachineName(16);
    $this->drupalPostForm('admin/config/regional/translate/import', array(
      'langcode' => 'fr',
      'files[file]' => $name,
    ), t('Import'));
    $this->assertUrl(\Drupal::url('locale.translate_import', [], ['absolute' => TRUE]), [], 'Correct page redirection.');
    $this->assertText(t('File to import not found.'), 'File to import not found message.');

    // Try importing a .po file with overriding strings, and ensure existing
    // strings are kept.
    $this->importPoFile($this->getOverwritePoFile(), array(
      'langcode' => 'fr',
    ));

    // The import should have created 1 string.
    $this->assertRaw(t('One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.', array('%number' => 1, '%update' => 0, '%delete' => 0)), 'The translation file was successfully imported.');
    // Ensure string wasn't overwritten.
    $search = array(
      'string' => 'Montag',
      'langcode' => 'fr',
      'translation' => 'translated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), 'String not overwritten by imported string.');

    // This import should not have changed number of plural forms.
    $locale_plurals = \Drupal::service('locale.plural.formula')->getNumberOfPlurals('fr');
    $this->assertEqual(2, $locale_plurals, 'Plural numbers untouched.');

    // Try importing a .po file with overriding strings, and ensure existing
    // strings are overwritten.
    $this->importPoFile($this->getOverwritePoFile(), array(
      'langcode' => 'fr',
      'overwrite_options[not_customized]' => TRUE,
    ));

    // The import should have updated 2 strings.
    $this->assertRaw(t('One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.', array('%number' => 0, '%update' => 2, '%delete' => 0)), 'The translation file was successfully imported.');
    // Ensure string was overwritten.
    $search = array(
      'string' => 'Montag',
      'langcode' => 'fr',
      'translation' => 'translated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), 'String overwritten by imported string.');
    // This import should have changed number of plural forms.
    $locale_plurals = \Drupal::service('locale.plural.formula')->reset()->getNumberOfPlurals('fr');
    $this->assertEqual(3, $locale_plurals, 'Plural numbers changed.');

    // Importing a .po file and mark its strings as customized strings.
    $this->importPoFile($this->getCustomPoFile(), array(
      'langcode' => 'fr',
      'customized' => TRUE,
    ));

    // The import should have created 6 strings.
    $this->assertRaw(t('One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.', array('%number' => 6, '%update' => 0, '%delete' => 0)), 'The customized translation file was successfully imported.');

    // The database should now contain 6 customized strings (two imported
    // strings are not translated).
    $count = db_query('SELECT COUNT(*) FROM {locales_target} WHERE customized = :custom', array(':custom' => 1))->fetchField();
    $this->assertEqual($count, 6, 'Customized translations successfully imported.');

    // Try importing a .po file with overriding strings, and ensure existing
    // customized strings are kept.
    $this->importPoFile($this->getCustomOverwritePoFile(), array(
      'langcode' => 'fr',
      'overwrite_options[not_customized]' => TRUE,
      'overwrite_options[customized]' => FALSE,
    ));

    // The import should have created 1 string.
    $this->assertRaw(t('One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.', array('%number' => 1, '%update' => 0, '%delete' => 0)), 'The customized translation file was successfully imported.');
    // Ensure string wasn't overwritten.
    $search = array(
      'string' => 'januari',
      'langcode' => 'fr',
      'translation' => 'translated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), 'Customized string not overwritten by imported string.');

    // Try importing a .po file with overriding strings, and ensure existing
    // customized strings are overwritten.
    $this->importPoFile($this->getCustomOverwritePoFile(), array(
      'langcode' => 'fr',
      'overwrite_options[not_customized]' => FALSE,
      'overwrite_options[customized]' => TRUE,
    ));

    // The import should have updated 2 strings.
    $this->assertRaw(t('One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.', array('%number' => 0, '%update' => 2, '%delete' => 0)), 'The customized translation file was successfully imported.');
    // Ensure string was overwritten.
    $search = array(
      'string' => 'januari',
      'langcode' => 'fr',
      'translation' => 'translated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), 'Customized string overwritten by imported string.');

  }

  /**
   * Test msgctxt context support.
   */
  public function testLanguageContext() {
    // Try importing a .po file.
    $this->importPoFile($this->getPoFileWithContext(), array(
      'langcode' => 'hr',
    ));

    // We cast the return value of t() to string so as to retrieve the
    // translated value, rendered as a string.
    $this->assertIdentical((string) t('May', array(), array('langcode' => 'hr', 'context' => 'Long month name')), 'Svibanj', 'Long month name context is working.');
    $this->assertIdentical((string) t('May', array(), array('langcode' => 'hr')), 'Svi.', 'Default context is working.');
  }

  /**
   * Test empty msgstr at end of .po file see #611786.
   */
  public function testEmptyMsgstr() {
    $langcode = 'hu';

    // Try importing a .po file.
    $this->importPoFile($this->getPoFileWithMsgstr(), array(
      'langcode' => $langcode,
    ));

    $this->assertRaw(t('One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.', array('%number' => 1, '%update' => 0, '%delete' => 0)), 'The translation file was successfully imported.');
    $this->assertIdentical((string) t('Operations', array(), array('langcode' => $langcode)), 'Műveletek', 'String imported and translated.');

    // Try importing a .po file.
    $this->importPoFile($this->getPoFileWithEmptyMsgstr(), array(
      'langcode' => $langcode,
      'overwrite_options[not_customized]' => TRUE,
    ));
    $this->assertRaw(t('One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.', array('%number' => 0, '%update' => 0, '%delete' => 1)), 'The translation file was successfully imported.');

    $str = "Operations";
    $search = array(
      'string' => $str,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText($str, 'Search found the string as untranslated.');
  }

  /**
   * Tests .po file import with configuration translation.
   */
  public function testConfigPoFile() {
    // Values for translations to assert. Config key, original string,
    // translation and config property name.
    $config_strings = array(
      'system.maintenance' => array(
        '@site is currently under maintenance. We should be back shortly. Thank you for your patience.',
        '@site karbantartás alatt áll. Rövidesen visszatérünk. Köszönjük a türelmet.',
        'message',
      ),
      'user.role.anonymous' => array(
        'Anonymous user',
        'Névtelen felhasználó',
        'label',
      ),
    );

    // Add custom language for testing.
    $langcode = 'xx';
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $this->randomMachineName(16),
      'direction' => LanguageInterface::DIRECTION_LTR,
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Check for the source strings we are going to translate. Adding the
    // custom language should have made the process to export configuration
    // strings to interface translation executed.
    $locale_storage = $this->container->get('locale.storage');
    foreach ($config_strings as $config_string) {
      $string = $locale_storage->findString(array('source' => $config_string[0], 'context' => '', 'type' => 'configuration'));
      $this->assertTrue($string, 'Configuration strings have been created upon installation.');
    }

    // Import a .po file to translate.
    $this->importPoFile($this->getPoFileWithConfig(), array(
      'langcode' => $langcode,
    ));

    // Translations got recorded in the interface translation system.
    foreach ($config_strings as $config_string) {
      $search = array(
        'string' => $config_string[0],
        'langcode' => $langcode,
        'translation' => 'all',
      );
      $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
      $this->assertText($config_string[1], format_string('Translation of @string found.', array('@string' => $config_string[0])));
    }

    // Test that translations got recorded in the config system.
    $overrides = \Drupal::service('language.config_factory_override');
    foreach ($config_strings as $config_key => $config_string) {
      $override = $overrides->getOverride($langcode, $config_key);
      $this->assertEqual($override->get($config_string[2]), $config_string[1]);
    }
  }

  /**
   * Tests .po file import with user.settings configuration.
   */
  public function testConfigtranslationImportingPoFile() {
    // Set the language code.
    $langcode = 'de';

    // Import a .po file to translate.
    $this->importPoFile($this->getPoFileWithConfigDe(), array(
      'langcode' => $langcode));

    // Check that the 'Anonymous' string is translated.
    $config = \Drupal::languageManager()->getLanguageConfigOverride($langcode, 'user.settings');
    $this->assertEqual($config->get('anonymous'), 'Anonymous German');
  }

  /**
   * Test the translation are imported when a new language is created.
   */
  public function testCreatedLanguageTranslation() {
    // Import a .po file to add de language.
    $this->importPoFile($this->getPoFileWithConfigDe(), array('langcode' => 'de'));

    // Get the language.entity.de label and check it's been translated.
    $override = \Drupal::languageManager()->getLanguageConfigOverride('de', 'language.entity.de');
    $this->assertEqual($override->get('label'), 'Deutsch');
  }

  /**
   * Helper function: import a standalone .po file in a given language.
   *
   * @param string $contents
   *   Contents of the .po file to import.
   * @param array $options
   *   (optional) Additional options to pass to the translation import form.
   */
  public function importPoFile($contents, array $options = array()) {
    $name = tempnam('temporary://', "po_") . '.po';
    file_put_contents($name, $contents);
    $options['files[file]'] = $name;
    $this->drupalPostForm('admin/config/regional/translate/import', $options, t('Import'));
    drupal_unlink($name);
  }

  /**
   * Helper function that returns a proper .po file.
   */
  public function getPoFile() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "One sheep"
msgid_plural "@count sheep"
msgstr[0] "un mouton"
msgstr[1] "@count moutons"

msgid "Monday"
msgstr "lundi"

msgid "Tuesday"
msgstr "mardi"

msgid "Wednesday"
msgstr "mercredi"

msgid "Thursday"
msgstr "jeudi"

msgid "Friday"
msgstr "vendredi"

msgid "Saturday"
msgstr "samedi"

msgid "Sunday"
msgstr "dimanche"
EOF;
  }

  /**
   * Helper function that returns a empty .po file.
   */
  public function getEmptyPoFile() {
    return '';
  }

  /**
   * Helper function that returns a bad .po file.
   */
  public function getBadPoFile() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "Save configuration"
msgstr "Enregistrer la configuration"

msgid "edit"
msgstr "modifier<img SRC="javascript:alert(\'xss\');">"

msgid "delete"
msgstr "supprimer<script>alert('xss');</script>"

EOF;
  }

  /**
   * Helper function that returns a proper .po file for testing.
   */
  public function getOverwritePoFile() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=3; plural=n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2;\\n"

msgid "Monday"
msgstr "Montag"

msgid "Day"
msgstr "Jour"
EOF;
  }

  /**
   * Helper function that returns a .po file which strings will be marked
   * as customized.
   */
  public function getCustomPoFile() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "One dog"
msgid_plural "@count dogs"
msgstr[0] "un chien"
msgstr[1] "@count chiens"

msgid "January"
msgstr "janvier"

msgid "February"
msgstr "février"

msgid "March"
msgstr "mars"

msgid "April"
msgstr "avril"

msgid "June"
msgstr "juin"
EOF;
  }

  /**
   * Helper function that returns a .po file for testing customized strings.
   */
  public function getCustomOverwritePoFile() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "January"
msgstr "januari"

msgid "February"
msgstr "februari"

msgid "July"
msgstr "juillet"
EOF;
  }

  /**
   * Helper function that returns a .po file with context.
   */
  public function getPoFileWithContext() {
    // Croatian (code hr) is one of the languages that have a different
    // form for the full name and the abbreviated name for the month of May.
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=3; plural=n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2;\\n"

msgctxt "Long month name"
msgid "May"
msgstr "Svibanj"

msgid "May"
msgstr "Svi."
EOF;
  }

  /**
   * Helper function that returns a .po file with an empty last item.
   */
  public function getPoFileWithEmptyMsgstr() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "Operations"
msgstr ""

EOF;
  }

  /**
   * Helper function that returns a .po file with an empty last item.
   */
  public function getPoFileWithMsgstr() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "Operations"
msgstr "Műveletek"

msgid "Will not appear in Drupal core, so we can ensure the test passes"
msgstr ""

EOF;
  }

  /**
   * Helper function that returns a .po file with configuration translations.
   */
  public function getPoFileWithConfig() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "@site is currently under maintenance. We should be back shortly. Thank you for your patience."
msgstr "@site karbantartás alatt áll. Rövidesen visszatérünk. Köszönjük a türelmet."

msgid "Anonymous user"
msgstr "Névtelen felhasználó"

EOF;
  }

  /**
   * Helper function that returns a .po file with configuration translations.
   */
  public function getPoFileWithConfigDe() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "Anonymous"
msgstr "Anonymous German"

msgid "German"
msgstr "Deutsch"

EOF;
  }
}
