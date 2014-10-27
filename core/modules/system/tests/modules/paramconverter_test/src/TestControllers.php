<?php

/**
 * @file
 * Contains Drupal\paramconverter_test\TestControllers.
 */

namespace Drupal\paramconverter_test;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routine for testing the paramconverter.
 */
class TestControllers {

  public function testUserNodeFoo(EntityInterface $user, NodeInterface $node, Request $request) {
    $foo = $request->attributes->get('foo');
    $foo = is_object($foo) ? $foo->label() : $foo;
    return ['#markup' => "user: {$user->label()}, node: {$node->label()}, foo: $foo"];
  }

  public function testNodeSetParent(NodeInterface $node, NodeInterface $parent) {
    return ['#markup' => "Setting '{$parent->label()}' as parent of '{$node->label()}'."];
  }

  public function testEntityLanguage(NodeInterface $node) {
    return ['#markup' => $node->label()];
  }
}
