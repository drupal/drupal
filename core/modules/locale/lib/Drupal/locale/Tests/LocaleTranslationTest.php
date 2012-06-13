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
      'language' => 'all',
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    // assertText() seems to remove the input field where $name always could be
    // found, so this is not a false assert. See how assertNoText succeeds
    // later.
    $this->assertText($name, t('Search found the name.'));
    $this->assertRaw($language_indicator, t('Name is untranslated.'));
    // Assume this is the only result, given the random name.
    $this->clickLink(t('edit'));
    // We save the lid from the path.
    $matches = array();
    preg_match('!admin/config/regional/translate/edit/(\d+)!', $this->getUrl(), $matches);
    $lid = $matches[1];
    // No t() here, it's surely not translated yet.
    $this->assertText($name, t('name found on edit screen.'));
    $this->assertNoText('English', t('No way to translate the string to English.'));
    $this->drupalLogout();
    $this->drupalLogin($admin_user);
    $this->drupalPost('admin/config/regional/language/edit/en', array('locale_translate_english' => TRUE), t('Save language'));
    $this->drupalLogout();
    $this->drupalLogin($translate_user);
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    // assertText() seems to remove the input field where $name always could be
    // found, so this is not a false assert. See how assertNoText succeeds
    // later.
    $this->assertText($name, t('Search found the name.'));
    $this->assertRaw($language_indicator, t('Name is untranslated.'));
    // Assume this is the only result, given the random name.
    $this->clickLink(t('edit'));
    $string_edit_url = $this->getUrl();
    $edit = array(
      "translations[$langcode][0]" => $translation,
      'translations[en][0]' => $translation_to_en,
    );
    $this->drupalPost(NULL, $edit, t('Save translations'));
    $this->assertText(t('The string has been saved.'), t('The string has been saved.'));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/translate/translate', array('absolute' => TRUE)), t('Correct page redirection.'));
    $this->drupalGet($string_edit_url);
    $this->assertRaw($translation, t('Non-English translation properly saved.'));
    $this->assertRaw($translation_to_en, t('English translation properly saved.'));
    $this->assertTrue($name != $translation && t($name, array(), array('langcode' => $langcode)) == $translation, t('t() works for non-English.'));
    // Refresh the locale() cache to get fresh data from t() below. We are in
    // the same HTTP request and therefore t() is not refreshed by saving the
    // translation above.
    locale_reset();
    // Now we should get the proper fresh translation from t().
    $this->assertTrue($name != $translation_to_en && t($name, array(), array('langcode' => 'en')) == $translation_to_en, t('t() works for English.'));
    $this->assertTrue(t($name, array(), array('langcode' => LANGUAGE_SYSTEM)) == $name, t('t() works for LANGUAGE_SYSTEM.'));
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    // The indicator should not be here.
    $this->assertNoRaw($language_indicator, t('String is translated.'));

    // Try to edit a non-existent string and ensure we're redirected correctly.
    // Assuming we don't have 999,999 strings already.
    $random_lid = 999999;
    $this->drupalGet('admin/config/regional/translate/edit/' . $random_lid);
    $this->assertText(t('String not found'), t('String not found.'));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/translate/translate', array('absolute' => TRUE)), t('Correct page redirection.'));
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
      'language' => 'all',
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    // Assume this is the only result, given the random name.
    $this->clickLink(t('delete'));
    $this->assertText(t('Are you sure you want to delete the string'), t('"delete" link is correct.'));
    // Delete the string.
    $path = 'admin/config/regional/translate/delete/' . $lid;
    $this->drupalGet($path);
    // First test the 'cancel' link.
    $this->clickLink(t('Cancel'));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/translate/translate', array('absolute' => TRUE)), t('Correct page redirection.'));
    $this->assertRaw($name, t('The string was not deleted.'));
    // Delete the name string.
    $this->drupalPost('admin/config/regional/translate/delete/' . $lid, array(), t('Delete'));
    $this->assertText(t('The string has been removed.'), t('The string has been removed message.'));
    $this->assertEqual($this->getUrl(), url('admin/config/regional/translate/translate', array('absolute' => TRUE)), t('Correct page redirection.'));
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertNoText($name, t('Search now can not find the name.'));
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
    $this->drupalGet('admin/config/regional/translate/translate');

    // Retrieve the id of the first string available in the {locales_source}
    // table and translate it.
    $query = db_select('locales_source', 'l');
    $query->addExpression('min(l.lid)', 'lid');
    $result = $query->condition('l.location', '%.js%', 'LIKE')->execute();
    $url = 'admin/config/regional/translate/edit/' . $result->fetchObject()->lid;
    $edit = array('translations['. $langcode .'][0]' => $this->randomName());
    $this->drupalPost($url, $edit, t('Save translations'));

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
    // This is the language indicator on the translation search screen for
    // untranslated strings.
    $language_indicator = "<em class=\"locale-untranslated\">$langcode</em> ";
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
      'language' => 'all',
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    // Find the edit path.
    $content = $this->drupalGetContent();
    $this->assertTrue(preg_match('@(admin/config/regional/translate/edit/[0-9]+)@', $content, $matches), t('Found the edit path.'));
    $path = $matches[0];
    foreach ($bad_translations as $key => $translation) {
      $edit = array(
        "translations[$langcode][0]" => $translation,
      );
      $this->drupalPost($path, $edit, t('Save translations'));
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
    // Add string.
    t($name, array(), array('langcode' => $langcode));
    // Reset locale cache.
    locale_reset();
    $this->drupalLogout();

    // Search for the name.
    $this->drupalLogin($translate_user);
    $search = array(
      'string' => $name,
      'language' => 'all',
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
      'language' => 'all',
      'translation' => 'translated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), t("Search didn't find the string."));

    // Ensure untranslated string appears if searching on 'only untranslated
    // strings'.
    $search = array(
      'string' => $name,
      'language' => 'all',
      'translation' => 'untranslated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), t('Search found the string.'));

    // Add translation.
    // Assume this is the only result, given the random name.
    $this->clickLink(t('edit'));
    // We save the lid from the path.
    $matches = array();
    preg_match('!admin/config/regional/translate/edit/(\d)+!', $this->getUrl(), $matches);
    $lid = $matches[1];
    $edit = array(
      "translations[$langcode][0]" => $translation,
    );
    $this->drupalPost(NULL, $edit, t('Save translations'));

    // Ensure translated string does appear if searching on 'only
    // translated strings'.
    $search = array(
      'string' => $translation,
      'language' => 'all',
      'translation' => 'translated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), t('Search found the translation.'));

    // Ensure translated source string doesn't appear if searching on 'only
    // untranslated strings'.
    $search = array(
      'string' => $name,
      'language' => 'all',
      'translation' => 'untranslated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), t("Search didn't find the source string."));

    // Ensure translated string doesn't appear if searching on 'only
    // untranslated strings'.
    $search = array(
      'string' => $translation,
      'language' => 'all',
      'translation' => 'untranslated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), t("Search didn't find the translation."));

    // Ensure translated string does appear if searching on the custom language.
    $search = array(
      'string' => $translation,
      'language' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), t('Search found the translation.'));

    // Ensure translated string doesn't appear if searching in System (English).
    $search = array(
      'string' => $translation,
      'language' => LANGUAGE_SYSTEM,
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), t("Search didn't find the translation."));

    // Search for a string that isn't in the system.
    $unavailable_string = $this->randomName(16);
    $search = array(
      'string' => $unavailable_string,
      'language' => 'all',
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), t("Search didn't find the invalid string."));
  }
}
