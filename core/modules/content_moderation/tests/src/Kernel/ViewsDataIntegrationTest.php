<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the views integration of content_moderation.
 *
 * @group content_moderation
 */
class ViewsDataIntegrationTest extends ViewsKernelTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_moderation_test_views',
    'node',
    'content_moderation',
    'workflows',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('entity_test_mulrevpub');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installSchema('node', 'node_access');
    $this->installConfig('content_moderation_test_views');
    $this->installConfig('content_moderation');

    $node_type = NodeType::create([
      'type' => 'page',
    ]);
    $node_type->save();
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_mulrevpub', 'entity_test_mulrevpub');
    $workflow->save();
  }

  /**
   * Tests the join from the revision data table to the moderation state table.
   */
  public function testContentModerationStateRevisionJoin() {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test title first revision',
    ]);
    $node->moderation_state->value = 'published';
    $node->save();

    $revision = clone $node;
    $revision->setNewRevision(TRUE);
    $revision->isDefaultRevision(FALSE);
    $revision->title->value = 'Test title second revision';
    $revision->moderation_state->value = 'draft';
    $revision->save();

    $view = Views::getView('test_content_moderation_revision_test');
    $view->execute();

    $expected_result = [
      [
        'vid' => $node->getRevisionId(),
        'moderation_state' => 'published',
      ],
      [
        'vid' => $revision->getRevisionId(),
        'moderation_state' => 'draft',
      ],
    ];
    $this->assertIdenticalResultset($view, $expected_result, ['vid' => 'vid', 'moderation_state' => 'moderation_state']);
  }

  /**
   * Tests the join from the data table to the moderation state table.
   */
  public function testContentModerationStateBaseJoin() {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test title first revision',
    ]);
    $node->moderation_state->value = 'published';
    $node->save();

    $revision = clone $node;
    $revision->setNewRevision(TRUE);
    $revision->isDefaultRevision(FALSE);
    $revision->title->value = 'Test title second revision';
    $revision->moderation_state->value = 'draft';
    $revision->save();

    $view = Views::getView('test_content_moderation_base_table_test');
    $view->execute();

    $expected_result = [
      [
        'nid' => $node->id(),
        // Joins from the base table to the default revision of the
        // content_moderation.
        'moderation_state' => 'published',
        // Joins from the revision table to the default revision of the
        // content_moderation.
        'moderation_state_1' => 'published',
        // Joins from the revision table to the revision of the
        // content_moderation.
        'moderation_state_2' => 'published',
      ],
    ];
    $this->assertIdenticalResultset($view, $expected_result, ['nid' => 'nid', 'moderation_state' => 'moderation_state', 'moderation_state_1' => 'moderation_state_1', 'moderation_state_2' => 'moderation_state_2']);
  }

  /**
   * Tests the content moderation state views field.
   */
  public function testContentModerationStateField() {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test title',
    ]);
    $node->moderation_state->value = 'published';
    $node->save();

    $view = Views::getView('test_content_moderation_field_state_test');
    $view->execute();

    $expected_result = [
      [
        'title' => 'Test title',
        'moderation_state' => 'published',
      ],
    ];
    $this->assertIdenticalResultset($view, $expected_result, ['title' => 'title', 'moderation_state' => 'moderation_state']);
  }

}
