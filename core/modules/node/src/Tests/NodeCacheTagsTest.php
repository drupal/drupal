<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeCacheTagsTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\system\Tests\Entity\EntityWithUriCacheTagsTestBase;

/**
 * Tests the Node entity's cache tags.
 *
 * @group node
 */
class NodeCacheTagsTest extends EntityWithUriCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Give anonymous users permission to view nodes, so that we can verify the
    // cache tags of cached versions of node pages.
    $user_role = entity_load('user_role', DRUPAL_ANONYMOUS_RID);
    $user_role->grantPermission('acess content');
    $user_role->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" node type.
    entity_create('node_type', array(
      'name' => 'Camelids',
      'type' => 'camelids',
    ))->save();

    // Create a "Llama" node.
    $node = entity_create('node', array('type' => 'camelids'));
    $node->setTitle('Llama')
      ->setPublished(TRUE)
      ->save();

    return $node;
  }

  /**
   * {@inheritdoc}
   *
   * Each node must have an author.
   */
  protected function getAdditionalCacheTagsForEntity(EntityInterface $node) {
    return array('user:' . $node->getOwnerId(), 'user_view');
  }

}
