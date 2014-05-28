<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleTranslationTest.
 */

namespace Drupal\locale\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\Language;
use Drupal\Component\Utility\String;

/**
 * Functional test for string translation and validation.
 */
class LocaleTranslationUiTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale');

  public static function getInfo() {
    return array(
      'name' => 'String translate, search and validate',
      'description' => 'Adds a new locale and translates its name. Checks the validation of translation strings and search results.',
      'group' => 'Locale',
    );
  }

  /**
   *  Enable interface translation to English
   */
  function testEnglishTranslation() {
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);

    $this->drupalPostForm('admin/config/regional/language/edit/en', array('locale_translate_english' => TRUE), t('Save language'));
    $this->assertLinkByHref('/admin/config/regional/translate?langcode=en', 0, 'Enabled interface translation to English.');
  }

  /**
   * Adds a language and tests string translation by users with the appropriate permissions.
   */
  function testStringTranslation() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    // User to translate and delete string.
    $translate_user = $this->drupalCreateUser(array('translate interface', 'access administration pages'));
    // Code for the language.
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomName(16);
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
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    // Add string.
    t($name, array(), array('langcode' => $langcode));
    // Reset locale cache.
    $this->container->get('string_translation')->reset();
    $this->assertRaw('"edit-languages-' . $langcode .'-weight"', 'Language code found.');
    $this->assertText(t($name), 'Test language added.');
    $this->drupalLogout();

    // Search for the name and translate it.
    $this->drupalLogin($translate_user);
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText($name, 'Search found the string as untranslated.');

    // Assume this is the only result, given the random name.
    // We save the lid from the path.
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];

    // No t() here, it's surely not translated yet.
    $this->assertText($name, 'name found on edit screen.');
    $this->assertNoOption('edit-langcode', 'en', 'No way to translate the string to English.');
    $this->drupalLogout();
    $this->drupalLogin($admin_user);
    $this->drupalPostForm('admin/config/regional/language/edit/en', array('locale_translate_english' => TRUE), t('Save language'));
    $this->drupalLogout();
    $this->drupalLogin($translate_user);
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText($name, 'Search found the string as untranslated.');

    // Assume this is the only result, given the random name.
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $translation,
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));
    $this->assertText(t('The strings have been saved.'), 'The strings have been saved.');
    $url_bits = explode('?', $this->getUrl());
    $this->assertEqual($url_bits[0], url('admin/config/regional/translate', array('absolute' => TRUE)), 'Correct page redirection.');
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'translated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertRaw($translation, 'Non-English translation properly saved.');


    $search = array(
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'untranslated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $translation_to_en,
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));
    $search = array(
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'translated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertRaw($translation_to_en, 'English translation properly saved.');

    // Reset the tag cache on the tester side in order to pick up the call to
    // Cache::deleteTags() on the tested side.
    drupal_static_reset('Drupal\Core\Cache\CacheBackendInterface::tagCache');

    $this->assertTrue($name != $translation && t($name, array(), array('langcode' => $langcode)) == $translation, 't() works for non-English.');
    // Refresh the locale() cache to get fresh data from t() below. We are in
    // the same HTTP request and therefore t() is not refreshed by saving the
    // translation above.
    $this->container->get('string_translation')->reset();
    // Now we should get the proper fresh translation from t().
    $this->assertTrue($name != $translation_to_en && t($name, array(), array('langcode' => 'en')) == $translation_to_en, 't() works for English.');
    $this->assertTrue(t($name, array(), array('langcode' => Language::LANGCODE_SYSTEM)) == $name, 't() works for Language::LANGCODE_SYSTEM.');

    $search = array(
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'untranslated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), 'String is translated.');

    $this->drupalLogout();

    // Delete the language.
    $this->drupalLogin($admin_user);
    $path = 'admin/config/regional/language/delete/' . $langcode;
    // This a confirm form, we do not need any fields changed.
    $this->drupalPostForm($path, array(), t('Delete'));
    // We need raw here because %language and %langcode will add HTML.
    $t_args = array('%language' => $name, '%langcode' => $langcode);
    $this->assertRaw(t('The %language (%langcode) language has been removed.', $t_args), 'The test language has been removed.');
    // Reload to remove $name.
    $this->drupalGet($path);
    // Verify that language is no longer found.
    $this->assertResponse(404, 'Language no longer found.');
    $this->drupalLogout();

    // Delete the string.
    $this->drupalLogin($translate_user);
    $search = array(
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'translated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    // Assume this is the only result, given the random name.
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => '',
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));
    $this->assertRaw($name, 'The strings have been saved.');
    $this->drupalLogin($translate_user);
    $search = array(
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'untranslated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), 'The translation has been removed');
  }

  /*
   * Adds a language and checks that the JavaScript translation files are
   * properly created and rebuilt on deletion.
   */
  function testJavaScriptTranslation() {
    $user = $this->drupalCreateUser(array('translate interface', 'administer languages', 'access administration pages'));
    $this->drupalLogin($user);
    $config = \Drupal::config('locale.settings');

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
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->container->get('language_manager')->reset();

    // Build the JavaScript translation file.

    // Retrieve the source string of the first string available in the
    // {locales_source} table and translate it.
    $source = db_select('locales_source', 'l')
      ->fields('l', array('source'))
      ->condition('l.source', '%.js%', 'LIKE')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    $search = array(
      'string' => $source,
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));

    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $this->randomName(),
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    // Trigger JavaScript translation parsing and building.
    _locale_rebuild_js($langcode);

    $locale_javascripts = \Drupal::state()->get('locale.translation.javascript') ?: array();
    $js_file = 'public://' . $config->get('javascript.directory') . '/' . $langcode . '_' . $locale_javascripts[$langcode] . '.js';
    $this->assertTrue($result = file_exists($js_file), String::format('JavaScript file created: %file', array('%file' => $result ? $js_file : 'not found')));

    // Test JavaScript translation rebuilding.
    file_unmanaged_delete($js_file);
    $this->assertTrue($result = !file_exists($js_file), String::format('JavaScript file deleted: %file', array('%file' => $result ? $js_file : 'found')));
    Cache::invalidateTags(array('content' => TRUE));
    _locale_rebuild_js($langcode);
    $this->assertTrue($result = file_exists($js_file), String::format('JavaScript file rebuilt: %file', array('%file' => $result ? $js_file : 'not found')));
  }

  /**
   * Tests the validation of the translation input.
   */
  function testStringValidation() {
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
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    // Add string.
    t($name, array(), array('langcode' => $langcode));
    // Reset locale cache.
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    // Find the edit path.

    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    foreach ($bad_translations as $translation) {
      $edit = array(
        $lid => $translation,
      );
      $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));
      // Check for a form error on the textarea.
      $form_class = $this->xpath('//form[@id="locale-translate-edit-form"]//textarea/@class');
      $this->assertNotIdentical(FALSE, strpos($form_class[0], 'error'), 'The string was rejected as unsafe.');
      $this->assertNoText(t('The string has been saved.'), 'The string was not saved.');
    }
  }

  /**
   * Tests translation search form.
   */
  function testStringSearch() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    // User to translate and delete string.
    $translate_user = $this->drupalCreateUser(array('translate interface', 'access administration pages'));

    // Code for the language.
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomName(16);
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
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => 'yy',
      'name' => $this->randomName(16),
      'direction' => '0',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Add string.
    t($name, array(), array('langcode' => $langcode));
    // Reset locale cache.
    $this->container->get('string_translation')->reset();
    $this->drupalLogout();

    // Search for the name.
    $this->drupalLogin($translate_user);
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    // assertText() seems to remove the input field where $name always could be
    // found, so this is not a false assert. See how assertNoText succeeds
    // later.
    $this->assertText($name, 'Search found the string.');

    // Ensure untranslated string doesn't appear if searching on 'only
    // translated strings'.
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'translated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), "Search didn't find the string.");

    // Ensure untranslated string appears if searching on 'only untranslated
    // strings'.
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), 'Search found the string.');

    // Add translation.
    // Assume this is the only result, given the random name.
    // We save the lid from the path.
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $translation,
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    // Ensure translated string does appear if searching on 'only
    // translated strings'.
    $search = array(
      'string' => $translation,
      'langcode' => $langcode,
      'translation' => 'translated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), 'Search found the translation.');

    // Ensure translated source string doesn't appear if searching on 'only
    // untranslated strings'.
    $search = array(
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), "Search didn't find the source string.");

    // Ensure translated string doesn't appear if searching on 'only
    // untranslated strings'.
    $search = array(
      'string' => $translation,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), "Search didn't find the translation.");

    // Ensure translated string does appear if searching on the custom language.
    $search = array(
      'string' => $translation,
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), 'Search found the translation.');

    // Ensure translated string doesn't appear if searching in System (English).
    $search = array(
      'string' => $translation,
      'langcode' => 'yy',
      'translation' => 'all',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), "Search didn't find the translation.");

    // Search for a string that isn't in the system.
    $unavailable_string = $this->randomName(16);
    $search = array(
      'string' => $unavailable_string,
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), "Search didn't find the invalid string.");
  }

  /**
   * Tests that only changed strings are saved customized when edited.
   */
  function testUICustomizedStrings(){
    $user = $this->drupalCreateUser(array('translate interface', 'administer languages', 'access administration pages'));
    $this->drupalLogin($user);
    $language = new Language(array('id' => 'de'));
    language_save($language);

    // Create test source string
    $string = $this->container->get('locale.storage')->createString(array(
      'source' => $this->randomName(100),
      'context' => $this->randomName(20),
    ))->save();

    // Create translation for new string and save it as non-customized.
    $translation = $this->container->get('locale.storage')->createTranslation(array(
      'lid' => $string->lid,
      'language' => 'de',
      'translation' => $this->randomName(100),
      'customized' => 0,
    ))->save();

    // Reset locale cache.
    $this->container->get('string_translation')->reset();

    // Ensure non-customized translation string does appear if searching Non-customized translation.
    $search = array(
      'string' => $string->getString(),
      'langcode' => 'de',
      'translation' => 'translated',
      'customized' => '0',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));

    $this->assertText($translation->getString(), 'Translation is found in search result.');

    // Submit the translations without changing the translation.
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $translation->getString(),
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    // Ensure unchanged translation string does appear if searching non-customized translation.
    $search = array(
      'string' => $string->getString(),
      'langcode' => 'de',
      'translation' => 'translated',
      'customized' => '0',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText($string->getString(), 'Translation is not marked as customized.');

    // Submit the translations with a new translation.
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $this->randomName(100),
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    // Ensure changed translation string does appear if searching customized translation.
    $search = array(
      'string' => $string->getString(),
      'langcode' => 'de',
      'translation' => 'translated',
      'customized' => '1',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText($string->getString(), "Translation is marked as customized.");
  }
}
