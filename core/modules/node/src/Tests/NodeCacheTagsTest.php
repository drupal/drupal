<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeCacheTagsTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\system\Tests\Entity\EntityWithUriCacheTagsTestBase;
use Drupal\user\Entity\Role;

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
   */
  protected function getAdditionalCacheContextsForEntity(EntityInterface $entity) {
    return ['timezone'];
  }

  /**
   * {@inheritdoc}
   *
   * Each node must have an author.
   */
  protected function getAdditionalCacheTagsForEntity(EntityInterface $node) {
    return array('user:' . $node->getOwnerId(), 'user_view');
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdditionalCacheContextsForEntityListing() {
    return ['user.node_grants:view'];
  }

}
