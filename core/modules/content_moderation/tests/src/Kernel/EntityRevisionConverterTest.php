<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestRev;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The router without access checks.
   *
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installSchema('system', 'router');
    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
    $this->installConfig(['content_moderation']);
    \Drupal::service('router.builder')->rebuild();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->router = $this->container->get('router.no_access_checks');
  }

  /**
   * @covers ::convert
   */
  public function testConvertNonRevisionableEntityType() {
    $entity_test = EntityTest::create([
      'name' => 'test',
    ]);

    $entity_test->save();

    $result = $this->router->match('/entity_test/' . $entity_test->id());

    $this->assertInstanceOf(EntityTest::class, $result['entity_test']);
    $this->assertEquals($entity_test->getRevisionId(), $result['entity_test']->getRevisionId());
  }

  /**
   * @covers ::applies
   */
  public function testConvertNoEditFormHandler() {
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_rev', 'entity_test_rev');
    $workflow->save();

    $entity_test_rev = EntityTestRev::create([
      'name' => 'Default Revision',
      'moderation_state' => 'published',
    ]);
    $entity_test_rev->save();

    $entity_test_rev->name = 'Pending revision';
    $entity_test_rev->moderation_state = 'draft';
    $entity_test_rev->save();

    // Ensure the entity type does not provide an explicit 'edit' form class.
    $definition = $this->entityTypeManager->getDefinition($entity_test_rev->getEntityTypeId());
    $this->assertNull($definition->getFormClass('edit'));

    // Ensure the revision converter is invoked for the edit route.
    $result = $this->router->match("/entity_test_rev/manage/{$entity_test_rev->id()}/edit");
    $this->assertEquals($entity_test_rev->getRevisionId(), $result['entity_test_rev']->getRevisionId());
  }

  /**
   * @covers ::convert
   */
  public function testConvertWithRevisionableEntityType() {
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

    $result = $this->router->match('/node/' . $node->id() . '/edit');

    $this->assertInstanceOf(Node::class, $result['node']);
    $this->assertEquals($revision_ids[2], $result['node']->getRevisionId());
    $this->assertFalse($result['node']->isDefaultRevision());
  }

}
