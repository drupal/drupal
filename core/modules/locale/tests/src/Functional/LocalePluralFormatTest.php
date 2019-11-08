<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\Component\Gettext\PoItem;
use Drupal\Core\Database\Database;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests plural handling for various languages.
 *
 * @group locale
 */
class LocalePluralFormatTest extends BrowserTestBase {

  /**
   * An admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['locale'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer languages', 'translate interface', 'access administration pages']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests locale_get_plural() and \Drupal::translation()->formatPlural()
   * functionality.
   */
  public function testGetPluralFormat() {
    // Import some .po files with formulas to set up the environment.
    // These will also add the languages to the system.
    $this->importPoFile($this->getPoFileWithSimplePlural(), [
      'langcode' => 'fr',
    ]);
    $this->importPoFile($this->getPoFileWithComplexPlural(), [
      'langcode' => 'hr',
    ]);

    // Attempt to import some broken .po files as well to prove that these
    // will not overwrite the proper plural formula imported above.
    $this->importPoFile($this->getPoFileWithMissingPlural(), [
      'langcode' => 'fr',
      'overwrite_options[not_customized]' => TRUE,
    ]);
    $this->importPoFile($this->getPoFileWithBrokenPlural(), [
      'langcode' => 'hr',
      'overwrite_options[not_customized]' => TRUE,
    ]);

    // Reset static caches from locale_get_plural() to ensure we get fresh data.
    drupal_static_reset('locale_get_plural');
    drupal_static_reset('locale_get_plural:plurals');
    drupal_static_reset('locale');

    // Expected plural translation strings for each plural index.
    $plural_strings = [
      // English is not imported in this case, so we assume built-in text
      // and formulas.
      'en' => [
        0 => '1 hour',
        1 => '@count hours',
      ],
      'fr' => [
        0 => '@count heure',
        1 => '@count heures',
      ],
      'hr' => [
        0 => '@count sat',
        1 => '@count sata',
        2 => '@count sati',
      ],
      // Hungarian is not imported, so it should assume the same text as
      // English, but it will always pick the plural form as per the built-in
      // logic, so only index -1 is relevant with the plural value.
      'hu' => [
        0 => '1 hour',
        -1 => '@count hours',
      ],
    ];

    // Expected plural indexes precomputed base on the plural formulas with
    // given $count value.
    $plural_tests = [
      'en' => [
        1 => 0,
        0 => 1,
        5 => 1,
        123 => 1,
        235 => 1,
      ],
      'fr' => [
        1 => 0,
        0 => 0,
        5 => 1,
        123 => 1,
        235 => 1,
      ],
      'hr' => [
        1 => 0,
        21 => 0,
        0 => 2,
        2 => 1,
        8 => 2,
        123 => 1,
        235 => 2,
      ],
      'hu' => [
        1 => -1,
        21 => -1,
        0 => -1,
      ],
    ];

    foreach ($plural_tests as $langcode => $tests) {
      foreach ($tests as $count => $expected_plural_index) {
        // Assert that the we get the right plural index.
        $this->assertIdentical(locale_get_plural($count, $langcode), $expected_plural_index, 'Computed plural index for ' . $langcode . ' for count ' . $count . ' is ' . $expected_plural_index);
        // Assert that the we get the right translation for that. Change the
        // expected index as per the logic for translation lookups.
        $expected_plural_index = ($count == 1) ? 0 : $expected_plural_index;
        $expected_plural_string = str_replace('@count', $count, $plural_strings[$langcode][$expected_plural_index]);
        $this->assertIdentical(\Drupal::translation()->formatPlural($count, '1 hour', '@count hours', [], ['langcode' => $langcode])->render(), $expected_plural_string, 'Plural translation of 1 hours / @count hours for count ' . $count . ' in ' . $langcode . ' is ' . $expected_plural_string);
        // DO NOT use translation to pass translated strings into
        // PluralTranslatableMarkup::createFromTranslatedString() this way. It
        // is designed to be used with *already* translated text like settings
        // from configuration. We use PHP translation here just because we have
        // the expected result data in that format.
        $translated_string = \Drupal::translation()->translate('1 hour' . PoItem::DELIMITER . '@count hours', [], ['langcode' => $langcode]);
        $plural = PluralTranslatableMarkup::createFromTranslatedString($count, $translated_string, [], ['langcode' => $langcode]);
        $this->assertIdentical($plural->render(), $expected_plural_string);
      }
    }
  }

