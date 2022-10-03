<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the import of locale files.
 *
 * @group locale
 */
class LocaleImportFunctionalTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['locale', 'dblog'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user able to create languages and import translations.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * A user able to create languages, import translations, access site reports.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUserAccessSiteReports;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Copy test po files to the translations directory.
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $file_system->copy(__DIR__ . '/../../../tests/test.de.po', 'translations://', FileSystemInterface::EXISTS_REPLACE);
    $file_system->copy(__DIR__ . '/../../../tests/test.xx.po', 'translations://', FileSystemInterface::EXISTS_REPLACE);

    $this->adminUser = $this->drupalCreateUser([
      'administer languages',
      'translate interface',
      'access administration pages',
    ]);
    $this->adminUserAccessSiteReports = $this->drupalCreateUser([
      'administer languages',
      'translate interface',
      'access administration pages',
      'access site reports',
    ]);
    $this->drupalLogin($this->adminUser);

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_LOCAL)
      ->save();
  }

  /**
   * Tests import of standalone .po files.
   */
  public function testStandalonePoFile() {
    // Try importing a .po file.
    $this->importPoFile($this->getPoFile(), [
      'langcode' => 'fr',
    ]);
    $this->config('locale.settings');
    // The import should automatically create the corresponding language.
    $this->assertSession()->pageTextContains("The language French has been created.");

    // The import should have created 8 strings.
    $this->assertSession()->pageTextContains("One translation file imported. 8 translations were added, 0 translations were updated and 0 translations were removed.");

    // This import should have saved plural forms to have 2 variants.
    $locale_plurals = \Drupal::service('locale.plural.formula')->getNumberOfPlurals('fr');
    $this->assertEquals(2, $locale_plurals, 'Plural number initialized.');

    // Ensure we were redirected correctly.
    $this->assertSession()->addressEquals(Url::fromRoute('locale.translate_page'));

    // Try importing a .po file with invalid tags.
    $this->importPoFile($this->getBadPoFile(), [
      'langcode' => 'fr',
    ]);

    // The import should have created 1 string and rejected 2.
    $this->assertSession()->pageTextContains("One translation file imported. 1 translations were added, 0 translations were updated and 0 translations were removed.");
    $this->assertSession()->pageTextContains("2 translation strings were skipped because of disallowed or malformed HTML. See the log for details.");

    // Repeat the process with a user that can access site reports, and this
    // time the different warnings must contain links to the log.
    $this->drupalLogin($this->adminUserAccessSiteReports);

    // Try importing a .po file with invalid tags.
    $this->importPoFile($this->getBadPoFile(), [
      'langcode' => 'fr',
    ]);

    $this->assertSession()->pageTextContains("2 translation strings were skipped because of disallowed or malformed HTML. See the log for details.");

    // Check empty files import with a user that cannot access site reports..
    $this->drupalLogin($this->adminUser);
    // Try importing a zero byte sized .po file.
    $this->importPoFile($this->getEmptyPoFile(), [
      'langcode' => 'fr',
    ]);
    // The import should have created 0 string and rejected 0.
    $this->assertSession()->pageTextContains("One translation file could not be imported. See the log for details.");

    // Repeat the process with a user that can access site reports, and this
    // time the different warnings must contain links to the log.
    $this->drupalLogin($this->adminUserAccessSiteReports);
    // Try importing a zero byte sized .po file.
    $this->importPoFile($this->getEmptyPoFile(), [
      'langcode' => 'fr',
    ]);
    // The import should have created 0 string and rejected 0.
    $this->assertSession()->pageTextContains("One translation file could not be imported. See the log for details.");

    // Try importing a .po file which doesn't exist.
    $name = $this->randomMachineName(16);
    $this->drupalGet('admin/config/regional/translate/import');
    $this->submitForm([
      'langcode' => 'fr',
      'files[file]' => $name,
    ], 'Import');
    $this->assertSession()->addressEquals(Url::fromRoute('locale.translate_import'));
    $this->assertSession()->pageTextContains('File to import not found.');

    // Try importing a .po file with overriding strings, and ensure existing
    // strings are kept.
    $this->importPoFile($this->getOverwritePoFile(), [
      'langcode' => 'fr',
    ]);

    // The import should have created 1 string.
    $this->assertSession()->pageTextContains("One translation file imported. 1 translations were added, 0 translations were updated and 0 translations were removed.");
    // Ensure string wasn't overwritten.
    $search = [
      'string' => 'Montag',
      'langcode' => 'fr',
      'translation' => 'translated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains('No strings available.');

    // This import should not have changed number of plural forms.
    $locale_plurals = \Drupal::service('locale.plural.formula')->getNumberOfPlurals('fr');
    $this->assertEquals(2, $locale_plurals, 'Plural numbers untouched.');

    // Try importing a .po file with overriding strings, and ensure existing
    // strings are overwritten.
    $this->importPoFile($this->getOverwritePoFile(), [
      'langcode' => 'fr',
      'overwrite_options[not_customized]' => TRUE,
    ]);

    // The import should have updated 2 strings.
    $this->assertSession()->pageTextContains("One translation file imported. 0 translations were added, 2 translations were updated and 0 translations were removed.");
    // Ensure string was overwritten.
    $search = [
      'string' => 'Montag',
      'langcode' => 'fr',
      'translation' => 'translated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextNotContains('No strings available.');
    // This import should have changed number of plural forms.
    $locale_plurals = \Drupal::service('locale.plural.formula')->reset()->getNumberOfPlurals('fr');
    $this->assertEquals(3, $locale_plurals, 'Plural numbers changed.');

    // Importing a .po file and mark its strings as customized strings.
    $this->importPoFile($this->getCustomPoFile(), [
      'langcode' => 'fr',
      'customized' => TRUE,
    ]);

    // The import should have created 6 strings.
    $this->assertSession()->pageTextContains("One translation file imported. 6 translations were added, 0 translations were updated and 0 translations were removed.");

    // The database should now contain 6 customized strings (two imported
    // strings are not translated).
    $count = Database::getConnection()->select('locales_target')
      ->condition('customized', 1)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(6, $count, 'Customized translations successfully imported.');

    // Try importing a .po file with overriding strings, and ensure existing
    // customized strings are kept.
    $this->importPoFile($this->getCustomOverwritePoFile(), [
      'langcode' => 'fr',
      'overwrite_options[not_customized]' => TRUE,
      'overwrite_options[customized]' => FALSE,
    ]);

    // The import should have created 1 string.
    $this->assertSession()->pageTextContains("One translation file imported. 1 translations were added, 0 translations were updated and 0 translations were removed.");
    // Ensure string wasn't overwritten.
    $search = [
      'string' => 'januari',
      'langcode' => 'fr',
      'translation' => 'translated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains('No strings available.');

    // Try importing a .po file with overriding strings, and ensure existing
    // customized strings are overwritten.
    $this->importPoFile($this->getCustomOverwritePoFile(), [
      'langcode' => 'fr',
      'overwrite_options[not_customized]' => FALSE,
      'overwrite_options[customized]' => TRUE,
    ]);

    // The import should have updated 2 strings.
    $this->assertSession()->pageTextContains("One translation file imported. 0 translations were added, 2 translations were updated and 0 translations were removed.");
    // Ensure string was overwritten.
    $search = [
      'string' => 'januari',
      'langcode' => 'fr',
      'translation' => 'translated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextNotContains('No strings available.');

  }

  /**
   * Tests msgctxt context support.
   */
  public function testLanguageContext() {
    // Try importing a .po file.
    $this->importPoFile($this->getPoFileWithContext(), [
      'langcode' => 'hr',
    ]);

    // We cast the return value of t() to string so as to retrieve the
    // translated value, rendered as a string.
    $this->assertSame('Svibanj', (string) t('May', [], ['langcode' => 'hr', 'context' => 'Long month name']), 'Long month name context is working.');
    $this->assertSame('Svi.', (string) t('May', [], ['langcode' => 'hr']), 'Default context is working.');
  }

  /**
   * Tests empty msgstr at end of .po file see #611786.
   */
  public function testEmptyMsgstr() {
    $langcode = 'hu';

    // Try importing a .po file.
    $this->importPoFile($this->getPoFileWithMsgstr(), [
      'langcode' => $langcode,
    ]);

    $this->assertSession()->pageTextContains("One translation file imported. 1 translations were added, 0 translations were updated and 0 translations were removed.");
    $this->assertSame('Műveletek', (string) t('Operations', [], ['langcode' => $langcode]), 'String imported and translated.');

    // Try importing a .po file.
    $this->importPoFile($this->getPoFileWithEmptyMsgstr(), [
      'langcode' => $langcode,
      'overwrite_options[not_customized]' => TRUE,
    ]);
    $this->assertSession()->pageTextContains("One translation file imported. 0 translations were added, 0 translations were updated and 1 translations were removed.");

    $str = "Operations";
    $search = [
      'string' => $str,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    ];
    // Check that search finds the string as untranslated.
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains($str);
  }

  /**
   * Tests .po file import with configuration translation.
   */
  public function testConfigPoFile() {
    // Values for translations to assert. Config key, original string,
    // translation and config property name.
    $config_strings = [
      'system.maintenance' => [
        '@site is currently under maintenance. We should be back shortly. Thank you for your patience.',
        // cSpell:disable-next-line
        '@site karbantartás alatt áll. Rövidesen visszatérünk. Köszönjük a türelmet.',
        'message',
      ],
      'user.role.anonymous' => [
        'Anonymous user',
        // cSpell:disable-next-line
        'Névtelen felhasználó',
        'label',
      ],
    ];

    // Add custom language for testing.
    $langcode = 'xx';
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $this->randomMachineName(16),
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');

    // Check for the source strings we are going to translate. Adding the
    // custom language should have made the process to export configuration
    // strings to interface translation executed.
    $locale_storage = $this->container->get('locale.storage');
    foreach ($config_strings as $config_string) {
      $string = $locale_storage->findString(['source' => $config_string[0], 'context' => '', 'type' => 'configuration']);
      $this->assertNotEmpty($string, 'Configuration strings have been created upon installation.');
    }

    // Import a .po file to translate.
    $this->importPoFile($this->getPoFileWithConfig(), [
      'langcode' => $langcode,
    ]);

    // Translations got recorded in the interface translation system.
    foreach ($config_strings as $config_string) {
      $search = [
        'string' => $config_string[0],
        'langcode' => $langcode,
        'translation' => 'all',
      ];
      $this->drupalGet('admin/config/regional/translate');
      $this->submitForm($search, 'Filter');
      $this->assertSession()->pageTextContains($config_string[1]);
    }

    // Test that translations got recorded in the config system.
    $overrides = \Drupal::service('language.config_factory_override');
    foreach ($config_strings as $config_key => $config_string) {
      $override = $overrides->getOverride($langcode, $config_key);
      $this->assertEquals($override->get($config_string[2]), $config_string[1]);
    }
  }

  /**
   * Tests .po file import with user.settings configuration.
   */
  public function testConfigTranslationImportingPoFile() {
    // Set the language code.
    $langcode = 'de';

    // Import a .po file to translate.
    $this->importPoFile($this->getPoFileWithConfigDe(), [
      'langcode' => $langcode,
    ]);

    // Check that the 'Anonymous' string is translated.
    $config = \Drupal::languageManager()->getLanguageConfigOverride($langcode, 'user.settings');
    $this->assertEquals('Anonymous German', $config->get('anonymous'));
  }

  /**
   * Tests the translation are imported when a new language is created.
   */
  public function testCreatedLanguageTranslation() {
    // Import a .po file to add de language.
    $this->importPoFile($this->getPoFileWithConfigDe(), ['langcode' => 'de']);

    // Get the language.entity.de label and check it's been translated.
    $override = \Drupal::languageManager()->getLanguageConfigOverride('de', 'language.entity.de');
    $this->assertEquals('Deutsch', $override->get('label'));
  }

  /**
   * Helper function: import a standalone .po file in a given language.
   *
   * @param string $contents
   *   Contents of the .po file to import.
   * @param array $options
   *   (optional) Additional options to pass to the translation import form.
   */
  public function importPoFile($contents, array $options = []) {
    $file_system = \Drupal::service('file_system');
    $name = $file_system->tempnam('temporary://', "po_") . '.po';
    file_put_contents($name, $contents);
    $options['files[file]'] = $name;
    $this->drupalGet('admin/config/regional/translate/import');
    $this->submitForm($options, 'Import');
    $file_system->unlink($name);
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
   * Helper function that returns an empty .po file.
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
