<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Adds a new locale and translates its name. Checks the validation of
 * translation strings and search results.
 *
 * @group locale
 */
class LocaleTranslationUiTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['locale'];

  /**
   * Enable interface translation to English.
   */
  public function testEnglishTranslation() {
    $admin_user = $this->drupalCreateUser(['administer languages', 'access administration pages']);
    $this->drupalLogin($admin_user);

    $this->drupalPostForm('admin/config/regional/language/edit/en', ['locale_translate_english' => TRUE], t('Save language'));
    $this->assertLinkByHref('/admin/config/regional/translate?langcode=en', 0, 'Enabled interface translation to English.');
  }

  /**
   * Adds a language and tests string translation by users with the appropriate permissions.
   */
  public function testStringTranslation() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(['administer languages', 'access administration pages']);
    // User to translate and delete string.
    $translate_user = $this->drupalCreateUser(['translate interface', 'access administration pages']);
    // Code for the language.
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = 'cucurbitaceae';
    // This will be the translation of $name.
    $translation = $this->randomMachineName(16);
    $translation_to_en = $this->randomMachineName(16);

    // Add custom language.
    $this->drupalLogin($admin_user);
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    // Add string.
    t($name, [], ['langcode' => $langcode])->render();
    // Reset locale cache.
    $this->container->get('string_translation')->reset();
    $this->assertRaw('"edit-languages-' . $langcode . '-weight"', 'Language code found.');
    $this->assertText(t($name), 'Test language added.');
    $this->drupalLogout();

    // Search for the name and translate it.
    $this->drupalLogin($translate_user);
    $search = [
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText($name, 'Search found the string as untranslated.');

    // No t() here, it's surely not translated yet.
    $this->assertText($name, 'name found on edit screen.');
    $this->assertNoOption('edit-langcode', 'en', 'No way to translate the string to English.');
    $this->drupalLogout();
    $this->drupalLogin($admin_user);
    $this->drupalPostForm('admin/config/regional/language/edit/en', ['locale_translate_english' => TRUE], t('Save language'));
    $this->drupalLogout();
    $this->drupalLogin($translate_user);
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText($name, 'Search found the string as untranslated.');

    // Assume this is the only result, given the random name.
    $textarea = current($this->xpath('//textarea'));
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => $translation,
    ];
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));
    $this->assertText(t('The strings have been saved.'), 'The strings have been saved.');
    $url_bits = explode('?', $this->getUrl());
    $this->assertEqual($url_bits[0], Url::fromRoute('locale.translate_page', [], ['absolute' => TRUE])->toString(), 'Correct page redirection.');
    $search = [
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'translated',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertRaw($translation, 'Non-English translation properly saved.');

    $search = [
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'untranslated',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $textarea = current($this->xpath('//textarea'));
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => $translation_to_en,
    ];
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));
    $search = [
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'translated',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertRaw($translation_to_en, 'English translation properly saved.');

    $this->assertTrue($name != $translation && t($name, [], ['langcode' => $langcode]) == $translation, 't() works for non-English.');
    // Refresh the locale() cache to get fresh data from t() below. We are in
    // the same HTTP request and therefore t() is not refreshed by saving the
    // translation above.
    $this->container->get('string_translation')->reset();
    // Now we should get the proper fresh translation from t().
    $this->assertTrue($name != $translation_to_en && t($name, [], ['langcode' => 'en']) == $translation_to_en, 't() works for English.');
    $this->assertTrue(t($name, [], ['langcode' => LanguageInterface::LANGCODE_SYSTEM]) == $name, 't() works for LanguageInterface::LANGCODE_SYSTEM.');

    $search = [
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'untranslated',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), 'String is translated.');

    // Test invalidation of 'rendered' cache tag after string translation.
    $this->drupalLogout();
    $this->drupalGet('xx/user/login');
    $this->assertText('Enter the password that accompanies your username.');

    $this->drupalLogin($translate_user);
    $search = [
      'string' => 'accompanies your username',
      'langcode' => $langcode,
      'translation' => 'untranslated',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $textarea = current($this->xpath('//textarea'));
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => 'Please enter your Llama username.',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    $this->drupalLogout();
    $this->drupalGet('xx/user/login');
    $this->assertText('Please enter your Llama username.');

    // Delete the language.
    $this->drupalLogin($admin_user);
    $path = 'admin/config/regional/language/delete/' . $langcode;
    // This a confirm form, we do not need any fields changed.
    $this->drupalPostForm($path, [], t('Delete'));
    // We need raw here because %language and %langcode will add HTML.
    $t_args = ['%language' => $name, '%langcode' => $langcode];
    $this->assertRaw(t('The %language (%langcode) language has been removed.', $t_args), 'The test language has been removed.');
    // Reload to remove $name.
    $this->drupalGet($path);
    // Verify that language is no longer found.
    $this->assertResponse(404, 'Language no longer found.');
    $this->drupalLogout();

    // Delete the string.
    $this->drupalLogin($translate_user);
    $search = [
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'translated',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    // Assume this is the only result, given the random name.
    $textarea = current($this->xpath('//textarea'));
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => '',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));
    $this->assertRaw($name, 'The strings have been saved.');
    $this->drupalLogin($translate_user);
    $search = [
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'untranslated',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), 'The translation has been removed');
  }

  /**
   * Adds a language and checks that the JavaScript translation files are
   * properly created and rebuilt on deletion.
   */
  public function testJavaScriptTranslation() {
    $user = $this->drupalCreateUser(['translate interface', 'administer languages', 'access administration pages']);
    $this->drupalLogin($user);
    $config = $this->config('locale.settings');

    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomMachineName(16);

    // Add custom language.
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    $this->container->get('language_manager')->reset();

    // Build the JavaScript translation file.

    // Retrieve the source string of the first string available in the
    // {locales_source} table and translate it.
    $query = Database::getConnection()->select('locales_source', 's');
    $query->addJoin('INNER', 'locales_location', 'l', 's.lid = l.lid');
    $source = $query->fields('s', ['source'])
      ->condition('l.type', 'javascript')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    $search = [
      'string' => $source,
      'langcode' => $langcode,
      'translation' => 'all',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));

    $textarea = current($this->xpath('//textarea'));
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => $this->randomMachineName(),
    ];
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    // Trigger JavaScript translation parsing and building.
    _locale_rebuild_js($langcode);

    $locale_javascripts = \Drupal::state()->get('locale.translation.javascript') ?: [];
    $js_file = 'public://' . $config->get('javascript.directory') . '/' . $langcode . '_' . $locale_javascripts[$langcode] . '.js';
    $this->assertTrue($result = file_exists($js_file), new FormattableMarkup('JavaScript file created: %file', ['%file' => $result ? $js_file : 'not found']));

    // Test JavaScript translation rebuilding.
    \Drupal::service('file_system')->delete($js_file);
    $this->assertTrue($result = !file_exists($js_file), new FormattableMarkup('JavaScript file deleted: %file', ['%file' => $result ? $js_file : 'found']));
    _locale_rebuild_js($langcode);
    $this->assertTrue($result = file_exists($js_file), new FormattableMarkup('JavaScript file rebuilt: %file', ['%file' => $result ? $js_file : 'not found']));
  }

  /**
   * Tests the validation of the translation input.
   */
  public function testStringValidation() {
    // User to add language and strings.
    $admin_user = $this->drupalCreateUser(['administer languages', 'access administration pages', 'translate interface']);
    $this->drupalLogin($admin_user);
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomMachineName(16);

    // These will be the invalid translations of $name.
    $key = $this->randomMachineName(16);
    $bad_translations[$key] = "<script>alert('xss');</script>" . $key;
    $key = $this->randomMachineName(16);
    $bad_translations[$key] = '<img SRC="javascript:alert(\'xss\');">' . $key;
    $key = $this->randomMachineName(16);
    $bad_translations[$key] = '<<SCRIPT>alert("xss");//<</SCRIPT>' . $key;
    $key = $this->randomMachineName(16);
    $bad_translations[$key] = "<BODY ONLOAD=alert('xss')>" . $key;

    // Add custom language.
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    // Add string.
    t($name, [], ['langcode' => $langcode])->render();
    // Reset locale cache.
    $search = [
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'all',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    // Find the edit path.

    $textarea = current($this->xpath('//textarea'));
    $lid = $textarea->getAttribute('name');
    foreach ($bad_translations as $translation) {
      $edit = [
        $lid => $translation,
      ];
      $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));
      // Check for a form error on the textarea.
      $form_class = $this->xpath('//form[@id="locale-translate-edit-form"]//textarea/@class');
      $this->assertContains('error', $form_class[0]->getText(), 'The string was rejected as unsafe.');
      $this->assertNoText(t('The string has been saved.'), 'The string was not saved.');
    }
  }

  /**
   * Tests translation search form.
   */
  public function testStringSearch() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(['administer languages', 'access administration pages']);
    // User to translate and delete string.
    $translate_user = $this->drupalCreateUser(['translate interface', 'access administration pages']);

    // Code for the language.
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomMachineName(16);
    // This will be the translation of $name.
    $translation = $this->randomMachineName(16);

    // Add custom language.
    $this->drupalLogin($admin_user);
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => 'yy',
      'label' => $this->randomMachineName(16),
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Add string.
    t($name, [], ['langcode' => $langcode])->render();
    // Reset locale cache.
    $this->container->get('string_translation')->reset();
    $this->drupalLogout();

    // Search for the name.
    $this->drupalLogin($translate_user);
    $search = [
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'all',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    // assertText() seems to remove the input field where $name always could be
    // found, so this is not a false assert. See how assertNoText succeeds
    // later.
    $this->assertText($name, 'Search found the string.');

    // Ensure untranslated string doesn't appear if searching on 'only
    // translated strings'.
    $search = [
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'translated',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), "Search didn't find the string.");

    // Ensure untranslated string appears if searching on 'only untranslated
    // strings'.
    $search = [
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), 'Search found the string.');

    // Add translation.
    // Assume this is the only result, given the random name.
    // We save the lid from the path.
    $textarea = current($this->xpath('//textarea'));
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => $translation,
    ];
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    // Ensure translated string does appear if searching on 'only
    // translated strings'.
    $search = [
      'string' => $translation,
      'langcode' => $langcode,
      'translation' => 'translated',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), 'Search found the translation.');

    // Ensure translated source string doesn't appear if searching on 'only
    // untranslated strings'.
    $search = [
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), "Search didn't find the source string.");

    // Ensure translated string doesn't appear if searching on 'only
    // untranslated strings'.
    $search = [
      'string' => $translation,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), "Search didn't find the translation.");

    // Ensure translated string does appear if searching on the custom language.
    $search = [
      'string' => $translation,
      'langcode' => $langcode,
      'translation' => 'all',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), 'Search found the translation.');

    // Ensure translated string doesn't appear if searching in System (English).
    $search = [
      'string' => $translation,
      'langcode' => 'yy',
      'translation' => 'all',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), "Search didn't find the translation.");

    // Search for a string that isn't in the system.
    $unavailable_string = $this->randomMachineName(16);
    $search = [
      'string' => $unavailable_string,
      'langcode' => $langcode,
      'translation' => 'all',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText(t('No strings available.'), "Search didn't find the invalid string.");
  }

  /**
   * Tests that only changed strings are saved customized when edited.
   */
  public function testUICustomizedStrings() {
    $user = $this->drupalCreateUser(['translate interface', 'administer languages', 'access administration pages']);
    $this->drupalLogin($user);
    ConfigurableLanguage::createFromLangcode('de')->save();

    // Create test source string.
    $string = $this->container->get('locale.storage')->createString([
      'source' => $this->randomMachineName(100),
      'context' => $this->randomMachineName(20),
    ])->save();

    // Create translation for new string and save it as non-customized.
    $translation = $this->container->get('locale.storage')->createTranslation([
      'lid' => $string->lid,
      'language' => 'de',
      'translation' => $this->randomMachineName(100),
      'customized' => 0,
    ])->save();

    // Reset locale cache.
    $this->container->get('string_translation')->reset();

    // Ensure non-customized translation string does appear if searching
    // non-customized translation.
    $search = [
      'string' => $string->getString(),
      'langcode' => 'de',
      'translation' => 'translated',
      'customized' => '0',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));

    $this->assertText($translation->getString(), 'Translation is found in search result.');

    // Submit the translations without changing the translation.
    $textarea = current($this->xpath('//textarea'));
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => $translation->getString(),
    ];
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    // Ensure unchanged translation string does appear if searching
    // non-customized translation.
    $search = [
      'string' => $string->getString(),
      'langcode' => 'de',
      'translation' => 'translated',
      'customized' => '0',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText($string->getString(), 'Translation is not marked as customized.');

    // Submit the translations with a new translation.
    $textarea = current($this->xpath('//textarea'));
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => $this->randomMachineName(100),
    ];
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    // Ensure changed translation string does appear if searching customized
    // translation.
    $search = [
      'string' => $string->getString(),
      'langcode' => 'de',
      'translation' => 'translated',
      'customized' => '1',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertText($string->getString(), "Translation is marked as customized.");
  }

}
