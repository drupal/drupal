<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * @coversDefaultClass \Drupal\content_moderation\ParamConverter\EntityRevisionConverter
 * @group content_moderation
 * @group legacy
 */
class EntityRevisionConverterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'system',
    'content_moderation',
    'node',
    'workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
  }

  /**
   * @covers ::convert
   * @expectedDeprecationMessage The load_pending_revision flag has been deprecated. You should use load_latest_revision instead.
   */
  public function testDeprecatedLoadPendingRevisionFlag() {
    NodeType::create([
      'type' => 'article',
    ])->save();

    $node = Node::create([
      'title' => 'test',
      'type' => 'article',
    ]);
    $node->save();

    $node->isDefaultRevision(FALSE);
    $node->setNewRevision(TRUE);
    $node->save();

    $converted = $this->container->get('paramconverter.latest_revision')->convert($node->id(), [
      'load_pending_revision' => TRUE,
      'type' => 'entity:node',
    ], 'node', []);
    $this->assertEquals($converted->getLoadedRevisionId(), $node->getLoadedRevisionId());
  }

}
