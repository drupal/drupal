<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\system\Functional\Entity\EntityWithUriCacheTagsTestBase;

/**
 * Tests the Node entity's cache tags.
 *
 * @group node
 */
class NodeCacheTagsTest extends EntityWithUriCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

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
      ->setPublished()
      ->save();

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultCacheContexts() {
    $defaults = parent::getDefaultCacheContexts();
    // @see \Drupal\node\Controller\NodeViewController::view()
    $defaults[] = 'url.site';
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
    return ['user:' . $node->getOwnerId(), 'user_view'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdditionalCacheContextsForEntityListing() {
    return ['user.node_grants:view'];
  }

}
