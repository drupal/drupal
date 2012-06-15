<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleTranslationTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functional test for string translation and validation.
 */
class LocaleTranslationTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'String translate, search and validate',
      'description' => 'Adds a new locale and translates its name. Checks the validation of translation strings and search results.',
      'group' => 'Locale',
    );
  }

  function setUp() {
    parent::setUp('locale');
  }

  /**
   * Adds a language and tests string translation by users with the appropriate permissions.
   */
  function testStringTranslation() {
    global $base_url;

    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    // User to translate and delete string.
    $translate_user = $this->drupalCreateUser(array('translate interface', 'access administration pages'));
    // Code for the language.
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomName(16);
    // This is the language indicator on the translation search screen for
    // untranslated strings.
    $language_indicator = "<em class=\"locale-untranslated\">$langcode</em> ";
    // This will be the translation of $name.
    $translation = $this->randomName(16);
    $translation_to_en = $this->randomName(16);

    // Add custom language.
    $this->drupalLogin($admin_user);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add custom language'));
    // Add string.
    t($name, array(), array('langcode' => $langcode));
    // Reset locale cache.
    locale_reset();
    $this->assertRaw('"edit-site-default-' . $langcode .'"', t('Language code found.'));
    $this->assertText(t($name), t('Test language added.'));
    $this->drupalLogout();

    // Search for the name and translate it.
    $this->drupalLogin($translate_user);
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText($name, t('Search found the string as untranslated.'));

    // Assume this is the only result, given the random name.
    // We save the lid from the path.
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $this->randomName(),
    );
    // No t() here, it's surely not translated yet.
    $this->assertText($name, t('name found on edit screen.'));
    $this->assertNoText('English', t('No way to translate the string to English.'));
    $this->drupalLogout();
    $this->drupalLogin($admin_user);
    $this->drupalPost('admin/config/regional/language/edit/en', array('locale_translate_english' => TRUE), t('Save language'));
    $this->drupalLogout();
    $this->drupalLogin($translate_user);
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText($name, t('Search found the string as untranslated.'));

    // Assume this is the only result, given the random name.
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $translation,
    );
    $this->drupalPost('admin/config/regional/translate/translate', $edit, t('Save translations'));
    $this->assertText(t('The strings have been saved.'), t('The strings have been saved.'));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/translate/translate', array('absolute' => TRUE)), t('Correct page redirection.'));
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'translated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertRaw($translation, t('Non-English translation properly saved.'));


    $search = array(
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'untranslated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $translation_to_en,
    );
    $this->drupalPost('admin/config/regional/translate/translate', $edit, t('Save translations'));
    $search = array(
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'translated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertRaw($translation_to_en, t('English translation properly saved.'));

    $this->assertTrue($name != $translation && t($name, array(), array('langcode' => $langcode)) == $translation, t('t() works for non-English.'));
    // Refresh the locale() cache to get fresh data from t() below. We are in
    // the same HTTP request and therefore t() is not refreshed by saving the
    // translation above.
    locale_reset();
    // Now we should get the proper fresh translation from t().
    $this->assertTrue($name != $translation_to_en && t($name, array(), array('langcode' => 'en')) == $translation_to_en, t('t() works for English.'));
    $this->assertTrue(t($name, array(), array('langcode' => LANGUAGE_SYSTEM)) == $name, t('t() works for LANGUAGE_SYSTEM.'));

    $search = array(
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'untranslated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), t('String is translated.'));

    $this->drupalLogout();

    // Delete the language.
    $this->drupalLogin($admin_user);
    $path = 'admin/config/regional/language/delete/' . $langcode;
    // This a confirm form, we do not need any fields changed.
    $this->drupalPost($path, array(), t('Delete'));
    // We need raw here because %language and %langcode will add HTML.
    $t_args = array('%language' => $name, '%langcode' => $langcode);
    $this->assertRaw(t('The %language (%langcode) language has been removed.', $t_args), t('The test language has been removed.'));
    // Reload to remove $name.
    $this->drupalGet($path);
    // Verify that language is no longer found.
    $this->assertResponse(404, t('Language no longer found.'));
    $this->drupalLogout();

    // Delete the string.
    $this->drupalLogin($translate_user);
    $search = array(
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'translated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    // Assume this is the only result, given the random name.
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => '',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $edit, t('Save translations'));
    $this->assertRaw($name, t('The strings have been saved.'));
    $this->drupalLogin($translate_user);
    $search = array(
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'untranslated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), t('The translation has been removed'));
  }

  /*
   * Adds a language and checks that the JavaScript translation files are
   * properly created and rebuilt on deletion.
   */
  function testJavaScriptTranslation() {
    $user = $this->drupalCreateUser(array('translate interface', 'administer languages', 'access administration pages'));
    $this->drupalLogin($user);

    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomName(16);

    // Add custom language.
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add custom language'));
    drupal_static_reset('language_list');

    // Build the JavaScript translation file.

    // Retrieve the source string of the first string available in the
    // {locales_source} table and translate it.
    $source = db_select('locales_source', 'l')
      ->fields('l', array('source'))
      ->condition('l.location', '%.js%', 'LIKE')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    $search = array(
      'string' => $source,
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));

    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $this->randomName(),
    );
    $this->drupalPost('admin/config/regional/translate/translate', $edit, t('Save translations'));

    // Trigger JavaScript translation parsing and building.
    _locale_rebuild_js($langcode);

    $locale_javascripts = variable_get('locale_translation_javascript', array());
    $js_file = 'public://' . variable_get('locale_js_directory', 'languages') . '/' . $langcode . '_' . $locale_javascripts[$langcode] . '.js';
    $this->assertTrue($result = file_exists($js_file), t('JavaScript file created: %file', array('%file' => $result ? $js_file : t('not found'))));

    // Test JavaScript translation rebuilding.
    file_unmanaged_delete($js_file);
    $this->assertTrue($result = !file_exists($js_file), t('JavaScript file deleted: %file', array('%file' => $result ? $js_file : t('found'))));
    cache_invalidate(array('content' => TRUE));
    _locale_rebuild_js($langcode);
    $this->assertTrue($result = file_exists($js_file), t('JavaScript file rebuilt: %file', array('%file' => $result ? $js_file : t('not found'))));
  }

  /**
   * Tests the validation of the translation input.
   */
  function testStringValidation() {
    global $base_url;

    // User to add language and strings.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages', 'translate interface'));
    $this->drupalLogin($admin_user);
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomName(16);

    // These will be the invalid translations of $name.
    $key = $this->randomName(16);
    $bad_translations[$key] = "<script>alert('xss');</script>" . $key;
    $key = $this->randomName(16);
    $bad_translations[$key] = '<img SRC="javascript:alert(\'xss\');">' . $key;
    $key = $this->randomName(16);
    $bad_translations[$key] = '<<SCRIPT>alert("xss");//<</SCRIPT>' . $key;
    $key = $this->randomName(16);
    $bad_translations[$key] ="<BODY ONLOAD=alert('xss')>" . $key;

    // Add custom language.
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add custom language'));
    // Add string.
    t($name, array(), array('langcode' => $langcode));
    // Reset locale cache.
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    // Find the edit path.

    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    foreach ($bad_translations as $key => $translation) {
      $edit = array(
        $lid => $translation,
      );
      $this->drupalPost('admin/config/regional/translate/translate', $edit, t('Save translations'));
      // Check for a form error on the textarea.
      $form_class = $this->xpath('//form[@id="locale-translate-edit-form"]//textarea/@class');
      $this->assertNotIdentical(FALSE, strpos($form_class[0], 'error'), t('The string was rejected as unsafe.'));
      $this->assertNoText(t('The string has been saved.'), t('The string was not saved.'));
    }
  }

  /**
   * Tests translation search form.
   */
  function testStringSearch() {
    global $base_url;

    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    // User to translate and delete string.
    $translate_user = $this->drupalCreateUser(array('translate interface', 'access administration pages'));

    // Code for the language.
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomName(16);
    // This is the language indicator on the translation search screen for
    // untranslated strings.
    $language_indicator = "<em class=\"locale-untranslated\">$langcode</em> ";
    // This will be the translation of $name.
    $translation = $this->randomName(16);

    // Add custom language.
    $this->drupalLogin($admin_user);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add custom language'));

    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => 'yy',
      'name' => $this->randomName(16),
      'direction' => '0',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Add string.
    t($name, array(), array('langcode' => $langcode));
    // Reset locale cache.
    locale_reset();
    $this->drupalLogout();

    // Search for the name.
    $this->drupalLogin($translate_user);
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    // assertText() seems to remove the input field where $name always could be
    // found, so this is not a false assert. See how assertNoText succeeds
    // later.
    $this->assertText($name, t('Search found the string.'));

    // Ensure untranslated string doesn't appear if searching on 'only
    // translated strings'.
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'translated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), t("Search didn't find the string."));

    // Ensure untranslated string appears if searching on 'only untranslated
    // strings'.
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), t('Search found the string.'));

    // Add translation.
    // Assume this is the only result, given the random name.
    // We save the lid from the path.
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $translation,
    );
    $this->drupalPost('admin/config/regional/translate/translate', $edit, t('Save translations'));

    // Ensure translated string does appear if searching on 'only
    // translated strings'.
    $search = array(
      'string' => $translation,
      'langcode' => $langcode,
      'translation' => 'translated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), t('Search found the translation.'));

    // Ensure translated source string doesn't appear if searching on 'only
    // untranslated strings'.
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), t("Search didn't find the source string."));

    // Ensure translated string doesn't appear if searching on 'only
    // untranslated strings'.
    $search = array(
      'string' => $translation,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), t("Search didn't find the translation."));

    // Ensure translated string does appear if searching on the custom language.
    $search = array(
      'string' => $translation,
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), t('Search found the translation.'));

    // Ensure translated string doesn't appear if searching in System (English).
    $search = array(
      'string' => $translation,
      'langcode' => 'yy',
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), t("Search didn't find the translation."));

    // Search for a string that isn't in the system.
    $unavailable_string = $this->randomName(16);
    $search = array(
      'string' => $unavailable_string,
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), t("Search didn't find the invalid string."));
  }
}
