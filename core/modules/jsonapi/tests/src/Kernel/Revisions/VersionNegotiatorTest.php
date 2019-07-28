<?php

namespace Drupal\Tests\jsonapi\Kernel\Revisions;

use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;
use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\jsonapi\Revisions\VersionById;
use Drupal\jsonapi\Revisions\VersionByRel;
use Drupal\jsonapi\Revisions\VersionNegotiator;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;
use Drupal\user\Entity\User;

/**
 * The test class for version negotiators.
 *
 * @coversDefaultClass \Drupal\jsonapi\Revisions\VersionNegotiator
 * @group jsonapi
 *
 * @internal
 */
class VersionNegotiatorTest extends JsonapiKernelTestBase {

  /**
   * The user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * The previous revision ID of $node.
   *
   * @var string
   */
  protected $nodePreviousRevisionId;

  /**
   * The version negotiator service.
   *
   * @var \Drupal\jsonapi\Revisions\VersionNegotiator
   */
  protected $versionNegotiator;

  /**
   * The other node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node2;

  public static $modules = [
    'node',
    'field',
    'jsonapi',
    'serialization',
    'system',
    'user',
  ];

  /**
   * Initialization tasks for the test.
   *
   * @inheritdoc
   */
  protected function setUp() {
    parent::setUp();
    // Add the entity schemas.
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    // Add the additional table schemas.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);
    $type = NodeType::create([
      'type' => 'dummy',
      'new_revision' => TRUE,
    ]);
    $type->save();

    $this->user = User::create([
      'name' => 'user1',
      'mail' => 'user@localhost',
      'status' => 1,
    ]);
    $this->user->save();

    $this->node = Node::create([
      'title' => 'dummy_title',
      'type' => 'dummy',
      'uid' => $this->user->id(),
    ]);
    $this->node->save();

    $this->nodePreviousRevisionId = $this->node->getRevisionId();

    $this->node->setNewRevision();
    $this->node->setTitle('revised_dummy_title');
    $this->node->save();

    $this->node2 = Node::create([
      'type' => 'dummy',
      'title' => 'Another test node',
      'uid' => $this->user->id(),
    ]);
    $this->node2->save();

    $entity_type_manager = \Drupal::entityTypeManager();
    $version_negotiator = new VersionNegotiator();
    $version_negotiator->addVersionNegotiator(new VersionById($entity_type_manager), 'id');
    $version_negotiator->addVersionNegotiator(new VersionByRel($entity_type_manager), 'rel');
    $this->versionNegotiator = $version_negotiator;

  }

  /**
   * @covers \Drupal\jsonapi\Revisions\VersionById::getRevision
   */
  public function testOldRevision() {
    $revision = $this->versionNegotiator->getRevision($this->node, 'id:' . $this->nodePreviousRevisionId);
    $this->assertEquals($this->node->id(), $revision->id());
    $this->assertEquals($this->nodePreviousRevisionId, $revision->getRevisionId());
  }

  /**
   * @covers \Drupal\jsonapi\Revisions\VersionById::getRevision
   */
  public function testInvalidRevisionId() {
    $this->setExpectedException(CacheableNotFoundHttpException::class, sprintf('The requested version, identified by `id:%s`, could not be found.', $this->node2->getRevisionId()));
    $this->versionNegotiator->getRevision($this->node, 'id:' . $this->node2->getRevisionId());
  }

  /**
   * @covers \Drupal\jsonapi\Revisions\VersionByRel::getRevision
   */
  public function testLatestVersion() {
    $revision = $this->versionNegotiator->getRevision($this->node, 'rel:' . VersionByRel::LATEST_VERSION);
    $this->assertEquals($this->node->id(), $revision->id());
    $this->assertEquals($this->node->getRevisionId(), $revision->getRevisionId());
  }

  /**
   * @covers \Drupal\jsonapi\Revisions\VersionByRel::getRevision
   */
  public function testCurrentVersion() {
    $revision = $this->versionNegotiator->getRevision($this->node, 'rel:' . VersionByRel::WORKING_COPY);
    $this->assertEquals($this->node->id(), $revision->id());
    $this->assertEquals($this->node->id(), $revision->id());
    $this->assertEquals($this->node->getRevisionId(), $revision->getRevisionId());
  }

  /**
   * @covers \Drupal\jsonapi\Revisions\VersionByRel::getRevision
   */
  public function testInvalidRevisionRel() {
    $this->setExpectedException(CacheableBadRequestHttpException::class, 'An invalid resource version identifier, `rel:erroneous-revision-name`, was provided.');
    $this->versionNegotiator->getRevision($this->node, 'rel:erroneous-revision-name');
  }

}
