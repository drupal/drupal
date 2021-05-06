<?php


namespace Drupal\Tests\node\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\CancellationHandlerInterface;

/**
 * Tests how nodes react to user cancellation.
 */
class UserCancellationTest extends BrowserTestBase {

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
    $this->drupalGet('/admin/people');
    $page = $this->getSession()->getPage();
    $page->checkField('Update the user ' . $alice->getDisplayName());
    $page->selectFieldOption('action', 'user_cancel_user_action');
    $page->pressButton('Apply to selected items');
    $page->selectFieldOption('user_cancel_method', CancellationHandlerInterface::METHOD_BLOCK_UNPUBLISH);
    $page->pressButton('Cancel accounts');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The update has been performed.');
    $aliceNode = Node::load($aliceNode->id());
    $this->assertFalse($aliceNode->isPublished());
    $this->assertSame($alice->id(), $aliceNode->getOwnerId());

    $page->checkField('Update the user ' . $bob->getDisplayName());
    $page->selectFieldOption('action', 'user_cancel_user_action');
    $page->pressButton('Apply to selected items');
    $page->selectFieldOption('user_cancel_method', CancellationHandlerInterface::METHOD_REASSIGN);
    $page->pressButton('Cancel accounts');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The update has been performed.');
    $bobNode = Node::load($bobNode->id());
    $this->assertTrue($bobNode->isPublished());
    $this->assertTrue($bobNode->getOwner()->isAnonymous());
  }

}
