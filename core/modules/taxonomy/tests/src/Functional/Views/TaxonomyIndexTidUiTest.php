<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\views_ui\Functional\UITestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Entity\View;

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
  public static $testViews = [
    'test_filter_taxonomy_index_tid',
    'test_taxonomy_term_name',
    'test_taxonomy_exposed_grouped_filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'views',
    'views_ui',
    'taxonomy_test_views',
  ];

  /**
   * A nested array of \Drupal\taxonomy\TermInterface objects.
   *
   * @var \Drupal\taxonomy\TermInterface[][]
   */
  protected $terms = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views, $modules);

    $this->adminUser = $this->drupalCreateUser([
      'administer taxonomy',
      'administer views',
    ]);
    $this->drupalLogin($this->adminUser);

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
          'parent' => isset($this->terms[$i][0]) ? $this->terms[$i][0]->id() : 0,
        ]);
        $term->save();
      }
    }
    ViewTestData::createTestViews(static::class, ['taxonomy_test_views']);

    Vocabulary::create([
      'vid' => 'empty_vocabulary',
      'name' => 'Empty Vocabulary',
    ])->save();
  }

  /**
   * Tests the filter UI.
   */
  public function testFilterUI() {
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');

    $result = $this->assertSession()->selectExists('edit-options-value')->findAll('css', 'option');

    // Ensure that the expected hierarchy is available in the UI.
    $counter = 0;
    for ($i = 0; $i < 3; $i++) {
      for ($j = 0; $j <= $i; $j++) {
        $option = $result[$counter++];
        $prefix = $this->terms[$i][$j]->parent->target_id ? '-' : '';
        $tid = $option->getAttribute('value');

        $this->assertEquals($prefix . $this->terms[$i][$j]->getName(), $option->getText());
        $this->assertEquals($this->terms[$i][$j]->id(), $tid);
      }
    }

    // Ensure the autocomplete input element appears when using the 'textfield'
    // type.
    $view = View::load('test_filter_taxonomy_index_tid');
    $display =& $view->getDisplay('default');
    $display['display_options']['filters']['tid']['type'] = 'textfield';
    $view->save();
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');
    $this->assertSession()->fieldExists('edit-options-value');

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
    $this->assertSame($expected, $view->calculateDependencies()->getDependencies());
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
    $this->assertSession()->pageTextNotContains($node1->getTitle());
    $this->assertSession()->linkByHrefNotExists($node1->toUrl()->toString());
    $xpath_node2_link = $this->assertSession()->buildXPathQuery('//div[@class="views-row"]//a[@href=:url and text()=:label]', [
      ':url' => $node2->toUrl()->toString(),
      ':label' => $node2->label(),
    ]);
    $this->assertSession()->elementsCount('xpath', $xpath_node2_link, 1);
    $xpath_node3_link = $this->assertSession()->buildXPathQuery('//div[@class="views-row"]//a[@href=:url and text()=:label]', [
      ':url' => $node3->toUrl()->toString(),
      ':label' => $node3->label(),
    ]);
    $this->assertSession()->elementsCount('xpath', $xpath_node3_link, 1);
    $this->assertSession()->pageTextNotContains($node4->getTitle());
    $this->assertSession()->linkByHrefNotExists($node4->toUrl()->toString());

    // Expose the filter.
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');
    $this->submitForm([], 'Expose filter');
    // Set the operator to 'empty' and remove the default term ID.
    $this->submitForm([
      'options[operator]' => 'empty',
      'options[value][]' => [],
    ], 'Apply');
    // Save the view.
    $this->submitForm([], 'Save');

    // After switching to 'empty' operator, the node without a term should be
    // shown.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $xpath_node1_link = $this->assertSession()->buildXPathQuery('//div[@class="views-row"]//a[@href=:url and text()=:label]', [
      ':url' => $node1->toUrl()->toString(),
      ':label' => $node1->label(),
    ]);
    $this->assertSession()->elementsCount('xpath', $xpath_node1_link, 1);
    $this->assertSession()->pageTextNotContains($node2->getTitle());
    $this->assertSession()->linkByHrefNotExists($node2->toUrl()->toString());
    $this->assertSession()->pageTextNotContains($node3->getTitle());
    $this->assertSession()->linkByHrefNotExists($node3->toUrl()->toString());
    $this->assertSession()->pageTextNotContains($node4->getTitle());
    $this->assertSession()->linkByHrefNotExists($node4->toUrl()->toString());

    // Set the operator to 'not empty'.
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');
    $this->submitForm(['options[operator]' => 'not empty'], 'Apply');
    // Save the view.
    $this->submitForm([], 'Save');

    // After switching to 'not empty' operator, all nodes with terms should be
    // shown.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $this->assertSession()->pageTextNotContains($node1->getTitle());
    $this->assertSession()->linkByHrefNotExists($node1->toUrl()->toString());
    $xpath_node2_link = $this->assertSession()->buildXPathQuery('//div[@class="views-row"]//a[@href=:url and text()=:label]', [
      ':url' => $node2->toUrl()->toString(),
      ':label' => $node2->label(),
    ]);
    $this->assertSession()->elementsCount('xpath', $xpath_node2_link, 1);
    $xpath_node3_link = $this->assertSession()->buildXPathQuery('//div[@class="views-row"]//a[@href=:url and text()=:label]', [
      ':url' => $node3->toUrl()->toString(),
      ':label' => $node3->label(),
    ]);
    $this->assertSession()->elementsCount('xpath', $xpath_node3_link, 1);
    $xpath_node4_link = $this->assertSession()->buildXPathQuery('//div[@class="views-row"]//a[@href=:url and text()=:label]', [
      ':url' => $node4->toUrl()->toString(),
      ':label' => $node4->label(),
    ]);
    $this->assertSession()->elementsCount('xpath', $xpath_node4_link, 1);

    // Select 'Term ID' as the field to be displayed.
    $edit = ['name[taxonomy_term_field_data.tid]' => TRUE];
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_taxonomy_term_name/default/field');
    $this->submitForm($edit, 'Add and configure fields');
    // Select 'Term' and 'Vocabulary' as filters.
    $edit = [
      'name[taxonomy_term_field_data.tid]' => TRUE,
      'name[taxonomy_term_field_data.vid]' => TRUE,
    ];
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_taxonomy_term_name/default/filter');
    $this->submitForm($edit, 'Add and configure filter criteria');
    // Select 'Empty Vocabulary' and 'Autocomplete' from the list of options.
    $this->drupalGet('admin/structure/views/nojs/handler-extra/test_taxonomy_term_name/default/filter/tid');
    $this->submitForm([], 'Apply and continue');
    // Expose the filter.
    $edit = ['options[expose_button][checkbox][checkbox]' => TRUE];
    $this->drupalGet('admin/structure/views/nojs/handler/test_taxonomy_term_name/default/filter/tid');
    $this->submitForm($edit, 'Expose filter');
    $this->drupalGet('admin/structure/views/nojs/handler/test_taxonomy_term_name/default/filter/tid');
    $this->submitForm($edit, 'Apply');
    // Filter 'Taxonomy terms' belonging to 'Empty Vocabulary'.
    $edit = ['options[value][empty_vocabulary]' => TRUE];
    $this->drupalGet('admin/structure/views/nojs/handler/test_taxonomy_term_name/default/filter/vid');
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/view/test_taxonomy_term_name/edit/default');
    $this->submitForm([], 'Save');
    $this->submitForm([], 'Update preview');
    $this->assertSession()->pageTextNotContains($node1->getTitle());
    $this->assertSession()->linkByHrefNotExists($node1->toUrl()->toString());
    $this->assertSession()->pageTextNotContains($node2->getTitle());
    $this->assertSession()->linkByHrefNotExists($node2->toUrl()->toString());
    $this->assertSession()->pageTextNotContains($node3->getTitle());
    $this->assertSession()->linkByHrefNotExists($node3->toUrl()->toString());
    $this->assertSession()->pageTextNotContains($node4->getTitle());
    $this->assertSession()->linkByHrefNotExists($node4->toUrl()->toString());
    $this->assertSession()->elementNotExists('xpath', "//div[@class='views-row']");
  }

  /**
   * Tests exposed grouped taxonomy filters.
   */
  public function testExposedGroupedFilter() {
    // Create a content type with a taxonomy field.
    $this->drupalCreateContentType(['type' => 'article']);
    $field_name = 'field_views_testing_tags';
    $this->createEntityReferenceField('node', 'article', $field_name, NULL, 'taxonomy_term');

    $nodes = [];
    for ($i = 0; $i < 3; $i++) {
      $node = [];
      $node['type'] = 'article';
      $node['field_views_testing_tags'][0]['target_id'] = $this->terms[$i][0]->id();
      $nodes[] = $this->drupalCreateNode($node);
    }

    $this->drupalGet('/admin/structure/views/nojs/handler/test_taxonomy_exposed_grouped_filter/page_1/filter/field_views_testing_tags_target_id');
    $edit = [
      'options[group_info][group_items][1][value][]' => [$this->terms[0][0]->id(), $this->terms[1][0]->id()],
      'options[group_info][group_items][2][value][]' => [$this->terms[1][0]->id(), $this->terms[2][0]->id()],
      'options[group_info][group_items][3][value][]' => [$this->terms[2][0]->id(), $this->terms[0][0]->id()],
    ];
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');

    // Visit the view's page url and validate the results.
    $this->drupalGet('/test-taxonomy-exposed-grouped-filter');
    $this->submitForm(['field_views_testing_tags_target_id' => 1], 'Apply');
    $this->assertSession()->pageTextContains($nodes[0]->getTitle());
    $this->assertSession()->pageTextContains($nodes[1]->getTitle());
    $this->assertSession()->pageTextNotContains($nodes[2]->getTitle());

    $this->submitForm(['field_views_testing_tags_target_id' => 2], 'Apply');
    $this->assertSession()->pageTextContains($nodes[1]->getTitle());
    $this->assertSession()->pageTextContains($nodes[2]->getTitle());
    $this->assertSession()->pageTextNotContains($nodes[0]->getTitle());

    $this->submitForm(['field_views_testing_tags_target_id' => 3], 'Apply');
    $this->assertSession()->pageTextContains($nodes[0]->getTitle());
    $this->assertSession()->pageTextContains($nodes[2]->getTitle());
    $this->assertSession()->pageTextNotContains($nodes[1]->getTitle());
  }

  /**
   * Tests that an exposed taxonomy filter doesn't show unpublished terms.
   */
  public function testExposedUnpublishedFilterOptions() {
    $this->terms[1][0]->setUnpublished()->save();
    // Expose the filter.
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');
    $this->submitForm([], 'Expose filter');
    $edit = ['options[expose_button][checkbox][checkbox]' => TRUE];
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');
    // Make sure the unpublished term is shown to the admin user.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[0][0]->id() . '"]'));
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[1][0]->id() . '"]'));
    $this->drupalLogout();
    $this->drupalGet('test-filter-taxonomy-index-tid');
    // Make sure the unpublished term isn't shown to the anonymous user.
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[0][0]->id() . '"]'));
    $this->assertEmpty($this->cssSelect('option[value="' . $this->terms[1][0]->id() . '"]'));

    // Tests that the term also isn't shown when not showing hierarchy.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'options[hierarchy]' => FALSE,
    ];
    $this->drupalGet('admin/structure/views/nojs/handler-extra/test_filter_taxonomy_index_tid/default/filter/tid');
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[0][0]->id() . '"]'));
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[1][0]->id() . '"]'));
    $this->drupalLogout();
    $this->drupalGet('test-filter-taxonomy-index-tid');
    // Make sure the unpublished term isn't shown to the anonymous user.
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[0][0]->id() . '"]'));
    $this->assertEmpty($this->cssSelect('option[value="' . $this->terms[1][0]->id() . '"]'));
  }

}
