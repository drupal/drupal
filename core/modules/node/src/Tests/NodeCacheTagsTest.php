<?php

namespace Drupal\node\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
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
  protected function createEntity() {
    // Create a "Camelids" node type.
    NodeType::create([
      'name' => 'Camelids',
      'type' => 'camelids',
    ])->save();

    // Create a "Llama" node.
    $node = Node::create(['type' => 'camelids']);
    $node->setTitle('Llama')
      ->setPublished(TRUE)
      ->save();

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultCacheContexts() {
    $defaults = parent::getDefaultCacheContexts();
    // @see \Drupal\node\Controller\NodeViewController::view()
    $defaults[] = 'user.roles:anonymous';
    return $defaults;
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
