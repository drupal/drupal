<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests taxonomy field filters with translations.
 *
 * @group taxonomy
 */
class TaxonomyFieldFilterTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'taxonomy',
    'taxonomy_test_views',
    'text',
    'views',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_field_filters'];

  /**
   * The vocabulary used for creating terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * List of taxonomy term names by language.
   *
   * @var array
   */
  public $termNames = [];

  public function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    // Add two new languages.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Set up term names.
    $this->termNames = [
      'en' => 'Food in Paris',
      'es' => 'Comida en Paris',
      'fr' => 'Nourriture en Paris',
    ];

    // Create a vocabulary.
    $this->vocabulary = Vocabulary::create([
      'name' => 'Views testing tags',
      'vid' => 'views_testing_tags',
    ]);
    $this->vocabulary->save();

    // Add a translatable field to the vocabulary.
    $field = FieldStorageConfig::create([
      'field_name' => 'field_foo',
      'entity_type' => 'taxonomy_term',
      'type' => 'text',
    ]);
    $field->save();
    FieldConfig::create([
      'field_name' => 'field_foo',
      'entity_type' => 'taxonomy_term',
      'label' => 'Foo',
      'bundle' => 'views_testing_tags',
    ])->save();

    // Create term with translations.
    $taxonomy = $this->createTermWithProperties(['name' => $this->termNames['en'], 'langcode' => 'en', 'description' => $this->termNames['en'], 'field_foo' => $this->termNames['en']]);
    foreach (['es', 'fr'] as $langcode) {
      $translation = $taxonomy->addTranslation($langcode, ['name' => $this->termNames[$langcode]]);
      $translation->description->value = $this->termNames[$langcode];
      $translation->field_foo->value = $this->termNames[$langcode];
    }
    $taxonomy->save();

    Views::viewsData()->clear();

    ViewTestData::createTestViews(get_class($this), ['taxonomy_test_views']);
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Tests description and term name filters.
   */
  public function testFilters() {
    // Test the name filter page, which filters for name contains 'Comida'.
    // Should show just the Spanish translation, once.
    $this->assertPageCounts('test-name-filter', ['es' => 1, 'fr' => 0, 'en' => 0], 'Comida name filter');

    // Test the description filter page, which filters for description contains
    // 'Comida'. Should show just the Spanish translation, once.
    $this->assertPageCounts('test-desc-filter', ['es' => 1, 'fr' => 0, 'en' => 0], 'Comida description filter');

    // Test the field filter page, which filters for field_foo contains
    // 'Comida'. Should show just the Spanish translation, once.
    $this->assertPageCounts('test-field-filter', ['es' => 1, 'fr' => 0, 'en' => 0], 'Comida field filter');

    // Test the name Paris filter page, which filters for name contains
    // 'Paris'. Should show each translation once.
    $this->assertPageCounts('test-name-paris', ['es' => 1, 'fr' => 1, 'en' => 1], 'Paris name filter');

    // Test the description Paris page, which filters for description contains
    // 'Paris'. Should show each translation, once.
    $this->assertPageCounts('test-desc-paris', ['es' => 1, 'fr' => 1, 'en' => 1], 'Paris description filter');

    // Test the field Paris filter page, which filters for field_foo contains
    // 'Paris'. Should show each translation once.
    $this->assertPageCounts('test-field-paris', ['es' => 1, 'fr' => 1, 'en' => 1], 'Paris field filter');

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
      $this->assertEqual(substr_count($text, $this->termNames[$langcode]), 2 * $count, 'Translation ' . $langcode . ' has count ' . $count . ' with ' . $message);
    }
  }

  /**
   * Creates a taxonomy term with specified name and other properties.
   *
   * @param array $properties
   *   Array of properties and field values to set.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The created taxonomy term.
   */
  protected function createTermWithProperties($properties) {
    // Use the first available text format.
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);

    $properties += [
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'field_foo' => $this->randomMachineName(),
    ];

    $term = Term::create([
      'name' => $properties['name'],
      'description' => $properties['description'],
      'format' => $format->id(),
      'vid' => $this->vocabulary->id(),
      'langcode' => $properties['langcode'],
    ]);
    $term->field_foo->value = $properties['field_foo'];
    $term->save();
    return $term;
  }

}
