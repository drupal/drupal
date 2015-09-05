<?php

/**
 * @file
 * Contains \Drupal\paramconverter_test\TestControllers.
 */

namespace Drupal\paramconverter_test;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;

/**
 * Controller routine for testing the paramconverter.
 */
class TestControllers {

  public function testUserNodeFoo(EntityInterface $user, NodeInterface $node, $foo) {
    $foo = is_object($foo) ? $foo->label() : $foo;
    return ['#markup' => "user: {$user->label()}, node: {$node->label()}, foo: $foo"];
  }

  public function testNodeSetParent(NodeInterface $node, NodeInterface $parent) {
    return ['#markup' => "Setting '{$parent->label()}' as parent of '{$node->label()}'."];
  }

  public function testEntityLanguage(NodeInterface $node) {
    $build = ['#markup' => $node->label()];
    \Drupal::service('renderer')->addCacheableDependency($build, $node);
    return $build;
  }
}
