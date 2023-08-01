<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the autocomplete implementation of the taxonomy class.
 *
 * @group taxonomy
 */
class TermAutocompleteTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * The field to add to the content type for the taxonomy terms.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The autocomplete URL to call.
   *
   * @var string
   */
  protected $autocompleteUrl;

  /**
   * The term IDs indexed by term names.
   *
   * @var array
   */
  protected $termIds;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a vocabulary.
    $this->vocabulary = $this->createVocabulary();

    // Create 11 terms, which have some sub-string in common, in a
    // non-alphabetical order, so that we will have more than 10 matches later
    // when we test the correct number of results is returned, and we can test
    // the order of the results. The location of the sub-string to match varies
    // also, since it should not be necessary to start with the sub-string to
    // match it. Save term IDs to reuse later.
    $termNames = [
      'aaa 20 bbb',
      'aaa 70 bbb',
      'aaa 10 bbb',
      'aaa 12 bbb',
      'aaa 40 bbb',
      'aaa 11 bbb',
      'aaa 30 bbb',
      'aaa 50 bbb',
      'aaa 80',
      'aaa 90',
      'bbb 60 aaa',
    ];
    foreach ($termNames as $termName) {
      $term = $this->createTerm($this->vocabulary, ['name' => $termName]);
      $this->termIds[$termName] = $term->id();
    }

    // Create a taxonomy_term_reference field on the article Content Type that
    // uses a taxonomy_autocomplete widget.
    $this->fieldName = $this->randomMachineName();
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => $this->fieldName,
      'bundle' => 'article',
      'entity_type' => 'node',
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          // Restrict selection of terms to a single vocabulary.
          'target_bundles' => [
            $this->vocabulary->id() => $this->vocabulary->id(),
          ],
        ],
      ],
    ])->save();
    EntityFormDisplay::load('node.article.default')
      ->setComponent($this->fieldName, [
        'type' => 'entity_reference_autocomplete',
      ])
      ->save();
    EntityViewDisplay::load('node.article.default')
      ->setComponent($this->fieldName, [
        'type' => 'entity_reference_label',
      ])
      ->save();

    // Create a user and then login.
    $this->adminUser = $this->drupalCreateUser(['create article content']);
    $this->drupalLogin($this->adminUser);

    // Retrieve the autocomplete URL.
    $this->drupalGet('node/add/article');
    $field = $this->assertSession()->fieldExists("{$this->fieldName}[0][target_id]");
    $this->autocompleteUrl = $this->getAbsoluteUrl($field->getAttribute('data-autocomplete-path'));
  }

  /**
   * Helper function for JSON formatted requests.
   *
   * @param string|\Drupal\Core\Url $path
   *   Drupal path or URL to load into Mink controlled browser.
   * @param array $options
   *   (optional) Options to be forwarded to the URL generator.
   * @param string[] $headers
   *   (optional) An array containing additional HTTP request headers.
   *
   * @return string[]
   *   Array representing decoded JSON response.
   */
  protected function drupalGetJson($path, array $options = [], array $headers = []) {
    $options = array_merge_recursive(['query' => ['_format' => 'json']], $options);
    return Json::decode($this->drupalGet($path, $options, $headers));
  }

  /**
   * Tests that the autocomplete method returns the good number of results.
   *
   * @see \Drupal\taxonomy\Controller\TermAutocompleteController::autocomplete()
   */
  public function testAutocompleteCountResults() {
    // Test that no matching term found.
    $data = $this->drupalGetJson(
      $this->autocompleteUrl,
      ['query' => ['q' => 'zzz']]
    );
    $this->assertEmpty($data, 'Autocomplete returned no results');

    // Test that only one matching term found, when only one matches.
    $data = $this->drupalGetJson(
      $this->autocompleteUrl,
      ['query' => ['q' => 'aaa 10']]
    );
    $this->assertCount(1, $data, 'Autocomplete returned 1 result');

    // Test the correct number of matches when multiple are partial matches.
    $data = $this->drupalGetJson(
      $this->autocompleteUrl,
      ['query' => ['q' => 'aaa 1']]
    );
    $this->assertCount(3, $data, 'Autocomplete returned 3 results');

    // Tests that only 10 results are returned, even if there are more than 10
    // matches.
    $data = $this->drupalGetJson(
      $this->autocompleteUrl,
      ['query' => ['q' => 'aaa']]
    );
    $this->assertCount(10, $data, 'Autocomplete returned only 10 results (for over 10 matches)');
  }

  /**
   * Tests that the autocomplete method returns properly ordered results.
   *
   * @see \Drupal\taxonomy\Controller\TermAutocompleteController::autocomplete()
   */
  public function testAutocompleteOrderedResults() {
    $expectedResults = [
      'aaa 10 bbb',
      'aaa 11 bbb',
      'aaa 12 bbb',
      'aaa 20 bbb',
      'aaa 30 bbb',
      'aaa 40 bbb',
      'aaa 50 bbb',
      'aaa 70 bbb',
      'bbb 60 aaa',
    ];
    // Build $expected to match the autocomplete results.
    $expected = [];
    foreach ($expectedResults as $termName) {
      $expected[] = [
        'value' => $termName . ' (' . $this->termIds[$termName] . ')',
        'label' => $termName,
      ];
    }

    $data = $this->drupalGetJson(
      $this->autocompleteUrl,
      ['query' => ['q' => 'bbb']]
    );

    $this->assertSame($expected, $data);
  }

}
