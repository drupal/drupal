<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

/**
 * Tests the node access grants cache context service.
 *
 * @group node
 * @group Cache
 */
class NodeAccessCacheRedirectWarningTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'node_access_test_empty'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    node_access_rebuild();
  }

  /**
   * Ensures that node access checks don't cause cache redirect warnings.
   *
   * @covers \Drupal\node\NodeAccessControlHandler
   */
  public function testNodeAccessCacheRedirectWarning(): void {
    $this->drupalPlaceBlock('local_tasks_block');

    // Ensure that both a node_grants implementation exists, and that the
    // current user has 'view own unpublished nodes' permission. Node's access
    // control handler bypasses node grants when 'view own published nodes' is
    // granted and the node is unpublished, which means that the code path is
    // significantly different when a node is published vs. unpublished, and
    // that cache contexts vary depend on the state of the node.
    $this->assertTrue(\Drupal::moduleHandler()->hasImplementations('node_grants'));

    $author = $this->drupalCreateUser([
      'create page content',
      'edit any page content',
      'view own unpublished content',
    ]);
    $this->drupalLogin($author);

    $node = $this->drupalCreateNode(['uid' => $author->id(), 'status' => 0]);

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($node->label());

    $node->setPublished();
    $node->save();

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($node->label());

    // When the node has been viewed in both the unpublished and published state
    // a cache redirect should exist for the local tasks block. Repeating the
    // process of changing the node status and viewing the node will test that
    // no stale redirect is found.
    $node->setUnpublished();
    $node->save();

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($node->label());

    $node->setPublished();
    $node->save();

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($node->label());
  }

}
