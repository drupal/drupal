<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleImportFunctionalTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for the import of translation files.
 */
class LocaleImportFunctionalTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Translation import',
      'description' => 'Tests the import of locale files.',
      'group' => 'Locale',
    );
  }

  /**
   * A user able to create languages and import translations.
   */
  protected $admin_user = NULL;

  function setUp() {
    parent::setUp(array('locale', 'dblog'));

    // Set the translation file directory.
    variable_set('locale_translate_file_directory', drupal_get_path('module', 'locale') . '/tests');

    $this->admin_user = $this->drupalCreateUser(array('administer languages', 'translate interface', 'access administration pages'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test import of standalone .po files.
   */
  function testStandalonePoFile() {
    // Try importing a .po file.
    $this->importPoFile($this->getPoFile(), array(
      'langcode' => 'fr',
    ));

    // The import should automatically create the corresponding language.
    $this->assertRaw(t('The language %language has been created.', array('%language' => 'French')), t('The language has been automatically created.'));

    // The import should have created 8 strings.
    $this->assertRaw(t('The translation was successfully imported. There are %number newly created translated strings, %update strings were updated and %delete strings were removed.', array('%number' => 8, '%update' => 0, '%delete' => 0)), t('The translation file was successfully imported.'));

    // This import should have saved plural forms to have 2 variants.
    $locale_plurals = variable_get('locale_translation_plurals', array());
    $this->assert($locale_plurals['fr']['plurals'] == 2, t('Plural number initialized.'));

    // Ensure we were redirected correctly.
    $this->assertEqual($this->getUrl(), url('admin/config/regional/translate', array('absolute' => TRUE)), t('Correct page redirection.'));


    // Try importing a .po file with invalid tags.
    $this->importPoFile($this->getBadPoFile(), array(
      'langcode' => 'fr',
    ));

    // The import should have created 1 string and rejected 2.
    $this->assertRaw(t('The translation was successfully imported. There are %number newly created translated strings, %update strings were updated and %delete strings were removed.', array('%number' => 1, '%update' => 0, '%delete' => 0)), t('The translation file was successfully imported.'));
    $skip_message = format_plural(2, 'A translation string was skipped because of disallowed or malformed HTML. <a href="@url">See the log</a> for details.', '@count translation strings were skipped because of disallowed or malformed HTML. <a href="@url">See the log</a> for details.', array('@url' => url('admin/reports/dblog')));
    $this->assertRaw($skip_message, t('Unsafe strings were skipped.'));


    // Try importing a .po file which doesn't exist.
    $name = $this->randomName(16);
    $this->drupalPost('admin/config/regional/translate/import', array(
      'langcode' => 'fr',
      'files[file]' => $name,
    ), t('Import'));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/translate/import', array('absolute' => TRUE)), t('Correct page redirection.'));
    $this->assertText(t('File to import not found.'), t('File to import not found message.'));


    // Try importing a .po file with overriding strings, and ensure existing
    // strings are kept.
    $this->importPoFile($this->getOverwritePoFile(), array(
      'langcode' => 'fr',
    ));

    // The import should have created 1 string.
    $this->assertRaw(t('The translation was successfully imported. There are %number newly created translated strings, %update strings were updated and %delete strings were removed.', array('%number' => 1, '%update' => 0, '%delete' => 0)), t('The translation file was successfully imported.'));
    // Ensure string wasn't overwritten.
    $search = array(
      'string' => 'Montag',
      'language' => 'fr',
      'translation' => 'translated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), t('String not overwritten by imported string.'));

    // This import should not have changed number of plural forms.
    $locale_plurals = variable_get('locale_translation_plurals', array());
    $this->assert($locale_plurals['fr']['plurals'] == 2, t('Plural numbers untouched.'));

    // Try importing a .po file with overriding strings, and ensure existing
    // strings are overwritten.
    $this->importPoFile($this->getOverwritePoFile(), array(
      'langcode' => 'fr',
      'overwrite_options[not_customized]' => TRUE,
    ));

    // The import should have updated 2 strings.
    $this->assertRaw(t('The translation was successfully imported. There are %number newly created translated strings, %update strings were updated and %delete strings were removed.', array('%number' => 0, '%update' => 2, '%delete' => 0)), t('The translation file was successfully imported.'));
    // Ensure string was overwritten.
    $search = array(
      'string' => 'Montag',
      'language' => 'fr',
      'translation' => 'translated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), t('String overwritten by imported string.'));
    // This import should have changed number of plural forms.
    $locale_plurals = variable_get('locale_translation_plurals', array());
    $this->assert($locale_plurals['fr']['plurals'] == 3, t('Plural numbers changed.'));

    // Importing a .po file and mark its strings as customized strings.
    $this->importPoFile($this->getCustomPoFile(), array(
      'langcode' => 'fr',
      'customized' => TRUE,
    ));

    // The import should have created 6 strings.
    $this->assertRaw(t('The translation was successfully imported. There are %number newly created translated strings, %update strings were updated and %delete strings were removed.', array('%number' => 6, '%update' => 0, '%delete' => 0)), t('The customized translation file was successfully imported.'));

    // The database should now contain 6 customized strings (two imported
    // strings are not translated).
    $count = db_query('SELECT lid FROM {locales_target} WHERE customized = :custom', array(':custom' => 1))->rowCount();
    $this->assertEqual($count, 6, t('Customized translations succesfully imported.'));

    // Try importing a .po file with overriding strings, and ensure existing
    // customized strings are kept.
    $this->importPoFile($this->getCustomOverwritePoFile(), array(
      'langcode' => 'fr',
      'overwrite_options[not_customized]' => TRUE,
      'overwrite_options[customized]' => FALSE,
    ));

    // The import should have created 1 string.
    $this->assertRaw(t('The translation was successfully imported. There are %number newly created translated strings, %update strings were updated and %delete strings were removed.', array('%number' => 1, '%update' => 0, '%delete' => 0)), t('The customized translation file was successfully imported.'));
    // Ensure string wasn't overwritten.
    $search = array(
      'string' => 'januari',
      'language' => 'fr',
      'translation' => 'translated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), t('Customized string not overwritten by imported string.'));

    // Try importing a .po file with overriding strings, and ensure existing
    // customized strings are overwritten.
    $this->importPoFile($this->getCustomOverwritePoFile(), array(
      'langcode' => 'fr',
      'overwrite_options[not_customized]' => FALSE,
      'overwrite_options[customized]' => TRUE,
    ));

    // The import should have updated 2 strings.
    $this->assertRaw(t('The translation was successfully imported. There are %number newly created translated strings, %update strings were updated and %delete strings were removed.', array('%number' => 0, '%update' => 2, '%delete' => 0)), t('The customized translation file was successfully imported.'));
    // Ensure string was overwritten.
    $search = array(
      'string' => 'januari',
      'language' => 'fr',
      'translation' => 'translated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), t('Customized string overwritten by imported string.'));

  }

  /**
   * Test automatic import of a module's translation files.
   */
  function testAutomaticModuleTranslationImportLanguageEnable() {
    // Code for the language - manually set to match the test translation file.
    $langcode = 'xx';
    // The English name for the language.
    $name = $this->randomName(16);

    // Create a custom language.
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Ensure the translation file was automatically imported when language was
    // added.
    $this->assertText(t('One translation file imported.'), t('Language file automatically imported.'));

    // Ensure strings were successfully imported.
    $search = array(
      'string' => 'lundi',
      'language' => $langcode,
      'translation' => 'translated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), t('String successfully imported.'));
  }

  /**
   * Test msgctxt context support.
   */
  function testLanguageContext() {
    // Try importing a .po file.
    $this->importPoFile($this->getPoFileWithContext(), array(
      'langcode' => 'hr',
    ));

    $this->assertIdentical(t('May', array(), array('langcode' => 'hr', 'context' => 'Long month name')), 'Svibanj', t('Long month name context is working.'));
    $this->assertIdentical(t('May', array(), array('langcode' => 'hr')), 'Svi.', t('Default context is working.'));
  }

  /**
   * Test empty msgstr at end of .po file see #611786.
   */
  function testEmptyMsgstr() {
    $langcode = 'hu';

    // Try importing a .po file.
    $this->importPoFile($this->getPoFileWithMsgstr(), array(
      'langcode' => $langcode,
    ));

    $this->assertRaw(t('The translation was successfully imported. There are %number newly created translated strings, %update strings were updated and %delete strings were removed.', array('%number' => 1, '%update' => 0, '%delete' => 0)), t('The translation file was successfully imported.'));
    $this->assertIdentical(t('Operations', array(), array('langcode' => $langcode)), 'Műveletek', t('String imported and translated.'));

    // Try importing a .po file.
    $this->importPoFile($this->getPoFileWithEmptyMsgstr(), array(
      'langcode' => $langcode,
      'overwrite_options[not_customized]' => TRUE,
    ));
    $this->assertRaw(t('The translation was successfully imported. There are %number newly created translated strings, %update strings were updated and %delete strings were removed.', array('%number' => 0, '%update' => 0, '%delete' => 1)), t('The translation file was successfully imported.'));
    // This is the language indicator on the translation search screen for
    // untranslated strings.
    $language_indicator = "<em class=\"locale-untranslated\">$langcode</em> ";
    $str = "Operations";
    $search = array(
      'string' => $str,
      'language' => 'all',
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    // assertText() seems to remove the input field where $str always could be
    // found, so this is not a false assert.
    $this->assertText($str, t('Search found the string.'));
    $this->assertRaw($language_indicator, t('String is untranslated again.'));
  }

  /**
   * Helper function: import a standalone .po file in a given language.
   *
   * @param $contents
   *   Contents of the .po file to import.
   * @param $options
   *   Additional options to pass to the translation import form.
   */
  function importPoFile($contents, array $options = array()) {
    $name = tempnam('temporary://', "po_") . '.po';
    file_put_contents($name, $contents);
    $options['files[file]'] = $name;
    $this->drupalPost('admin/config/regional/translate/import', $options, t('Import'));
    drupal_unlink($name);
  }

  /**
   * Helper function that returns a proper .po file.
   */
  function getPoFile() {
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
   * Helper function that returns a bad .po file.
   */
  function getBadPoFile() {
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
  function getOverwritePoFile() {
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
  function getCustomPoFile() {
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
  function getCustomOverwritePoFile() {
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
  function getPoFileWithContext() {
    // Croatian (code hr) is one the the languages that have a different
    // form for the full name and the abbreviated name for the month May.
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
  function getPoFileWithEmptyMsgstr() {
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
  function getPoFileWithMsgstr() {
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

}
