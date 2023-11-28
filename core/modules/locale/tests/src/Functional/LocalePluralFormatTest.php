<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\Component\Gettext\PoItem;
use Drupal\Core\Database\Database;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Tests\BrowserTestBase;

// cspell:ignore heure heures jours lundi ponedjeljak

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
  protected static $modules = ['locale'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer languages',
      'translate interface',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests locale_get_plural() and \Drupal::translation()->formatPlural().
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
        $this->assertSame($expected_plural_index, locale_get_plural($count, $langcode), 'Computed plural index for ' . $langcode . ' for count ' . $count . ' is ' . $expected_plural_index);
        // Assert that the we get the right translation for that. Change the
        // expected index as per the logic for translation lookups.
        $expected_plural_index = ($count == 1) ? 0 : $expected_plural_index;
        $expected_plural_string = str_replace('@count', $count, $plural_strings[$langcode][$expected_plural_index]);
        $this->assertSame($expected_plural_string, \Drupal::translation()->formatPlural($count, '@count hour', '@count hours', [], ['langcode' => $langcode])->render(), 'Plural translation of @count hour / @count hours for count ' . $count . ' in ' . $langcode . ' is ' . $expected_plural_string);
        // DO NOT use translation to pass translated strings into
        // PluralTranslatableMarkup::createFromTranslatedString() this way. It
        // is designed to be used with *already* translated text like settings
        // from configuration. We use PHP translation here just because we have
        // the expected result data in that format.
        $translated_string = \Drupal::translation()->translate('@count hour' . PoItem::DELIMITER . '@count hours', [], ['langcode' => $langcode]);
        $plural = PluralTranslatableMarkup::createFromTranslatedString($count, $translated_string, [], ['langcode' => $langcode]);
        $this->assertSame($expected_plural_string, $plural->render());
      }
    }
  }

  /**
   * Tests plural editing of DateFormatter strings.
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
    $this->assertSession()->pageTextContains("seconde");

    $path = 'admin/config/regional/translate/';
    $search = [
      'langcode' => 'fr',
      // Limit to only translated strings to ensure that database ordering does
      // not break the test.
      'translation' => 'translated',
    ];
    $this->drupalGet($path);
    $this->submitForm($search, 'Filter');
    // Plural values for the langcode fr.
    $this->assertSession()->pageTextContains('@count seconde');
    $this->assertSession()->pageTextContains('@count secondes');

    // Inject a plural source string to the database. We need to use a specific
    // langcode here because the language will be English by default and will
    // not save our source string for performance optimization if we do not ask
    // specifically for a language.
    \Drupal::translation()->formatPlural(1, '@count second', '@count seconds', [], ['langcode' => 'fr'])->render();
    $lid = Database::getConnection()->select('locales_source', 'ls')
      ->fields('ls', ['lid'])
      ->condition('source', "@count second" . PoItem::DELIMITER . "@count seconds")
      ->condition('context', '')
      ->execute()
      ->fetchField();
    // Look up editing page for this plural string and check fields.
    $search = [
      'string' => '@count second',
      'langcode' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');

    // Save complete translations for the string in langcode fr.
    $edit = [
      "strings[$lid][translations][0]" => '@count seconde updated',
      "strings[$lid][translations][1]" => '@count secondes updated',
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Save translations');

    // User interface input for translating seconds should not be duplicated
    $this->assertSession()->pageTextContainsOnce('@count seconds');

    // Member for time should be translated. Change the created time to ensure
    // that the we're dealing in multiple seconds and it can't be exactly 1
    // second or minute.
    $this->adminUser->set('created', time() - 2)->save();
    $this->drupalGet('user');
    $this->assertSession()->pageTextContains("secondes updated");
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
    $this->drupalGet('admin/config/regional/translate/export');
    $this->submitForm(['langcode' => 'fr'], 'Export');
    // Ensure we have a translation file.
    $this->assertSession()->pageTextContains('# French translation of Drupal');
    // Ensure our imported translations exist in the file.
    $this->assertSession()->responseContains("msgid \"Monday\"\nmsgstr \"lundi\"");
    // Check for plural export specifically.
    $this->assertSession()->responseContains("msgid \"@count hour\"\nmsgid_plural \"@count hours\"\nmsgstr[0] \"@count heure\"\nmsgstr[1] \"@count heures\"");

    // Get the Croatian translations.
    $this->drupalGet('admin/config/regional/translate/export');
    $this->submitForm(['langcode' => 'hr'], 'Export');
    // Ensure we have a translation file.
    $this->assertSession()->pageTextContains('# Croatian translation of Drupal');
    // Ensure our imported translations exist in the file.
    $this->assertSession()->responseContains("msgid \"Monday\"\nmsgstr \"Ponedjeljak\"");
    // Check for plural export specifically.
    $this->assertSession()->responseContains("msgid \"@count hour\"\nmsgid_plural \"@count hours\"\nmsgstr[0] \"@count sat\"\nmsgstr[1] \"@count sata\"\nmsgstr[2] \"@count sati\"");

    // Check if the source appears on the translation page.
    $this->drupalGet('admin/config/regional/translate');
    $this->assertSession()->pageTextContains("@count hour");
    $this->assertSession()->pageTextContains("@count hours");

    // Look up editing page for this plural string and check fields.
    $path = 'admin/config/regional/translate/';
    $search = [
      'langcode' => 'hr',
    ];
    $this->drupalGet($path);
    $this->submitForm($search, 'Filter');
    // Labels for plural editing elements.
    $this->assertSession()->pageTextContains('Singular form');
    $this->assertSession()->pageTextContains('First plural form');
    $this->assertSession()->pageTextContains('2. plural form');
    $this->assertSession()->pageTextNotContains('3. plural form');

    // Plural values for langcode hr.
    $this->assertSession()->pageTextContains('@count sat');
    $this->assertSession()->pageTextContains('@count sata');
    $this->assertSession()->pageTextContains('@count sati');

    $connection = Database::getConnection();
    // Edit langcode hr translations and see if that took effect.
    $lid = $connection->select('locales_source', 'ls')
      ->fields('ls', ['lid'])
      ->condition('source', "@count hour" . PoItem::DELIMITER . "@count hours")
      ->condition('context', '')
      ->execute()
      ->fetchField();
    $edit = [
      "strings[$lid][translations][1]" => '@count sata edited',
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Save translations');

    $search = [
      'langcode' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    // Plural values for the langcode fr.
    $this->assertSession()->pageTextContains('@count heure');
    $this->assertSession()->pageTextContains('@count heures');
    $this->assertSession()->pageTextNotContains('2. plural form');

    // Edit langcode fr translations and see if that took effect.
    $edit = [
      "strings[$lid][translations][0]" => '@count heure edited',
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Save translations');

    // Inject a plural source string to the database. We need to use a specific
    // langcode here because the language will be English by default and will
    // not save our source string for performance optimization if we do not ask
    // specifically for a language.
    \Drupal::translation()->formatPlural(1, '@count day', '@count days', [], ['langcode' => 'fr'])->render();
    $lid = $connection->select('locales_source', 'ls')
      ->fields('ls', ['lid'])
      ->condition('source', "@count day" . PoItem::DELIMITER . "@count days")
      ->condition('context', '')
      ->execute()
      ->fetchField();
    // Look up editing page for this plural string and check fields.
    $search = [
      'string' => '@count day',
      'langcode' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');

    // Save complete translations for the string in langcode fr.
    $edit = [
      "strings[$lid][translations][0]" => '@count jour',
      "strings[$lid][translations][1]" => '@count jours',
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Save translations');

    // Save complete translations for the string in langcode hr.
    $search = [
      'string' => '@count day',
      'langcode' => 'hr',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');

    $edit = [
      "strings[$lid][translations][0]" => '@count dan',
      "strings[$lid][translations][1]" => '@count dana',
      "strings[$lid][translations][2]" => '@count dana',
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Save translations');

    // Get the French translations.
    $this->drupalGet('admin/config/regional/translate/export');
    $this->submitForm(['langcode' => 'fr'], 'Export');
    // Check for plural export specifically.
    $this->assertSession()->responseContains("msgid \"@count hour\"\nmsgid_plural \"@count hours\"\nmsgstr[0] \"@count heure edited\"\nmsgstr[1] \"@count heures\"");
    $this->assertSession()->responseContains("msgid \"@count day\"\nmsgid_plural \"@count days\"\nmsgstr[0] \"@count jour\"\nmsgstr[1] \"@count jours\"");

    // Get the Croatian translations.
    $this->drupalGet('admin/config/regional/translate/export');
    $this->submitForm(['langcode' => 'hr'], 'Export');
    // Check for plural export specifically.
    $this->assertSession()->responseContains("msgid \"@count hour\"\nmsgid_plural \"@count hours\"\nmsgstr[0] \"@count sat\"\nmsgstr[1] \"@count sata edited\"\nmsgstr[2] \"@count sati\"");
    $this->assertSession()->responseContains("msgid \"@count day\"\nmsgid_plural \"@count days\"\nmsgstr[0] \"@count dan\"\nmsgstr[1] \"@count dana\"\nmsgstr[2] \"@count dana\"");
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
    $this->drupalGet('admin/config/regional/translate/import');
    $this->submitForm($options, 'Import');
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

msgid "@count hour"
msgid_plural "@count hours"
msgstr[0] "@count heure"
msgstr[1] "@count heures"

msgid "@count second"
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

msgid "@count hour"
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
