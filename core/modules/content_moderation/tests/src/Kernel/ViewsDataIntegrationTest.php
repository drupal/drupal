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
  protected static $modules = [
    'content_moderation_test_views',
    'node',
    'content_moderation',
    'workflows',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('entity_test_mulrevpub');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installSchema('node', 'node_access');
    $this->installConfig('content_moderation');

    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $node_type->save();
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_mulrevpub', 'entity_test_mulrevpub');
    $workflow->save();

    $this->installConfig('content_moderation_test_views');
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
