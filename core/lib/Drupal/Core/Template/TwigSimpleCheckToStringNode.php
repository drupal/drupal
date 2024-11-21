<?php

declare(strict_types=1);

namespace Drupal\Core\Template;

use Twig\Compiler;
use Twig\Node\CheckToStringNode;

/**
 * Defines a twig node for simplifying CheckToStringNode.
 *
 * Drupal's sandbox policy is very permissive with checking whether an object
 * can be converted to a string. We allow any object with a __toString method.
 * This means that the array traversal in the default SandboxExtension
 * implementation added by the parent class is a performance overhead we don't
 * need.
 *
 * @see \Drupal\Core\Template\TwigSandboxPolicy
 * @see \Drupal\Core\Template\RemoveCheckToStringNodeVisitor
 */
final class TwigSimpleCheckToStringNode extends CheckToStringNode {

  /**
   * {@inheritdoc}
   */
  public function compile(Compiler $compiler): void {
    $expr = $this->getNode('expr');
    $compiler
      ->subcompile($expr);
  }

}
