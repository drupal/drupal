<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Url;

/**
 * Tests reverting node revisions correctly sets authorship information.
 *
 * @group node
 */
class NodeRevisionsAuthorTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests node authorship is retained after reverting revisions.
   */
  public function testNodeRevisionRevertAuthors(): void {
    // Create and log in user.
    $initialUser = $this->drupalCreateUser([
      'view page revisions',
      'revert page revisions',
      'edit any page content',
    ]);
    $initialRevisionUser = $this->drupalCreateUser();
    // Third user is an author only and needs no permissions
    $initialRevisionAuthor = $this->drupalCreateUser();

    // Create initial node (author: $user1).
    $this->drupalLogin($initialUser);
    $node = $this->drupalCreateNode();
    $originalRevisionId = $node->getRevisionId();
    $originalBody = $node->body->value;
    $originalTitle = $node->getTitle();

    // Create a revision (as $initialUser) showing $initialRevisionAuthor
    // as author.
    $node->setRevisionLogMessage('Changed author');
    $revisedTitle = $this->randomMachineName();
    $node->setTitle($revisedTitle);
    $revisedBody = $this->randomMachineName(32);
    $node->set('body', [
      'value' => $revisedBody,
      'format' => filter_default_format(),
    ]);
    $node->setOwnerId($initialRevisionAuthor->id());
    $node->setRevisionUserId($initialRevisionUser->id());
    $node->setNewRevision();
    $node->save();
    $revisedRevisionId = $node->getRevisionId();

    $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');

    self::assertEquals($node->getOwnerId(), $initialRevisionAuthor->id());
    self::assertEquals($node->getRevisionUserId(), $initialRevisionUser->id());

    // Revert to the original node revision.
    $this->drupalGet(Url::fromRoute('node.revision_revert_confirm', [
      'node' => $node->id(),
      'node_revision' => $originalRevisionId,
    ]));
    $this->submitForm([], 'Revert');
    $this->assertSession()->pageTextContains(\sprintf('Basic page %s has been reverted', $originalTitle));

    // With the revert done, reload the node and verify that the authorship
    // fields have reverted correctly.
    $nodeStorage->resetCache([$node->id()]);
    /** @var \Drupal\node\NodeInterface $revertedNode */
    $revertedNode = $nodeStorage->load($node->id());
    self::assertEquals($originalBody, $revertedNode->body->value);
    self::assertEquals($initialUser->id(), $revertedNode->getOwnerId());
    self::assertEquals($initialUser->id(), $revertedNode->getRevisionUserId());

    // Revert again to the revised version and check that node author and
    // revision author fields are correct.
    // Revert to the original node.
    $this->drupalGet(Url::fromRoute('node.revision_revert_confirm', [
      'node' => $revertedNode->id(),
      'node_revision' => $revisedRevisionId,
    ]));
    $this->submitForm([], 'Revert');
    $this->assertSession()->pageTextContains(\sprintf('Basic page %s has been reverted', $revisedTitle));

    // With the reversion done, reload the node and verify that the
    // authorship fields have reverted correctly.
    $nodeStorage->resetCache([$revertedNode->id()]);
    /** @var \Drupal\node\NodeInterface $re_reverted_node */
    $re_reverted_node = $nodeStorage->load($revertedNode->id());
    self::assertEquals($revisedBody, $re_reverted_node->body->value);
    self::assertEquals($initialRevisionAuthor->id(), $re_reverted_node->getOwnerId());
    // The new revision user will be the current logged in user as set in
    // NodeRevisionRevertForm.
    self::assertEquals($initialUser->id(), $re_reverted_node->getRevisionUserId());
  }

}
