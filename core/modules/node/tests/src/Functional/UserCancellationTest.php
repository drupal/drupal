<?php

namespace Drupal\Tests\node\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\user\Traits\UserCancellationTrait;
use Drupal\user\CancellationHandlerInterface;
use Drupal\user\UserInterface;

/**
 * Tests how nodes react to user cancellation.
 *
 * @group node
 */
class UserCancellationTest extends BrowserTestBase {

  use UserCancellationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
  ];

  /**
   * Tests how nodes react to user cancellation.
   */
  public function testUserCancellation(): void {
    $this->drupalCreateContentType(['type' => 'test']);
    $alice = $this->drupalCreateUser();
    $bob = $this->drupalCreateUser();
    $charlie = $this->drupalCreateUser();

    $alice_node = $this->createNodeForUser($alice);
    $bob_node = $this->createNodeForUser($bob);
    $charlie_node = $this->createNodeForUser($charlie);

    $previous_charlie_node_vid = $charlie_node->getRevisionId();
    $charlie_node->setNewRevision();
    $charlie_node->save();

    $this->drupalLogin($this->rootUser);
    $this->cancelUser($alice, CancellationHandlerInterface::METHOD_BLOCK_UNPUBLISH);
    $alice_node = Node::load($alice_node->id());
    $this->assertFalse($alice_node->isPublished());
    $this->assertSame($alice->id(), $alice_node->getOwnerId());

    $this->cancelUser($bob, CancellationHandlerInterface::METHOD_REASSIGN);
    $bob_node = Node::load($bob_node->id());
    $this->assertTrue($bob_node->isPublished());
    $this->assertTrue($bob_node->getOwner()->isAnonymous());

    $this->cancelUser($charlie, CancellationHandlerInterface::METHOD_DELETE);
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->container->get('entity_type.manager')
      ->getStorage('node');
    $this->assertNull($node_storage->load($charlie_node->id()));
    $this->assertNull($node_storage->loadRevision($previous_charlie_node_vid));
  }

  /**
   * Creates a node associated with a user account.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account that will own the node.
   *
   * @return \Drupal\node\Entity\Node
   *   The new, saved node.
   */
  protected function createNodeForUser(UserInterface $user): Node {
    $node = $this->drupalCreateNode([
      'type' => 'test',
      'uid' => $user->id(),
    ]);
    $this->assertSame($user->id(), $node->getOwnerId());
    $this->assertTrue($node->isPublished());
    return $node;
  }

}
