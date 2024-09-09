<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the validation of translation strings and search results.
 *
 * @group locale
 * @group #slow
 */
class LocaleTranslationUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['locale'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected bool $useOneTimeLoginLinks = FALSE;

  /**
   * Enable interface translation to English.
   */
  public function testEnglishTranslation(): void {
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/config/regional/language/edit/en');
    $this->submitForm(['locale_translate_english' => TRUE], 'Save language');
    $this->assertSession()->linkByHrefExists('/admin/config/regional/translate?langcode=en', 0, 'Enabled interface translation to English.');
  }

  /**
   * Adds a language and tests string translation by users with the appropriate permissions.
   */
  public function testStringTranslation(): void {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
    ]);
    // User to translate and delete string.
    $translate_user = $this->drupalCreateUser([
      'translate interface',
      'access administration pages',
    ]);
    // Code for the language.
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = 'Foo';
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
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');
    // Add string.
    t($name, [], ['langcode' => $langcode])->render();
    // Reset locale cache.
    $this->container->get('string_translation')->reset();
    $this->assertSession()->responseContains('"edit-languages-' . $langcode . '-weight"');
    // Ensure that test language was added.
    $this->assertSession()->pageTextContains($name);
    $this->drupalLogout();

    // Add a whitespace at the end of string to ensure it is found.
    $name_ws = $name . " ";

    // Search for the name and translate it.
    $this->drupalLogin($translate_user);
    $search = [
      'string' => $name_ws,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    ];
    // Check that search finds the string as untranslated.
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains($name);

    // No t() here, it's surely not translated yet.
    $this->assertSession()->pageTextContains($name);
    // Verify that there is no way to translate the string to English.
    $this->assertSession()->optionNotExists('edit-langcode', 'en');
    $this->drupalLogout();
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/regional/language/edit/en');
    $this->submitForm(['locale_translate_english' => TRUE], 'Save language');
    $this->drupalLogout();
    $this->drupalLogin($translate_user);
    // Check that search finds the string as untranslated.
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains($name);

    // Assume this is the only result, given the random name.
    $textarea = $this->assertSession()->elementExists('xpath', '//textarea');
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => $translation,
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($edit, 'Save translations');
    $this->assertSession()->pageTextContains('The strings have been saved.');
    $url_bits = explode('?', $this->getUrl());
    $this->assertEquals(Url::fromRoute('locale.translate_page', [], ['absolute' => TRUE])->toString(), $url_bits[0], 'Correct page redirection.');
    $search = [
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'translated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains($translation);

    $search = [
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'untranslated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $textarea = $this->assertSession()->elementExists('xpath', '//textarea');
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => $translation_to_en,
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($edit, 'Save translations');
    $search = [
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'translated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains($translation_to_en);

    $this->assertNotEquals($translation, $name);
    $this->assertEquals($translation, t($name, [], ['langcode' => $langcode]), 't() works for non-English.');
    // Refresh the locale() cache to get fresh data from t() below. We are in
    // the same HTTP request and therefore t() is not refreshed by saving the
    // translation above.
    $this->container->get('string_translation')->reset();
    // Now we should get the proper fresh translation from t().
    $this->assertNotEquals($translation_to_en, $name);
    $this->assertEquals($translation_to_en, t($name, [], ['langcode' => 'en']), 't() works for English.');
    $this->assertTrue(t($name, [], ['langcode' => LanguageInterface::LANGCODE_SYSTEM]) == $name, 't() works for LanguageInterface::LANGCODE_SYSTEM.');

    $search = [
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'untranslated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains('No strings available.');

    // Test invalidation of 'rendered' cache tag after string translation.
    $this->drupalLogout();
    $this->drupalGet('xx/user/login');
    $this->assertSession()->pageTextContains('Password');

    $this->drupalLogin($translate_user);
    $search = [
      'string' => 'Password',
      'langcode' => $langcode,
      'translation' => 'untranslated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $textarea = $this->assertSession()->elementExists('xpath', '//textarea');
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => 'Llamas are larger than frogs.',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($edit, 'Save translations');

    $this->drupalLogout();
    $this->drupalGet('xx/user/login');
    $this->assertSession()->pageTextContains('Llamas are larger than frogs.');

    // Delete the language.
    $this->drupalLogin($admin_user);
    $path = 'admin/config/regional/language/delete/' . $langcode;
    // This a confirm form, we do not need any fields changed.
    $this->drupalGet($path);
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains("The {$name} ({$langcode}) language has been removed.");
    // Reload to remove $name.
    $this->drupalGet($path);
    // Verify that language is no longer found.
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalLogout();

    // Delete the string.
    $this->drupalLogin($translate_user);
    $search = [
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'translated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    // Assume this is the only result, given the random name.
    $textarea = $this->assertSession()->elementExists('xpath', '//textarea');
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => '',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($edit, 'Save translations');
    $this->assertSession()->responseContains($name);
    $this->drupalLogin($translate_user);
    $search = [
      'string' => $name,
      'langcode' => 'en',
      'translation' => 'untranslated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextNotContains('No strings available.');
  }

  /**
   * Tests the rebuilding of JavaScript translation files on deletion.
   */
  public function testJavaScriptTranslation(): void {
    $user = $this->drupalCreateUser([
      'translate interface',
      'administer languages',
      'access administration pages',
    ]);
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
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');
    $this->container->get('language_manager')->reset();

    // Build the JavaScript translation file.

    // Retrieve the source string of the first string available in the
    // {locales_source} table and translate it.
    $query = Database::getConnection()->select('locales_source', 's');
    $query->addJoin('INNER', 'locales_location', 'l', '[s].[lid] = [l].[lid]');
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
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');

    $textarea = $this->assertSession()->elementExists('xpath', '//textarea');
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => $this->randomMachineName(),
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($edit, 'Save translations');

    // Trigger JavaScript translation parsing and building.
    _locale_rebuild_js($langcode);

    $locale_javascripts = \Drupal::state()->get('locale.translation.javascript', []);
    $js_file = 'public://' . $config->get('javascript.directory') . '/' . $langcode . '_' . $locale_javascripts[$langcode] . '.js';
    $this->assertFileExists($js_file);

    // Test JavaScript translation rebuilding.
    \Drupal::service('file_system')->delete($js_file);
    $this->assertFileDoesNotExist($js_file);
    _locale_rebuild_js($langcode);
    $this->assertFileExists($js_file);

    // Test if JavaScript translation contains a custom string override.
    $string_override = $this->randomMachineName();
    $settings = Settings::getAll();
    $settings['locale_custom_strings_' . $langcode] = ['' => [$string_override => $string_override]];
    // Recreate the settings static.
    new Settings($settings);
    _locale_rebuild_js($langcode);
    $locale_javascripts = \Drupal::state()->get('locale.translation.javascript', []);
    $js_file = 'public://' . $config->get('javascript.directory') . '/' . $langcode . '_' . $locale_javascripts[$langcode] . '.js';
    $content = file_get_contents($js_file);
    $this->assertStringContainsString('"' . $string_override . '":"' . $string_override . '"', $content);
  }

  /**
   * Tests the validation of the translation input.
   */
  public function testStringValidation(): void {
    // User to add language and strings.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'translate interface',
    ]);
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
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');
    // Add string.
    t($name, [], ['langcode' => $langcode])->render();
    // Reset locale cache.
    $search = [
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'all',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    // Find the edit path.

    $textarea = $this->assertSession()->elementExists('xpath', '//textarea');
    $lid = $textarea->getAttribute('name');
    foreach ($bad_translations as $translation) {
      $edit = [
        $lid => $translation,
      ];
      $this->drupalGet('admin/config/regional/translate');
      $this->submitForm($edit, 'Save translations');
      // Check for a form error on the textarea, which means the string was
      // rejected as unsafe.
      $this->assertSession()->elementAttributeContains('xpath', '//form[@id="locale-translate-edit-form"]//textarea', 'class', 'error');
      $this->assertSession()->pageTextNotContains('The string has been saved.');
    }
  }

  /**
   * Tests translation search form.
   */
  public function testStringSearch(): void {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
    ]);
    // User to translate and delete string.
    $translate_user = $this->drupalCreateUser([
      'translate interface',
      'access administration pages',
    ]);

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
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');

    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => 'yy',
      'label' => $this->randomMachineName(16),
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');

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
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    // pageTextContains() seems to remove the input field where $name always
    // could be found, so this is not a false assert. See how
    // pageTextNotContains succeeds later.
    $this->assertSession()->pageTextContains($name);

    // Ensure untranslated string doesn't appear if searching on 'only
    // translated strings'.
    $search = [
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'translated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains('No strings available.');

    // Ensure untranslated string appears if searching on 'only untranslated
    // strings'.
    $search = [
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextNotContains('No strings available.');

    // Add translation.
    // Assume this is the only result, given the random name.
    // We save the lid from the path.
    $textarea = $this->assertSession()->elementExists('xpath', '//textarea');
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => $translation,
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($edit, 'Save translations');

    // Ensure translated string does appear if searching on 'only
    // translated strings'.
    $search = [
      'string' => $translation,
      'langcode' => $langcode,
      'translation' => 'translated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextNotContains('No strings available.');

    // Ensure translated source string doesn't appear if searching on 'only
    // untranslated strings'.
    $search = [
      'string' => $name,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains('No strings available.');

    // Ensure translated string doesn't appear if searching on 'only
    // untranslated strings'.
    $search = [
      'string' => $translation,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains('No strings available.');

    // Ensure translated string does appear if searching on the custom language.
    $search = [
      'string' => $translation,
      'langcode' => $langcode,
      'translation' => 'all',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextNotContains('No strings available.');

    // Ensure translated string doesn't appear if searching in System (English).
    $search = [
      'string' => $translation,
      'langcode' => 'yy',
      'translation' => 'all',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains('No strings available.');

    // Search for a string that isn't in the system.
    $unavailable_string = $this->randomMachineName(16);
    $search = [
      'string' => $unavailable_string,
      'langcode' => $langcode,
      'translation' => 'all',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains('No strings available.');
  }

  /**
   * Tests that only changed strings are saved customized when edited.
   */
  public function testUICustomizedStrings(): void {
    $user = $this->drupalCreateUser([
      'translate interface',
      'administer languages',
      'access administration pages',
    ]);
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
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');

    $this->assertSession()->pageTextContains($translation->getString());

    // Submit the translations without changing the translation.
    $textarea = $this->assertSession()->elementExists('xpath', '//textarea');
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => $translation->getString(),
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($edit, 'Save translations');

    // Ensure unchanged translation string does appear if searching
    // non-customized translation.
    $search = [
      'string' => $string->getString(),
      'langcode' => 'de',
      'translation' => 'translated',
      'customized' => '0',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains($string->getString());

    // Submit the translations with a new translation.
    $textarea = $this->assertSession()->elementExists('xpath', '//textarea');
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => $this->randomMachineName(100),
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($edit, 'Save translations');

    // Ensure changed translation string does appear if searching customized
    // translation.
    $search = [
      'string' => $string->getString(),
      'langcode' => 'de',
      'translation' => 'translated',
      'customized' => '1',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextContains($string->getString());
  }

}
