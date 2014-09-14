<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Views\TaxonomyFieldFilterTest.
 */

namespace Drupal\taxonomy\Tests\Views;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests taxonomy field filters with translations.
 *
 * @group taxonomy
 */
class TaxonomyFieldFilterTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('language', 'taxonomy', 'taxonomy_test_views', 'text', 'views', 'node', 'options');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_field_filters');

  /**
   * List of taxonomy term names by language.
   *
   * @var array
   */
  public $term_names = array();

  function setUp() {
    parent::setUp();

    // Add two new languages.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Set up term names.
    $this->term_names = array(
      'en' => 'Food in Paris',
      'es' => 'Comida en Paris',
      'fr' => 'Nouriture en Paris',
    );

    // Create a vocabulary.
    $this->vocabulary = entity_create('taxonomy_vocabulary',  array(
      'name' => 'Views testing tags',
      'vid' => 'views_testing_tags',
    ));
    $this->vocabulary->save();

    // Add a translatable field to the vocabulary.
    $field = entity_create('field_storage_config', array(
      'name' => 'field_foo',
      'entity_type' => 'taxonomy_term',
      'type' => 'text',
    ));
    $field->translatable = TRUE;
    $field->save();
    entity_create('field_instance_config', array(
      'field_name' => 'field_foo',
      'entity_type' => 'taxonomy_term',
      'label' => 'Foo',
      'bundle' => 'views_testing_tags',
    ))->save();

    // Create term with translations.
    $taxonomy = $this->createTermWithProperties(array('name' => $this->term_names['en'], 'langcode' => 'en', 'description' => $this->term_names['en'], 'field_foo' => $this->term_names['en']));
    foreach (array('es', 'fr') as $langcode) {
      $translation = $taxonomy->addTranslation($langcode, array('name' => $this->term_names[$langcode]));
      $translation->description->value = $this->term_names[$langcode];
      $translation->field_foo->value = $this->term_names[$langcode];
    }
    $taxonomy->save();

    ViewTestData::createTestViews(get_class($this), array('taxonomy_test_views'));

  }

  /**
   * Tests description and term name filters.
   */
  public function testFilters() {
    // Test the name filter page, which filters for name contains 'Comida'.
    // Should show just the Spanish translation, once.
    $this->assertPageCounts('test-name-filter', array('es' => 1, 'fr' => 0, 'en' => 0), 'Comida name filter');

    // Test the description filter page, which filters for description contains
    // 'Comida'. Should show just the Spanish translation, once.
    $this->assertPageCounts('test-desc-filter', array('es' => 1, 'fr' => 0, 'en' => 0), 'Comida description filter');

    // Test the field filter page, which filters for field_foo contains
    // 'Comida'. Should show just the Spanish translation, once.
    $this->assertPageCounts('test-field-filter', array('es' => 1, 'fr' => 0, 'en' => 0), 'Comida field filter');

    // Test the name Paris filter page, which filters for name contains
    // 'Paris'. Should show each translation once.
    $this->assertPageCounts('test-name-paris', array('es' => 1, 'fr' => 1, 'en' => 1), 'Paris name filter');

    // Test the description Paris page, which filters for description contains
    // 'Paris'. Should show each translation, once.
    $this->assertPageCounts('test-desc-paris', array('es' => 1, 'fr' => 1, 'en' => 1), 'Paris description filter');

    // Test the field Paris filter page, which filters for field_foo contains
    // 'Paris'. Should show each translation once.
    $this->assertPageCounts('test-field-paris', array('es' => 1, 'fr' => 1, 'en' => 1), 'Paris field filter');

  }

  /**
   * Asserts that the given taxonomy translation counts are correct.
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

    // Check the counts. Note that the title and body are both shown on the
    // page, and they are the same. So the title/body string should appear on
    // the page twice as many times as the input count.
    foreach ($counts as $langcode => $count) {
      $this->assertEqual(substr_count($text, $this->term_names[$langcode]), 2 * $count, 'Translation ' . $langcode . ' has count ' . $count . ' with ' . $message);
    }
  }

  /**
   * Creates a taxonomy term with specified name and other properties.
   *
   * @param array $properties
   *   Array of properties and field values to set.
   *
   * @return \Drupal\taxonomy\Term
   *   The created taxonomy term.
   */
  protected function createTermWithProperties($properties) {
    // Use the first available text format.
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);

    $properties += array(
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'field_foo' => $this->randomMachineName(),
    );

    $term = entity_create('taxonomy_term', array(
      'name' => $properties['name'],
      'description' => $properties['description'],
      'format' => $format->format,
      'vid' => $this->vocabulary->id(),
      'langcode' => $properties['langcode'],
    ));
    $term->field_foo->value = $properties['field_foo'];
    $term->save();
    return $term;
  }

}
