<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the views moderation_state field sorting integration.
 *
 * @group content_moderation
 */
class ViewsModerationStateSortTest extends ViewsKernelTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'content_moderation',
    'workflows',
    'workflow_type_test',
    'entity_test',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(FALSE);

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('entity_test_no_bundle');
    $this->installSchema('node', 'node_access');
    $this->installConfig('content_moderation');

    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->save();

    ConfigurableLanguage::createFromLangcode('fr')->save();

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addState('zz_draft', 'ZZ Draft');
    $workflow->getTypePlugin()->addState('aa_draft', 'AA Draft');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();
  }

  /**
   * Test sorting with a standard data base table.
   */
  public function testSortBaseTable() {
    $this->enableModules(['content_moderation_test_views']);
    $this->installConfig(['content_moderation_test_views']);

    // Create two revisions. The sorted revision will be 'zz_draft' since it
    // will be attached to the default revision of the entity.
    $first_node = Node::create([
      'type' => 'example',
      'title' => 'Foo',
      'moderation_state' => 'aa_draft',
    ]);
    $first_node->save();
    $first_node->moderation_state = 'zz_draft';
    $first_node->save();

    // Create a second published node, which falls between aa_draft and zz_draft
    // for the purposes of testing.
    $second_node = Node::create([
      'type' => 'example',
      'title' => 'Foo',
      'moderation_state' => 'published',
    ]);
    $second_node->save();

    // Ascending order will see 'published' followed by 'zz_draft'.
    $this->assertSortResults('test_content_moderation_state_sort_base_table', 'nid', 'ASC', [
      ['nid' => $second_node->id()],
      ['nid' => $first_node->id()],
    ]);
    // Descending will reverse the order.
    $this->assertSortResults('test_content_moderation_state_sort_base_table', 'nid', 'DESC', [
      ['nid' => $first_node->id()],
      ['nid' => $second_node->id()],
    ]);
  }

  /**
   * Test sorting with the revision base table.
   */
  public function testSortRevisionBaseTable() {
    $this->enableModules(['content_moderation_test_views']);
    $this->installConfig(['content_moderation_test_views']);

    // Create a series of node revisions in different states and store
    // each revision ID at the given state.
    $node = Node::create([
      'type' => 'example',
      'title' => 'Foo',
      'moderation_state' => 'published',
    ]);
    $node->save();
    $published_revision_id = $node->getRevisionId();

    $node->moderation_state = 'draft';
    $node->save();
    $draft_revision_id = $node->getRevisionId();

    $node->moderation_state = 'aa_draft';
    $node->save();
    $aa_draft_revision_id = $node->getRevisionId();

    $translated = $node->addTranslation('fr');
    $translated->moderation_state = 'zz_draft';
    $translated->title = 'Translated';
    $translated->save();
    $zz_draft_revision_id = $translated->getRevisionId();

    // A second aa_draft revision will be created for the non-translated
    // revision. Since in this case there will be two revisions with "aa_draft"
    // we add another sort in content_moderation_test_views_views_query_alter.
    // Secondary sorting is not an option in views when using exposed sorting
    // and table click sorting, so in order to maintain the same level of
    // coverage this is required.
    $second_aa_draft_revision_id = $translated->getRevisionId();

    $this->assertSortResults('test_content_moderation_state_sort_revision_table', 'vid', 'ASC', [
      ['vid' => $aa_draft_revision_id],
      ['vid' => $second_aa_draft_revision_id],
      ['vid' => $draft_revision_id],
      ['vid' => $published_revision_id],
      ['vid' => $zz_draft_revision_id],
    ]);

    $this->assertSortResults('test_content_moderation_state_sort_revision_table', 'vid', 'DESC', [
      ['vid' => $zz_draft_revision_id],
      ['vid' => $published_revision_id],
      ['vid' => $draft_revision_id],
      ['vid' => $aa_draft_revision_id],
      ['vid' => $second_aa_draft_revision_id],
    ]);
  }

  /**
   * Assert the order of a views sort result.
   *
   * @param string $view_id
   *   The ID of the view.
   * @param string $column
   *   The column associated with each row.
   * @param string $order
   *   The sort order.
   * @param array $expected
   *   The expected results array.
   */
  protected function assertSortResults($view_id, $column, $order, array $expected) {
    // Test with exposed input.
    $view = Views::getView($view_id);
    $view->setExposedInput([
      'sort_by' => 'moderation_state',
      'sort_order' => $order,
    ]);
    $view->execute();
    $this->assertIdenticalResultset($view, $expected, [$column => $column]);

    // Test click sorting.
    $view = Views::getView($view_id);
    $view->removeHandler('default', 'sort', 'moderation_state');
    $request = new Request([
      'order' => 'moderation_state',
      'sort' => strtolower($order),
    ]);
    $view->setRequest($request);
    $view->execute();
    $this->assertIdenticalResultset($view, $expected, [$column => $column]);
  }

}
