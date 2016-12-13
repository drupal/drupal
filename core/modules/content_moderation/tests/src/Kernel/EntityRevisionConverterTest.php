<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\workflows\Entity\Workflow;

/**
 * @coversDefaultClass \Drupal\content_moderation\ParamConverter\EntityRevisionConverter
 * @group content_moderation
 */
class EntityRevisionConverterTest extends KernelTestBase {

  public static $modules = [
    'user',
    'entity_test',
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

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installSchema('system', 'router');
    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * @covers ::convert
   */
  public function testConvertNonRevisionableEntityType() {
    $entity_test = EntityTest::create([
      'name' => 'test',
    ]);

    $entity_test->save();

    /** @var \Symfony\Component\Routing\RouterInterface $router */
    $router = \Drupal::service('router.no_access_checks');
    $result = $router->match('/entity_test/' . $entity_test->id());

    $this->assertInstanceOf(EntityTest::class, $result['entity_test']);
    $this->assertEquals($entity_test->getRevisionId(), $result['entity_test']->getRevisionId());
  }

  /**
   * @covers ::convert
   */
  public function testConvertWithRevisionableEntityType() {
    $this->installConfig(['content_moderation']);
    $node_type = NodeType::create([
      'type' => 'article',
    ]);
    $node_type->save();
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'article');
    $workflow->save();

    $revision_ids = [];
    $node = Node::create([
      'title' => 'test',
      'type' => 'article',
    ]);
    $node->moderation_state->value = 'published';
    $node->save();

    $revision_ids[] = $node->getRevisionId();

    $node->setNewRevision(TRUE);
    $node->save();
    $revision_ids[] = $node->getRevisionId();

    $node->setNewRevision(TRUE);
    $node->moderation_state->value = 'draft';
    $node->save();
    $revision_ids[] = $node->getRevisionId();

    /** @var \Symfony\Component\Routing\RouterInterface $router */
    $router = \Drupal::service('router.no_access_checks');
    $result = $router->match('/node/' . $node->id() . '/edit');

    $this->assertInstanceOf(Node::class, $result['node']);
    $this->assertEquals($revision_ids[2], $result['node']->getRevisionId());
    $this->assertFalse($result['node']->isDefaultRevision());
  }

}
