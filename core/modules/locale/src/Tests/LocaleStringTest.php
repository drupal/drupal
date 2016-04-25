<?php

namespace Drupal\locale\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the locale string storage, string objects and data API.
 *
 * @group locale
 */
class LocaleStringTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale');

  /**
   * The locale storage.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add a default locale storage for all these tests.
    $this->storage = $this->container->get('locale.storage');
    // Create two languages: Spanish and German.
    foreach (array('es', 'de') as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  /**
   * Test CRUD API.
   */
  public function testStringCRUDAPI() {
    // Create source string.
    $source = $this->buildSourceString();
    $source->save();
    $this->assertTrue($source->lid, format_string('Successfully created string %string', array('%string' => $source->source)));

    // Load strings by lid and source.
    $string1 = $this->storage->findString(array('lid' => $source->lid));
    $this->assertEqual($source, $string1, 'Successfully retrieved string by identifier.');
    $string2 = $this->storage->findString(array('source' => $source->source, 'context' => $source->context));
    $this->assertEqual($source, $string2, 'Successfully retrieved string by source and context.');
    $string3 = $this->storage->findString(array('source' => $source->source, 'context' => ''));
    $this->assertFalse($string3, 'Cannot retrieve string with wrong context.');

    // Check version handling and updating.
    $this->assertEqual($source->version, 'none', 'String originally created without version.');
    $string = $this->storage->findTranslation(array('lid' => $source->lid));
    $this->assertEqual($string->version, \Drupal::VERSION, 'Checked and updated string version to Drupal version.');

    // Create translation and find it by lid and source.
    $langcode = 'es';
    $translation = $this->createTranslation($source, $langcode);
    $this->assertEqual($translation->customized, LOCALE_NOT_CUSTOMIZED, 'Translation created as not customized by default.');
    $string1 = $this->storage->findTranslation(array('language' => $langcode, 'lid' => $source->lid));
    $this->assertEqual($string1->translation, $translation->translation, 'Successfully loaded translation by string identifier.');
    $string2 = $this->storage->findTranslation(array('language' => $langcode, 'source' => $source->source, 'context' => $source->context));
    $this->assertEqual($string2->translation, $translation->translation, 'Successfully loaded translation by source and context.');
    $translation
      ->setCustomized()
      ->save();
    $translation = $this->storage->findTranslation(array('language' => $langcode, 'lid' => $source->lid));
    $this->assertEqual($translation->customized, LOCALE_CUSTOMIZED, 'Translation successfully marked as customized.');

    // Delete translation.
    $translation->delete();
    $deleted = $this->storage->findTranslation(array('language' => $langcode, 'lid' => $source->lid));
    $this->assertFalse(isset($deleted->translation), 'Successfully deleted translation string.');

    // Create some translations and then delete string and all of its
    // translations.
    $lid = $source->lid;
    $this->createAllTranslations($source);
    $search = $this->storage->getTranslations(array('lid' => $source->lid));
    $this->assertEqual(count($search), 3, 'Created and retrieved all translations for our source string.');

    $source->delete();
    $string = $this->storage->findString(array('lid' => $lid));
    $this->assertFalse($string, 'Successfully deleted source string.');
    $deleted = $search = $this->storage->getTranslations(array('lid' => $lid));
    $this->assertFalse($deleted, 'Successfully deleted all translation strings.');

    // Tests that locations of different types and arbitrary lengths can be
    // added to a source string. Too long locations will be cut off.
    $source_string = $this->buildSourceString();
    $source_string->addLocation('javascript', $this->randomString(8));
    $source_string->addLocation('configuration', $this->randomString(50));
    $source_string->addLocation('code', $this->randomString(100));
    $source_string->addLocation('path', $location = $this->randomString(300));
    $source_string->save();

    $rows = db_query('SELECT * FROM {locales_location} WHERE sid = :sid', array(':sid' => $source_string->lid))->fetchAllAssoc('type');
    $this->assertEqual(count($rows), 4, '4 source locations have been persisted.');
    $this->assertEqual($rows['path']->name, substr($location, 0, 255), 'Too long location has been limited to 255 characters.');
  }

  /**
   * Test Search API loading multiple objects.
   */
  public function testStringSearchAPI() {
    $language_count = 3;
    // Strings 1 and 2 will have some common prefix.
    // Source 1 will have all translations, not customized.
    // Source 2 will have all translations, customized.
    // Source 3 will have no translations.
    $prefix = $this->randomMachineName(100);
    $source1 = $this->buildSourceString(array('source' => $prefix . $this->randomMachineName(100)))->save();
    $source2 = $this->buildSourceString(array('source' => $prefix . $this->randomMachineName(100)))->save();
    $source3 = $this->buildSourceString()->save();
    // Load all source strings.
    $strings = $this->storage->getStrings(array());
    $this->assertEqual(count($strings), 3, 'Found 3 source strings in the database.');
    // Load all source strings matching a given string.
    $filter_options['filters'] = array('source' => $prefix);
    $strings = $this->storage->getStrings(array(), $filter_options);
    $this->assertEqual(count($strings), 2, 'Found 2 strings using some string filter.');

    // Not customized translations.
    $translate1 = $this->createAllTranslations($source1);
    // Customized translations.
    $this->createAllTranslations($source2, array('customized' => LOCALE_CUSTOMIZED));
    // Try quick search function with different field combinations.
    $langcode = 'es';
    $found = $this->storage->findTranslation(array('language' => $langcode, 'source' => $source1->source, 'context' => $source1->context));
    $this->assertTrue($found && isset($found->language) && isset($found->translation) && !$found->isNew(), 'Translation found searching by source and context.');
    $this->assertEqual($found->translation, $translate1[$langcode]->translation, 'Found the right translation.');
    // Now try a translation not found.
    $found = $this->storage->findTranslation(array('language' => $langcode, 'source' => $source3->source, 'context' => $source3->context));
    $this->assertTrue($found && $found->lid == $source3->lid && !isset($found->translation) && $found->isNew(), 'Translation not found but source string found.');

    // Load all translations. For next queries we'll be loading only translated
    // strings.
    $translations = $this->storage->getTranslations(array('translated' => TRUE));
    $this->assertEqual(count($translations), 2 * $language_count, 'Created and retrieved all translations for source strings.');

    // Load all customized translations.
    $translations = $this->storage->getTranslations(array('customized' => LOCALE_CUSTOMIZED, 'translated' => TRUE));
    $this->assertEqual(count($translations), $language_count, 'Retrieved all customized translations for source strings.');

    // Load all Spanish customized translations.
    $translations = $this->storage->getTranslations(array('language' => 'es', 'customized' => LOCALE_CUSTOMIZED, 'translated' => TRUE));
    $this->assertEqual(count($translations), 1, 'Found only Spanish and customized translations.');

    // Load all source strings without translation (1).
    $translations = $this->storage->getStrings(array('translated' => FALSE));
    $this->assertEqual(count($translations), 1, 'Found 1 source string without translations.');

    // Load Spanish translations using string filter.
    $filter_options['filters'] = array('source' => $prefix);
    $translations = $this->storage->getTranslations(array('language' => 'es'), $filter_options);
    $this->assertEqual(count($translations), 2, 'Found 2 translations using some string filter.');

  }

  /**
   * Creates random source string object.
   *
   * @return \Drupal\locale\StringInterface
   *   A locale string.
   */
  public function buildSourceString($values = array()) {
    return $this->storage->createString($values += array(
      'source' => $this->randomMachineName(100),
      'context' => $this->randomMachineName(20),
    ));
  }

  /**
   * Creates translations for source string and all languages.
   */
  public function createAllTranslations($source, $values = array()) {
    $list = array();
    /* @var $language_manager \Drupal\Core\Language\LanguageManagerInterface */
    $language_manager = $this->container->get('language_manager');
    foreach ($language_manager->getLanguages() as $language) {
      $list[$language->getId()] = $this->createTranslation($source, $language->getId(), $values);
    }
    return $list;
  }

  /**
   * Creates single translation for source string.
   */
  public function createTranslation($source, $langcode, $values = array()) {
    return $this->storage->createTranslation($values + array(
      'lid' => $source->lid,
      'language' => $langcode,
      'translation' => $this->randomMachineName(100),
    ))->save();
  }
}