  /**
   * Tests plural editing of DateFormatter strings
   */
  public function testPluralEditDateFormatter() {

    // Import some .po files with formulas to set up the environment.
    // These will also add the languages to the system.
    $this->importPoFile($this->getPoFileWithSimplePlural(), [
      'langcode' => 'fr',
    ]);

    // Set French as the site default language.
    $this->config('system.site')->set('default_langcode', 'fr')->save();

    // Visit User Info page before updating translation strings. Change the
    // created time to ensure that the we're dealing in seconds and it can't be
    // exactly 1 minute.
    $this->adminUser->set('created', time() - 1)->save();
    $this->drupalGet('user');

    // Member for time should be translated.
    $this->assertText("seconde", "'Member for' text is translated.");

    $path = 'admin/config/regional/translate/';
    $search = [
      'langcode' => 'fr',
      // Limit to only translated strings to ensure that database ordering does
      // not break the test.
      'translation' => 'translated',
    ];
    $this->drupalPostForm($path, $search, t('Filter'));
    // Plural values for the langcode fr.
    $this->assertText('@count seconde');
    $this->assertText('@count secondes');

    // Inject a plural source string to the database. We need to use a specific
    // langcode here because the language will be English by default and will
    // not save our source string for performance optimization if we do not ask
    // specifically for a language.
    \Drupal::translation()->formatPlural(1, '1 second', '@count seconds', [], ['langcode' => 'fr'])->render();
    $lid = Database::getConnection()->query("SELECT lid FROM {locales_source} WHERE source = :source AND context = ''", [':source' => "1 second" . PoItem::DELIMITER . "@count seconds"])->fetchField();
    // Look up editing page for this plural string and check fields.
    $search = [
      'string' => '1 second',
      'langcode' => 'fr',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));

    // Save complete translations for the string in langcode fr.
    $edit = [
      "strings[$lid][translations][0]" => '1 seconde updated',
      "strings[$lid][translations][1]" => '@count secondes updated',
    ];
    $this->drupalPostForm($path, $edit, t('Save translations'));

    // User interface input for translating seconds should not be duplicated
    $this->assertUniqueText('@count seconds', 'Interface translation input for @count seconds only appears once.');

