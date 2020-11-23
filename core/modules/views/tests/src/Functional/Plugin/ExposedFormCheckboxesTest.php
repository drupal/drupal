<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests exposed forms functionality.
 *
 * @group views
 */
class ExposedFormCheckboxesTest extends ViewTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_exposed_form_checkboxes'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views_ui', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test terms.
   *
   * @var array
   */
  public $terms = [];

  /**
   * Vocabulary for testing checkbox options.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  public $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(FALSE);

    // Create a vocabulary and entity reference field so we can test the "is all
    // of" filter operator. Must be done ahead of the view import so the
    // vocabulary is in place to meet the view dependencies.
    $vocabulary = Vocabulary::create([
      'name' => 'test_exposed_checkboxes',
      'vid' => 'test_exposed_checkboxes',
      'nodes' => ['article' => 'article'],
    ]);
    $vocabulary->save();
    $this->vocabulary = $vocabulary;

    ViewTestData::createTestViews(self::class, ['views_test_config']);
    $this->enableViewsTestModule();

    // Create two content types.
    $this->drupalCreateContentType(['type' => 'article']);
    $this->drupalCreateContentType(['type' => 'page']);

    // Create some random nodes: 5 articles, one page.
    for ($i = 0; $i < 5; $i++) {
      $this->drupalCreateNode(['type' => 'article']);
    }
    $this->drupalCreateNode(['type' => 'page']);
  }

  /**
   * Tests overriding the default render option with checkboxes.
   */
  public function testExposedFormRenderCheckboxes() {
    // Use a test theme to convert multi-select elements into checkboxes.
    \Drupal::service('theme_installer')->install(['views_test_checkboxes_theme']);
    $this->config('system.theme')
      ->set('default', 'views_test_checkboxes_theme')
      ->save();

    // Only display 5 items per page so we can test that paging works.
    $view = Views::getView('test_exposed_form_checkboxes');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['pager']['options']['items_per_page'] = 5;

    $view->save();
    $this->drupalGet('test_exposed_form_checkboxes');

    $actual = $this->xpath('//form//input[@type="checkbox" and @name="type[article]"]');
    $this->assertCount(1, $actual, 'Article option renders as a checkbox.');
    $actual = $this->xpath('//form//input[@type="checkbox" and @name="type[page]"]');
    $this->assertCount(1, $actual, 'Page option renders as a checkbox');

    // Ensure that all results are displayed.
    $rows = $this->xpath("//div[contains(@class, 'views-row')]");
    $this->assertCount(5, $rows, '5 rows are displayed by default on the first page when no options are checked.');

    $this->clickLink('Page 2');
    $rows = $this->xpath("//div[contains(@class, 'views-row')]");
    $this->assertCount(1, $rows, '1 row is displayed by default on the second page when no options are checked.');
    $this->assertNoText('An illegal choice has been detected. Please contact the site administrator.');
  }

  /**
   * Tests that "is all of" filters work with checkboxes.
   */
  public function testExposedIsAllOfFilter() {
    foreach (['Term 1', 'Term 2', 'Term 3'] as $term_name) {
      // Add a few terms to the new vocabulary.
      $term = Term::create([
        'name' => $term_name,
        'vid' => $this->vocabulary->id(),
      ]);
      $term->save();
      $this->terms[] = $term;
    }

    // Create a field.
    $field_name = mb_strtolower($this->randomMachineName());
    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => FALSE,
    ];
    $this->createEntityReferenceField('node', 'article', $field_name, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // Add some test nodes.
    $this->createNode([
      'type' => 'article',
      $field_name => [$this->terms[0]->id(), $this->terms[1]->id()],
    ]);
    $this->createNode([
      'type' => 'article',
      $field_name => [$this->terms[0]->id(), $this->terms[2]->id()],
    ]);

    // Use a test theme to convert multi-select elements into checkboxes.
    \Drupal::service('theme_installer')->install(['views_test_checkboxes_theme']);
    $this->config('system.theme')
      ->set('default', 'views_test_checkboxes_theme')
      ->save();

    $this->drupalGet('test_exposed_form_checkboxes');

    // Ensure that all results are displayed.
    $rows = $this->xpath("//div[contains(@class, 'views-row')]");
    $this->assertCount(8, $rows, 'All rows are displayed by default on the first page when no options are checked.');
    $this->assertNoText('An illegal choice has been detected. Please contact the site administrator.');

    // Select one option and ensure we still have results.
    $tid = $this->terms[0]->id();
    $this->submitForm(["tid[$tid]" => $tid], 'Apply');

    // Ensure only nodes tagged with $tid are displayed.
    $rows = $this->xpath("//div[contains(@class, 'views-row')]");
    $this->assertCount(2, $rows, 'Correct rows are displayed when a tid is selected.');
    $this->assertNoText('An illegal choice has been detected. Please contact the site administrator.');
  }

}
