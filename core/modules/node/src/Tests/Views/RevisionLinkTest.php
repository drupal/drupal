<?php

namespace Drupal\node\Tests\Views;

/**
 * Tests the different revision link handlers.
 *
 * @group node
 *
 * @see \Drupal\node\Plugin\views\field\RevisionLink
 * @see \Drupal\node\Plugin\views\field\RevisionLinkDelete
 * @see \Drupal\node\Plugin\views\field\RevisionLinkRevert
 */
class RevisionLinkTest extends NodeTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_node_revision_links'];

  /**
   * Tests revision links.
   */
  public function testRevisionLinks() {
    // Create one user which can view/revert and delete and one which can only
    // do one of them.
    $this->drupalCreateContentType(['name' => 'page', 'type' => 'page']);
    $account = $this->drupalCreateUser(['revert all revisions', 'view all revisions', 'delete all revisions', 'edit any page content', 'delete any page content']);
    $this->drupalLogin($account);
    // Create two nodes, one without an additional revision and one with a
    // revision.
    $nodes = [
      $this->drupalCreateNode(),
      $this->drupalCreateNode(),
    ];

    $first_revision = $nodes[1]->getRevisionId();
    // Create revision of the node.
    $nodes[1]->setNewRevision();
    $nodes[1]->save();
    $second_revision = $nodes[1]->getRevisionId();

    $this->drupalGet('test-node-revision-links');
    $this->assertResponse(200, 'Test view can be accessed in the path expected');
    // The first node revision should link to the node directly as you get an
    // access denied if you link to the revision.
    $url = $nodes[0]->urlInfo()->toString();
    $this->assertLinkByHref($url);
    $this->assertNoLinkByHref($url . '/revisions/' . $nodes[0]->getRevisionId() . '/view');
    $this->assertNoLinkByHref($url . '/revisions/' . $nodes[0]->getRevisionId() . '/delete');
    $this->assertNoLinkByHref($url . '/revisions/' . $nodes[0]->getRevisionId() . '/revert');

    // For the second node the current revision got set to the last revision, so
    // the first one should also link to the node page itself.
    $url = $nodes[1]->urlInfo()->toString();
    $this->assertLinkByHref($url);
    $this->assertLinkByHref($url . '/revisions/' . $first_revision . '/view');
    $this->assertLinkByHref($url . '/revisions/' . $first_revision . '/delete');
    $this->assertLinkByHref($url . '/revisions/' . $first_revision . '/revert');
    $this->assertNoLinkByHref($url . '/revisions/' . $second_revision . '/view');
    $this->assertNoLinkByHref($url . '/revisions/' . $second_revision . '/delete');
    $this->assertNoLinkByHref($url . '/revisions/' . $second_revision . '/revert');

    $accounts = [
      'view' => $this->drupalCreateUser(['view all revisions']),
      'revert' => $this->drupalCreateUser(['revert all revisions', 'edit any page content']),
      'delete' => $this->drupalCreateUser(['delete all revisions', 'delete any page content']),
    ];

    $url = $nodes[1]->urlInfo()->toString();
    // Render the view with users which can only delete/revert revisions.
    foreach ($accounts as $allowed_operation => $account) {
      $this->drupalLogin($account);
      $this->drupalGet('test-node-revision-links');
      // Check expected links.
      foreach (['revert', 'delete'] as $operation) {
        if ($operation == $allowed_operation) {
          $this->assertLinkByHref($url . '/revisions/' . $first_revision . '/' . $operation);
        }
        else {
          $this->assertNoLinkByHref($url . '/revisions/' . $first_revision . '/' . $operation);
        }
      }
    }
  }

}
