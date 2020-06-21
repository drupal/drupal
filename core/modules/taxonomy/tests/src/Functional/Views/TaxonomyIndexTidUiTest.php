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
  public static $testViews = ['test_filter_taxonomy_index_tid', 'test_taxonomy_term_name'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

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
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

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
    ViewTestData::createTestViews(get_class($this), ['taxonomy_test_views']);

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

    $result = $this->xpath('//select[@id="edit-options-value"]/option');

    // Ensure that the expected hierarchy is available in the UI.
    $counter = 0;
    for ($i = 0; $i < 3; $i++) {
      for ($j = 0; $j <= $i; $j++) {
        $option = $result[$counter++];
        $prefix = $this->terms[$i][$j]->parent->target_id ? '-' : '';
        $tid = $option->getAttribute('value');

        $this->assertEqual($prefix . $this->terms[$i][$j]->getName(), $option->getText());
        $this->assertEqual($this->terms[$i][$j]->id(), $tid);
      }
    }

    // Ensure the autocomplete input element appears when using the 'textfield'
    // type.
    $view = View::load('test_filter_taxonomy_index_tid');
    $display =& $view->getDisplay('default');
    $display['display_options']['filters']['tid']['type'] = 'textfield';
    $view->save();
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');
    $this->assertFieldById('edit-options-value', NULL);

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
    $this->assertCount(2, $xpath);
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node2->toUrl()->toString()]);
    $this->assertCount(1, $xpath);
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node3->toUrl()->toString()]);
    $this->assertCount(1, $xpath);

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
    $this->assertCount(1, $xpath);
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node1->toUrl()->toString()]);
    $this->assertCount(1, $xpath);

    // Set the operator to 'not empty'.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid', ['options[operator]' => 'not empty'], 'Apply');
    // Save the view.
    $this->drupalPostForm(NULL, [], 'Save');

    // After switching to 'not empty' operator, all nodes with terms should be
    // shown.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $xpath = $this->xpath('//div[@class="view-content"]//a');
    $this->assertCount(3, $xpath);
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node2->toUrl()->toString()]);
    $this->assertCount(1, $xpath);
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node3->toUrl()->toString()]);
    $this->assertCount(1, $xpath);
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node4->toUrl()->toString()]);
    $this->assertCount(1, $xpath);

    // Select 'Term ID' as the field to be displayed.
    $edit = ['name[taxonomy_term_field_data.tid]' => TRUE];
    $this->drupalPostForm('admin/structure/views/nojs/add-handler/test_taxonomy_term_name/default/field', $edit, 'Add and configure fields');
    // Select 'Term' and 'Vocabulary' as filters.
    $edit = [
      'name[taxonomy_term_field_data.tid]' => TRUE,
      'name[taxonomy_term_field_data.vid]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/views/nojs/add-handler/test_taxonomy_term_name/default/filter', $edit, 'Add and configure filter criteria');
    // Select 'Empty Vocabulary' and 'Autocomplete' from the list of options.
    $this->drupalPostForm('admin/structure/views/nojs/handler-extra/test_taxonomy_term_name/default/filter/tid', [], 'Apply and continue');
    // Expose the filter.
    $edit = ['options[expose_button][checkbox][checkbox]' => TRUE];
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_taxonomy_term_name/default/filter/tid', $edit, 'Expose filter');
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_taxonomy_term_name/default/filter/tid', $edit, 'Apply');
    // Filter 'Taxonomy terms' belonging to 'Empty Vocabulary'.
    $edit = ['options[value][empty_vocabulary]' => TRUE];
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_taxonomy_term_name/default/filter/vid', $edit, 'Apply');
    $this->drupalPostForm('admin/structure/views/view/test_taxonomy_term_name/edit/default', [], 'Save');
    $this->drupalPostForm(NULL, [], t('Update preview'));
    $preview = $this->xpath("//div[@class='view-content']");
    $this->assertTrue(empty($preview), 'No results.');
  }

}
