<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Views\TaxonomyIndexTidUiTest.
 */

namespace Drupal\taxonomy\Tests\Views;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\views\Tests\ViewTestData;
use Drupal\views_ui\Tests\UITestBase;

/**
 * Tests the taxonomy index filter handler UI.
 *
 * @group taxonomy
 * @see \Drupal\taxonomy\Plugin\views\field\TaxonomyIndexTid
 */
class TaxonomyIndexTidUiTest extends UITestBase {

  use EntityReferenceTestTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_filter_taxonomy_index_tid');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'taxonomy', 'taxonomy_test_views'];

  /**
   * A nested array of \Drupal\taxonomy\TermInterface objects.
   *
   * @var \Drupal\taxonomy\TermInterface[][]
   */
  protected $terms = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ])->save();

    // Setup a hierarchy which looks like this:
    // term 0.0
    // term 1.0
    // - term 1.1
    // term 2.0
    // - term 2.1
    // - term 2.2
    for ($i = 0; $i < 3; $i++) {
      for ($j = 0; $j <= $i; $j++) {
        $this->terms[$i][$j] = $term = Term::create([
          'vid' => 'tags',
          'name' => "Term $i.$j",
          'parent' => isset($terms[$i][0]) ? $terms[$i][0]->id() : 0,
        ]);
        $term->save();
      }
    }
    ViewTestData::createTestViews(get_class($this), array('taxonomy_test_views'));
  }

  /**
   * Tests the filter UI.
   */
  public function testFilterUI() {
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');

    $result = $this->xpath('//select[@id="edit-options-value"]/option');

    // Ensure that the expected hierarchy is available in the UI.
    $counter = 0;
    for ($i = 0; $i < 3; $i++) {
      for ($j = 0; $j <= $i; $j++) {
        $option = $result[$counter++];
        $prefix = $this->terms[$i][$j]->parent->target_id ? '-' : '';
        $attributes = $option->attributes();
        $tid = (string) $attributes->value;

        $this->assertEqual($prefix . $this->terms[$i][$j]->getName(), (string) $option);
        $this->assertEqual($this->terms[$i][$j]->id(), $tid);
      }
    }

    // Ensure the autocomplete input element appears when using the 'textfield'
    // type.
    $view = entity_load('view', 'test_filter_taxonomy_index_tid');
    $display =& $view->getDisplay('default');
    $display['display_options']['filters']['tid']['type'] = 'textfield';
    $view->save();
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');
    $this->assertFieldByXPath('//input[@id="edit-options-value"]');

    // Tests \Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid::calculateDependencies().
    $expected = [
      'config' => [
        'taxonomy.vocabulary.tags',
      ],
      'content' => [
        'taxonomy_term:tags:' . Term::load(2)->uuid(),
      ],
      'module' => [
        'node',
        'taxonomy',
        'user',
      ],
    ];
    $this->assertIdentical($expected, $view->calculateDependencies()->getDependencies());
  }

  /**
   * Tests exposed taxonomy filters.
   */
  public function testExposedFilter() {
    $node_type = $this->drupalCreateContentType(['type' => 'page']);

    // Create the tag field itself.
    $field_name = 'taxonomy_tags';
    $this->createEntityReferenceField('node', $node_type->id(), $field_name, NULL, 'taxonomy_term');

    // Create 4 nodes: 1 without a term, 2 with the same term, and 1 with a
    // different term.
    $node1 = $this->drupalCreateNode();
    $node2 = $this->drupalCreateNode([
      $field_name => [['target_id' => $this->terms[1][0]->id()]],
    ]);
    $node3 = $this->drupalCreateNode([
      $field_name => [['target_id' => $this->terms[1][0]->id()]],
    ]);
    $node4 = $this->drupalCreateNode([
      $field_name => [['target_id' => $this->terms[2][0]->id()]],
    ]);

    // Only the nodes with the selected term should be shown.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $xpath = $this->xpath('//div[@class="view-content"]//a');
    $this->assertIdentical(2, count($xpath));
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node2->url()]);
    $this->assertIdentical(1, count($xpath));
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node3->url()]);
    $this->assertIdentical(1, count($xpath));

    // Expose the filter.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid', [], 'Expose filter');
    // Set the operator to 'empty' and remove the default term ID.
    $this->drupalPostForm(NULL, [
      'options[operator]' => 'empty',
      'options[value][]' => [],
    ], 'Apply');
    // Save the view.
    $this->drupalPostForm(NULL, [], 'Save');

    // After switching to 'empty' operator, the node without a term should be
    // shown.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $xpath = $this->xpath('//div[@class="view-content"]//a');
    $this->assertIdentical(1, count($xpath));
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node1->url()]);
    $this->assertIdentical(1, count($xpath));

    // Set the operator to 'not empty'.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid', ['options[operator]' => 'not empty'], 'Apply');
    // Save the view.
    $this->drupalPostForm(NULL, [], 'Save');

    // After switching to 'not empty' operator, all nodes with terms should be
    // shown.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $xpath = $this->xpath('//div[@class="view-content"]//a');
    $this->assertIdentical(3, count($xpath));
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node2->url()]);
    $this->assertIdentical(1, count($xpath));
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node3->url()]);
    $this->assertIdentical(1, count($xpath));
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node4->url()]);
    $this->assertIdentical(1, count($xpath));
  }

}
