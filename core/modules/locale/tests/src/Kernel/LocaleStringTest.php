<?php

namespace Drupal\Tests\locale\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\locale\StringInterface;

/**
 * Tests the locale string storage, string objects and data API.
 *
 * @group locale
 */
class LocaleStringTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'locale',
  ];

  /**
   * The locale storage.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add a default locale storage for all these tests.
    $this->storage = $this->container->get('locale.storage');
    // Create two languages: Spanish and German.
    foreach (['es', 'de'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    $this->installSchema('locale', [
      'locales_location',
      'locales_source',
      'locales_target',
    ]);
  }

  /**
   * Tests CRUD API.
   */
  public function testStringCrudApi() {
    // Create source string.
    $source = $this->buildSourceString()->save();
    $this->assertNotEmpty($source->lid);

    // Load strings by lid and source.
    $string1 = $this->storage->findString(['lid' => $source->lid]);
    $this->assertEquals($source, $string1);
    $string2 = $this->storage->findString(['source' => $source->source, 'context' => $source->context]);
    $this->assertEquals($source, $string2);
    $string3 = $this->storage->findString(['source' => $source->source, 'context' => '']);
    $this->assertNull($string3);

    // Check version handling and updating.
    $this->assertEquals('none', $source->version);
    $string = $this->storage->findTranslation(['lid' => $source->lid]);
    $this->assertEquals(\Drupal::VERSION, $string->version);

    // Create translation and find it by lid and source.
    $langcode = 'es';
    $translation = $this->createTranslation($source, $langcode);
    $this->assertEquals(LOCALE_NOT_CUSTOMIZED, $translation->customized);
    $string1 = $this->storage->findTranslation(['language' => $langcode, 'lid' => $source->lid]);
    $this->assertEquals($translation->translation, $string1->translation);
    $string2 = $this->storage->findTranslation(['language' => $langcode, 'source' => $source->source, 'context' => $source->context]);
    $this->assertEquals($translation->translation, $string2->translation);
    $translation
      ->setCustomized()
      ->save();
    $translation = $this->storage->findTranslation(['language' => $langcode, 'lid' => $source->lid]);
    $this->assertEquals(LOCALE_CUSTOMIZED, $translation->customized);

    // Delete translation.
    $translation->delete();
    $deleted = $this->storage->findTranslation(['language' => $langcode, 'lid' => $source->lid]);
    $this->assertNull($deleted->translation);

    // Create some translations and then delete string and all of its
    // translations.
    $lid = $source->lid;
    $this->createAllTranslations($source);
    $search = $this->storage->getTranslations(['lid' => $source->lid]);
    $this->assertCount(3, $search);

    $source->delete();
    $string = $this->storage->findString(['lid' => $lid]);
    $this->assertNull($string);
    $deleted = $search = $this->storage->getTranslations(['lid' => $lid]);
    $this->assertEmpty($deleted);

    // Tests that locations of different types and arbitrary lengths can be
    // added to a source string. Too long locations will be cut off.
    $source_string = $this->buildSourceString();
    $source_string->addLocation('javascript', $this->randomString(8));
    $source_string->addLocation('configuration', $this->randomString(50));
    $source_string->addLocation('code', $this->randomString(100));
    $source_string->addLocation('path', $location = $this->randomString(300));
    $source_string->save();

    $rows = $this->container->get('database')->select('locales_location')
      ->fields('locales_location')
      ->condition('sid', $source_string->lid)
      ->execute()
      ->fetchAllAssoc('type');
    $this->assertCount(4, $rows);
    $this->assertEquals(substr($location, 0, 255), $rows['path']->name);
  }

  /**
   * Tests Search API loading multiple objects.
   */
  public function testStringSearchApi() {
    $language_count = 3;
    // Strings 1 and 2 will have some common prefix.
    // Source 1 will have all translations, not customized.
    // Source 2 will have all translations, customized.
    // Source 3 will have no translations.
    $prefix = $this->randomMachineName(100);
    $source1 = $this->buildSourceString(['source' => $prefix . $this->randomMachineName(100)])->save();
    $source2 = $this->buildSourceString(['source' => $prefix . $this->randomMachineName(100)])->save();
    $source3 = $this->buildSourceString()->save();

    // Load all source strings.
    $strings = $this->storage->getStrings([]);
    $this->assertCount(3, $strings);
    // Load all source strings matching a given string.
    $filter_options['filters'] = ['source' => $prefix];
    $strings = $this->storage->getStrings([], $filter_options);
    $this->assertCount(2, $strings);

    // Not customized translations.
    $translate1 = $this->createAllTranslations($source1);
    // Customized translations.
    $this->createAllTranslations($source2, ['customized' => LOCALE_CUSTOMIZED]);
    // Try quick search function with different field combinations.
    $langcode = 'es';
    $found = $this->storage->findTranslation(['language' => $langcode, 'source' => $source1->source, 'context' => $source1->context]);
    $this->assertNotNull($found, 'Translation not found searching by source and context.');
    $this->assertNotNull($found->language);
    $this->assertNotNull($found->translation);
    $this->assertFalse($found->isNew());
    $this->assertEquals($translate1[$langcode]->translation, $found->translation);
    // Now try a translation not found.
    $found = $this->storage->findTranslation(['language' => $langcode, 'source' => $source3->source, 'context' => $source3->context]);
    $this->assertNotNull($found);
    $this->assertSame($source3->lid, $found->lid);
    $this->assertNull($found->translation);
    $this->assertTrue($found->isNew());

    // Load all translations. For next queries we'll be loading only translated
    // strings.
    $translations = $this->storage->getTranslations(['translated' => TRUE]);
    $this->assertCount(2 * $language_count, $translations);

    // Load all customized translations.
    $translations = $this->storage->getTranslations(['customized' => LOCALE_CUSTOMIZED, 'translated' => TRUE]);
    $this->assertCount($language_count, $translations);

    // Load all Spanish customized translations.
    $translations = $this->storage->getTranslations(['language' => 'es', 'customized' => LOCALE_CUSTOMIZED, 'translated' => TRUE]);
    $this->assertCount(1, $translations);

    // Load all source strings without translation (1).
    $translations = $this->storage->getStrings(['translated' => FALSE]);
    $this->assertCount(1, $translations);

    // Load Spanish translations using string filter.
    $filter_options['filters'] = ['source' => $prefix];
    $translations = $this->storage->getTranslations(['language' => 'es'], $filter_options);
    $this->assertCount(2, $translations);
  }

  /**
   * Creates random source string object.
   *
   * @param array $values
   *   The values array.
   *
   * @return \Drupal\locale\StringInterface
   *   A locale string.
   */
  protected function buildSourceString(array $values = []) {
    return $this->storage->createString($values += [
      'source' => $this->randomMachineName(100),
      'context' => $this->randomMachineName(20),
    ]);
  }

  /**
   * Creates translations for source string and all languages.
   *
   * @param \Drupal\locale\StringInterface $source
   *   The source string.
   * @param array $values
   *   The values array.
   *
   * @return array
   *   Translation list.
   */
  protected function createAllTranslations(StringInterface $source, array $values = []) {
    $list = [];
    /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
    $language_manager = $this->container->get('language_manager');
    foreach ($language_manager->getLanguages() as $language) {
      $list[$language->getId()] = $this->createTranslation($source, $language->getId(), $values);
    }
    return $list;
  }

  /**
   * Creates single translation for source string.
   *
   * @param \Drupal\locale\StringInterface $source
   *   The source string.
   * @param string $langcode
   *   The language code.
   * @param array $values
   *   The values array.
   *
   * @return \Drupal\locale\StringInterface
   *   The translated string object.
   */
  protected function createTranslation(StringInterface $source, $langcode, array $values = []) {
    return $this->storage->createTranslation($values + [
      'lid' => $source->lid,
      'language' => $langcode,
      'translation' => $this->randomMachineName(100),
    ])->save();
  }

}