    // Member for time should be translated. Change the created time to ensure
    // that the we're dealing in multiple seconds and it can't be exactly 1
    // second or minute.
    $this->adminUser->set('created', time() - 2)->save();
    $this->drupalGet('user');
    $this->assertText("secondes updated", "'Member for' text is translated.");
  }

  /**
   * Tests plural editing and export functionality.
   */
  public function testPluralEditExport() {
    // Import some .po files with formulas to set up the environment.
    // These will also add the languages to the system.
    $this->importPoFile($this->getPoFileWithSimplePlural(), [
      'langcode' => 'fr',
    ]);
    $this->importPoFile($this->getPoFileWithComplexPlural(), [
      'langcode' => 'hr',
    ]);

    // Get the French translations.
    $this->drupalPostForm('admin/config/regional/translate/export', [
      'langcode' => 'fr',
    ], t('Export'));
    // Ensure we have a translation file.
    $this->assertRaw('# French translation of Drupal', 'Exported French translation file.');
    // Ensure our imported translations exist in the file.
    $this->assertRaw("msgid \"Monday\"\nmsgstr \"lundi\"", 'French translations present in exported file.');
    // Check for plural export specifically.
    $this->assertRaw("msgid \"1 hour\"\nmsgid_plural \"@count hours\"\nmsgstr[0] \"@count heure\"\nmsgstr[1] \"@count heures\"", 'Plural translations exported properly.');

    // Get the Croatian translations.
    $this->drupalPostForm('admin/config/regional/translate/export', [
      'langcode' => 'hr',
    ], t('Export'));
    // Ensure we have a translation file.
    $this->assertRaw('# Croatian translation of Drupal', 'Exported Croatian translation file.');
    // Ensure our imported translations exist in the file.
    $this->assertRaw("msgid \"Monday\"\nmsgstr \"Ponedjeljak\"", 'Croatian translations present in exported file.');
    // Check for plural export specifically.
    $this->assertRaw("msgid \"1 hour\"\nmsgid_plural \"@count hours\"\nmsgstr[0] \"@count sat\"\nmsgstr[1] \"@count sata\"\nmsgstr[2] \"@count sati\"", 'Plural translations exported properly.');

    // Check if the source appears on the translation page.
    $this->drupalGet('admin/config/regional/translate');
    $this->assertText("1 hour");
    $this->assertText("@count hours");

    // Look up editing page for this plural string and check fields.
    $path = 'admin/config/regional/translate/';
    $search = [
      'langcode' => 'hr',
    ];
    $this->drupalPostForm($path, $search, t('Filter'));
    // Labels for plural editing elements.
    $this->assertText('Singular form');
    $this->assertText('First plural form');
    $this->assertText('2. plural form');
    $this->assertNoText('3. plural form');

    // Plural values for langcode hr.
    $this->assertText('@count sat');
    $this->assertText('@count sata');
    $this->assertText('@count sati');

    $connection = Database::getConnection();
    // Edit langcode hr translations and see if that took effect.
    $lid = $connection->query("SELECT lid FROM {locales_source} WHERE source = :source AND context = ''", [':source' => "1 hour" . PoItem::DELIMITER . "@count hours"])->fetchField();
    $edit = [
      "strings[$lid][translations][1]" => '@count sata edited',
    ];
    $this->drupalPostForm($path, $edit, t('Save translations'));

    $search = [
      'langcode' => 'fr',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    // Plural values for the langcode fr.
    $this->assertText('@count heure');
    $this->assertText('@count heures');
    $this->assertNoText('2. plural form');

    // Edit langcode fr translations and see if that took effect.
    $edit = [
      "strings[$lid][translations][0]" => '@count heure edited',
    ];
    $this->drupalPostForm($path, $edit, t('Save translations'));

    // Inject a plural source string to the database. We need to use a specific
    // langcode here because the language will be English by default and will
    // not save our source string for performance optimization if we do not ask
    // specifically for a language.
    \Drupal::translation()->formatPlural(1, '1 day', '@count days', [], ['langcode' => 'fr'])->render();
    $lid = $connection->query("SELECT lid FROM {locales_source} WHERE source = :source AND context = ''", [':source' => "1 day" . PoItem::DELIMITER . "@count days"])->fetchField();
    // Look up editing page for this plural string and check fields.
    $search = [
      'string' => '1 day',
      'langcode' => 'fr',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));

    // Save complete translations for the string in langcode fr.
    $edit = [
      "strings[$lid][translations][0]" => '1 jour',
      "strings[$lid][translations][1]" => '@count jours',
    ];
    $this->drupalPostForm($path, $edit, t('Save translations'));

    // Save complete translations for the string in langcode hr.
    $search = [
      'string' => '1 day',
      'langcode' => 'hr',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));

    $edit = [
      "strings[$lid][translations][0]" => '@count dan',
      "strings[$lid][translations][1]" => '@count dana',
      "strings[$lid][translations][2]" => '@count dana',
    ];
    $this->drupalPostForm($path, $edit, t('Save translations'));

    // Get the French translations.
    $this->drupalPostForm('admin/config/regional/translate/export', [
      'langcode' => 'fr',
    ], t('Export'));
    // Check for plural export specifically.
    $this->assertRaw("msgid \"1 hour\"\nmsgid_plural \"@count hours\"\nmsgstr[0] \"@count heure edited\"\nmsgstr[1] \"@count heures\"", 'Edited French plural translations for hours exported properly.');
    $this->assertRaw("msgid \"1 day\"\nmsgid_plural \"@count days\"\nmsgstr[0] \"1 jour\"\nmsgstr[1] \"@count jours\"", 'Added French plural translations for days exported properly.');

    // Get the Croatian translations.
    $this->drupalPostForm('admin/config/regional/translate/export', [
      'langcode' => 'hr',
    ], t('Export'));
    // Check for plural export specifically.
    $this->assertRaw("msgid \"1 hour\"\nmsgid_plural \"@count hours\"\nmsgstr[0] \"@count sat\"\nmsgstr[1] \"@count sata edited\"\nmsgstr[2] \"@count sati\"", 'Edited Croatian plural translations exported properly.');
    $this->assertRaw("msgid \"1 day\"\nmsgid_plural \"@count days\"\nmsgstr[0] \"@count dan\"\nmsgstr[1] \"@count dana\"\nmsgstr[2] \"@count dana\"", 'Added Croatian plural translations exported properly.');
  }

  /**
   * Imports a standalone .po file in a given language.
   *
   * @param string $contents
   *   Contents of the .po file to import.
   * @param array $options
   *   Additional options to pass to the translation import form.
   */
  public function importPoFile($contents, array $options = []) {
    $file_system = \Drupal::service('file_system');
    $name = $file_system->tempnam('temporary://', "po_") . '.po';
    file_put_contents($name, $contents);
    $options['files[file]'] = $name;
    $this->drupalPostForm('admin/config/regional/translate/import', $options, t('Import'));
    $file_system->unlink($name);
  }

  /**
   * Returns a .po file with a simple plural formula.
   */
  public function getPoFileWithSimplePlural() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "1 hour"
msgid_plural "@count hours"
msgstr[0] "@count heure"
msgstr[1] "@count heures"

msgid "1 second"
msgid_plural "@count seconds"
msgstr[0] "@count seconde"
msgstr[1] "@count secondes"

msgid "Monday"
msgstr "lundi"
EOF;
  }

  /**
   * Returns a .po file with a complex plural formula.
   */
  public function getPoFileWithComplexPlural() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=3; plural=n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2;\\n"

msgid "1 hour"
msgid_plural "@count hours"
msgstr[0] "@count sat"
msgstr[1] "@count sata"
msgstr[2] "@count sati"

msgid "Monday"
msgstr "Ponedjeljak"
EOF;
  }

  /**
   * Returns a .po file with a missing plural formula.
   */
  public function getPoFileWithMissingPlural() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"

msgid "Monday"
msgstr "lundi"
EOF;
  }

  /**
   * Returns a .po file with a broken plural formula.
   */
  public function getPoFileWithBrokenPlural() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: broken, will not parse\\n"

msgid "Monday"
msgstr "Ponedjeljak"
EOF;
  }

}
