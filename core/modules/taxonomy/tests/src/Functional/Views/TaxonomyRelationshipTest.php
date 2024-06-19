<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\views\Views;

/**
 * Tests taxonomy relationships with parent term and node.
 *
 * @group taxonomy
 */
class TaxonomyRelationshipTest extends TaxonomyTestBase {

  /**
   * Stores the terms used in the tests.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms = [];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_taxonomy_term_relationship'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views, $modules);

    // Make term2 parent of term1.
    $this->term1->set('parent', $this->term2->id());
    $this->term1->save();
    // Store terms in an array for testing.
    $this->terms[] = $this->term1;
    $this->terms[] = $this->term2;
    // Only set term1 on node1 and term2 on node2 for testing.
    unset($this->nodes[0]->field_views_testing_tags[1]);
    $this->nodes[0]->save();
    unset($this->nodes[1]->field_views_testing_tags[0]);
    $this->nodes[1]->save();

    Views::viewsData()->clear();

  }

  /**
   * Tests the taxonomy parent plugin UI.
   */
  public function testTaxonomyRelationships(): void {

    // Check the generated views data of taxonomy_index.
    $views_data = Views::viewsData()->get('taxonomy_index');
    // Check the table join data.
    $this->assertEquals('tid', $views_data['table']['join']['taxonomy_term_field_data']['left_field']);
    $this->assertEquals('tid', $views_data['table']['join']['taxonomy_term_field_data']['field']);
    $this->assertEquals('nid', $views_data['table']['join']['node_field_data']['left_field']);
    $this->assertEquals('nid', $views_data['table']['join']['node_field_data']['field']);
    $this->assertEquals('entity_id', $views_data['table']['join']['taxonomy_term__parent']['left_field']);
    $this->assertEquals('tid', $views_data['table']['join']['taxonomy_term__parent']['field']);

    // Check the generated views data of taxonomy_term__parent.
    $views_data = Views::viewsData()->get('taxonomy_term__parent');
    // Check the table join data.
    $this->assertEquals('entity_id', $views_data['table']['join']['taxonomy_term__parent']['left_field']);
    $this->assertEquals('parent_target_id', $views_data['table']['join']['taxonomy_term__parent']['field']);
    $this->assertEquals('tid', $views_data['table']['join']['taxonomy_term_field_data']['left_field']);
    $this->assertEquals('entity_id', $views_data['table']['join']['taxonomy_term_field_data']['field']);
    // Check the parent relationship data.
    $this->assertEquals('taxonomy_term_field_data', $views_data['parent_target_id']['relationship']['base']);
    $this->assertEquals('tid', $views_data['parent_target_id']['relationship']['base field']);
    $this->assertEquals('Parent', $views_data['parent_target_id']['relationship']['label']);
    $this->assertEquals('standard', $views_data['parent_target_id']['relationship']['id']);
    // Check the parent filter and argument data.
    $this->assertEquals('numeric', $views_data['parent_target_id']['filter']['id']);
    $this->assertEquals('taxonomy', $views_data['parent_target_id']['argument']['id']);

    // Check an actual test view.
    $view = Views::getView('test_taxonomy_term_relationship');
    $this->executeView($view);
    /** @var \Drupal\views\ResultRow $row */
    foreach ($view->result as $index => $row) {
      // Check that the actual ID of the entity is the expected one.
      $this->assertEquals($this->terms[$index]->id(), $row->tid);

      // Also check that we have the correct result entity.
      $this->assertEquals($this->terms[$index]->id(), $row->_entity->id());
      $this->assertInstanceOf(TermInterface::class, $row->_entity);

      if (!$index) {
        $this->assertInstanceOf(TermInterface::class, $row->_relationship_entities['parent']);
        $this->assertEquals($this->term2->id(), $row->_relationship_entities['parent']->id());
        $this->assertEquals($this->term2->id(), $row->taxonomy_term_field_data_taxonomy_term__parent_tid);
      }
      $this->assertInstanceOf(NodeInterface::class, $row->_relationship_entities['nid']);
      $this->assertEquals($this->nodes[$index]->id(), $row->_relationship_entities['nid']->id());
    }

    // Test node fields are available through relationship.
    \Drupal::service('module_installer')->install(['views_ui']);
    $this->drupalLogin($this->createUser(['administer views']));
    $this->drupalGet('admin/structure/views/view/test_taxonomy_term_relationship');
    $this->click('#views-add-field');
    $this->assertSession()->pageTextContains('Body');
  }

}
