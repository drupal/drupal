<?php

declare(strict_types=1);

namespace Drupal\Core\Template;

use Twig\Environment;
use Twig\Node\CheckToStringNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Defines a TwigNodeVisitor that replaces CheckToStringNodes.
 *
 * Twig 3.14.1 resulted in a performance regression in Drupal due to checking if
 * __toString is an allowed method on objects. __toString is allowed on all
 * objects when Drupal's default SandboxPolicy is active. Therefore, Twig's
 * SandboxExtension checks are unnecessary.
 */
final class RemoveCheckToStringNodeVisitor implements NodeVisitorInterface {

  /**
   * {@inheritdoc}
   */
  public function enterNode(Node $node, Environment $env): Node {
    if ($node instanceof CheckToStringNode) {
      // Replace CheckToStringNode with the faster equivalent, __toString is an
      // allowed method so any checking of __toString on a per-object basis is
      // performance overhead.
      $new = new TwigSimpleCheckToStringNode($node->getNode('expr'));
      // @todo https://www.drupal.org/project/drupal/issues/3488584 Update for
      //   Twig 4 as the spread attribute has been removed there.
      if ($node->hasAttribute('spread')) {
        $new->setAttribute('spread', $node->getAttribute('spread'));
      }
      return $new;
    }
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function leaveNode(Node $node, Environment $env): ?Node {
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority() {
    // Runs after sandbox visitor.
    return 1;
  }

}
