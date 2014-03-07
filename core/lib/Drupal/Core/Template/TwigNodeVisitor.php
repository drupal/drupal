<?php

/**
 * @file
 * Definition of Drupal\Core\Template\TwigNodeVisitor.
 */

namespace Drupal\Core\Template;

/**
 * Provides a Twig_NodeVisitor to change the generated parse-tree.
 *
 * This is used to ensure that everything that is printed is wrapped via
 * twig_render_var() function so that we can write for example just {{ content }}
 * in templates instead of having to write {{ render_var(content) }}.
 *
 * @see twig_render
 */
class TwigNodeVisitor implements \Twig_NodeVisitorInterface {

  /**
   * {@inheritdoc}
   */
  function enterNode(\Twig_NodeInterface $node, \Twig_Environment $env) {
    return $node;
  }

  /**
   * Implements Twig_NodeVisitorInterface::leaveNode().
   *
   * We use this to inject a call to render_var -> twig_render_var()
   * before anything is printed.
   *
   * @see twig_render
   */
  function leaveNode(\Twig_NodeInterface $node, \Twig_Environment $env) {
    if ($node instanceof \Twig_Node_Print) {
      $class = get_class($node);
      $line = $node->getLine();
      return new $class(
        new \Twig_Node_Expression_Function('render_var', new \Twig_Node(array($node->getNode('expr'))), $line),
        $line
      );
    }

    return $node;
  }

  /**
   * Implements Twig_NodeVisitorInterface::getPriority().
   */
  function getPriority() {
    // We want to run before other NodeVisitors like Escape or Optimizer
    return -1;
  }
}
