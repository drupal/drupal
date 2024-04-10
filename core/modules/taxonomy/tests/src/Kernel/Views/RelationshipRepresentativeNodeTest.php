<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Views;

use Drupal\views\Views;

/**
 * Tests the representative node relationship for terms.
 *
 * @group taxonomy
 */
class RelationshipRepresentativeNodeTest extends TaxonomyTestBase {

  /**
   * Views used by this test.
   *
   * @var string[]
   */
  public static $testViews = ['test_groupwise_term'];

  /**
   * Tests the relationship.
   */
  public function testRelationship(): void {
    $view = Views::getView('test_groupwise_term');
    $this->executeView($view);
    $map = ['node_field_data_taxonomy_term_field_data_nid' => 'nid', 'tid' => 'tid'];
    $expected_result = [
      [
        'nid' => $this->nodes[1]->id(),
        'tid' => $this->term2->id(),
      ],
      [
        'nid' => $this->nodes[1]->id(),
        'tid' => $this->term1->id(),
      ],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $map);
  }

}
