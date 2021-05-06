<?php

namespace Drupal\Tests\node\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\user\Traits\UserCancellationTrait;
use Drupal\user\CancellationHandlerInterface;

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
    $node_type = $this->drupalCreateContentType();
    $alice = $this->drupalCreateUser();
    $bob = $this->drupalCreateUser();

    $aliceNode = $this->drupalCreateNode([
      'type' => $node_type->id(),
      'uid' => $alice->id(),
    ]);
    $this->assertSame($alice->id(), $aliceNode->getOwnerId());
    $this->assertTrue($aliceNode->isPublished());

    $bobNode = $this->drupalCreateNode([
      'type' => $node_type->id(),
      'uid' => $bob->id(),
    ]);
    $this->assertSame($bob->id(), $bobNode->getOwnerId());
    $this->assertTrue($bobNode->isPublished());

    $this->drupalLogin($this->rootUser);
    $this->cancelUser($alice, CancellationHandlerInterface::METHOD_BLOCK_UNPUBLISH);
    $aliceNode = Node::load($aliceNode->id());
    $this->assertFalse($aliceNode->isPublished());
    $this->assertSame($alice->id(), $aliceNode->getOwnerId());

    $this->cancelUser($bob, CancellationHandlerInterface::METHOD_REASSIGN);
    $bobNode = Node::load($bobNode->id());
    $this->assertTrue($bobNode->isPublished());
    $this->assertTrue($bobNode->getOwner()->isAnonymous());
  }

}
