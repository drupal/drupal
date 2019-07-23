<?php

namespace Drupal\Tests\node\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Tests legacy user functionality.
 *
 * @group user
 * @group legacy
 */
class NodeLegacyTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create the node bundles required for testing.
    $type = NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ]);
    $type->save();

    $this->installSchema('node', 'node_access');
  }

  /**
   * @expectedDeprecation node_load_multiple() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\node\Entity\Node::loadMultiple(). See https://www.drupal.org/node/2266845
   * @expectedDeprecation node_load() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\node\Entity\Node::load(). See https://www.drupal.org/node/2266845
   * @expectedDeprecation node_type_load() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal\node\Entity\NodeType::load(). See https://www.drupal.org/node/2266845
   */
  public function testEntityLegacyCode() {
    $this->assertCount(0, node_load_multiple());
    Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
    ])->save();
    $this->assertCount(1, node_load_multiple());
    Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
    ])->save();
    $this->assertCount(2, node_load_multiple());

    $this->assertNull(node_load(30));
    $this->assertInstanceOf(NodeInterface::class, node_load(1));
    $this->assertNull(node_type_load('a_node_type_does_not_exist'));
    $this->assertInstanceOf(NodeTypeInterface::class, node_type_load('page'));
  }

  /**
   * @expectedDeprecation node_view() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal::entityTypeManager()->getViewBuilder('node')->view() instead. See https://www.drupal.org/node/3033656
   * @expectedDeprecation node_view_multiple() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal::entityTypeManager()->getViewBuilder('node')->viewMultiple() instead. See https://www.drupal.org/node/3033656
   */
  public function testNodeView() {
    $entity = Node::create(['type' => 'page']);
    $this->assertNotEmpty(node_view($entity));
    $entities = [
      Node::create(['type' => 'page']),
      Node::create(['type' => 'page']),
    ];
    $this->assertEquals(4, count(node_view_multiple($entities)));
  }

  /**
   * Tests that NodeType::isNewRevision() triggers a deprecation error.
   *
   * @expectedDeprecation NodeType::isNewRevision is deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use Drupal\Core\Entity\RevisionableEntityBundleInterface::shouldCreateNewRevision() instead. See https://www.drupal.org/node/3067365
   */
  public function testNodeTypeIsNewRevision() {
    $type = NodeType::load('page');
    $this->assertSame($type->shouldCreateNewRevision(), $type->isNewRevision());
  }

  /**
   * Tests that Node::setRevisionAuthorId() triggers a deprecation error.
   *
   * @expectedDeprecation Drupal\node\Entity\Node::setRevisionAuthorId is deprecated in drupal:8.2.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\RevisionLogInterface::setRevisionUserId() instead. See https://www.drupal.org/node/3069750
   */
  public function testNodeSetRevisionAuthorId() {
    $user = $this->createUser(['uid' => 2, 'name' => 'Test']);
    $entity = Node::create([
      'type' => 'page',
    ]);
    $entity->setRevisionAuthorId($user->id());
    $this->assertSame($user->id(), $entity->getRevisionUser()->id());
  }

  /**
   * Tests that Node::getRevisionAuthor() triggers a deprecation error.
   *
   * @expectedDeprecation Drupal\node\Entity\Node::getRevisionAuthor is deprecated in drupal:8.2.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Entity\RevisionLogInterface::getRevisionUser() instead. See https://www.drupal.org/node/3069750
   */
  public function testNodeGetRevisionAuthor() {
    $user = $this->createUser(['uid' => 2, 'name' => 'Test']);
    $entity = Node::create([
      'type' => 'page',
    ]);
    $entity->setRevisionUser($user);
    $this->assertSame($user->id(), $entity->getRevisionAuthor()->id());
  }

}
