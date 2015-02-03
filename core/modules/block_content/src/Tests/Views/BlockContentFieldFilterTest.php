<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\Views\BlockContentFieldFilterTest.
 */

namespace Drupal\block_content\Tests\Views;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests block_content field filters with translations.
 *
 * @group block_content
 */
class BlockContentFieldFilterTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('language');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_field_filters');

  /**
   * List of block_content infos by language.
   *
   * @var array
   */
  public $block_content_infos = array();


  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    // Add two new languages.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Make the body field translatable. The info is already translatable by
    // definition.
    $field_storage = FieldStorageConfig::loadByName('block_content', 'body');
    $field_storage->translatable = TRUE;
    $field_storage->save();

    // Set up block_content infos.
    $this->block_content_infos = array(
      'en' => 'Food in Paris',
      'es' => 'Comida en Paris',
      'fr' => 'Nouriture en Paris',
    );

    // Create block_content with translations.
    $block_content = $this->createBlockContent(array('info' => $this->block_content_infos['en'], 'langcode' => 'en', 'type' => 'basic', 'body' => array(array('value' => $this->block_content_infos['en']))));
    foreach (array('es', 'fr') as $langcode) {
      $translation = $block_content->addTranslation($langcode, array('info' => $this->block_content_infos[$langcode]));
      $translation->body->value = $this->block_content_infos[$langcode];
    }
    $block_content->save();
  }

  /**
   * Tests body and info filters.
   */
  public function testFilters() {
    // Test the info filter page, which filters for info contains 'Comida'.
    // Should show just the Spanish translation, once.
    $this->assertPageCounts('test-info-filter', array('es' => 1, 'fr' => 0, 'en' => 0), 'Comida info filter');

    // Test the body filter page, which filters for body contains 'Comida'.
    // Should show just the Spanish translation, once.
    $this->assertPageCounts('test-body-filter', array('es' => 1, 'fr' => 0, 'en' => 0), 'Comida body filter');

    // Test the info Paris filter page, which filters for info contains
    // 'Paris'. Should show each translation once.
    $this->assertPageCounts('test-info-paris', array('es' => 1, 'fr' => 1, 'en' => 1), 'Paris info filter');

    // Test the body Paris filter page, which filters for body contains
    // 'Paris'. Should show each translation once.
    $this->assertPageCounts('test-body-paris', array('es' => 1, 'fr' => 1, 'en' => 1), 'Paris body filter');
  }

  /**
   * Asserts that the given block_content translation counts are correct.
   *
   * @param string $path
   *   Path of the page to test.
   * @param array $counts
   *   Array whose keys are languages, and values are the number of times
   *   that translation should be shown on the given page.
   * @param string $message
   *   Message suffix to display.
   */
  protected function assertPageCounts($path, $counts, $message) {
    // Get the text of the page.
    $this->drupalGet($path);
    $text = $this->getTextContent();

    foreach ($counts as $langcode => $count) {
      $this->assertEqual(substr_count($text, $this->block_content_infos[$langcode]), $count, 'Translation ' . $langcode . ' has count ' . $count . ' with ' . $message);
    }
  }
}
