<?php

/**
 * @file
 * Contains \Drupal\Core\Template\TwigNodeVisitor.
 */

namespace Drupal\Core\Template;

/**
 * Provides a Twig_NodeVisitor to change the generated parse-tree.
 *
 * This is used to ensure that everything printed is wrapped via the
 * twig_render_var() function in order to just write {{ content }}
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
   * {@inheritdoc}
   */
  function leaveNode(\Twig_NodeInterface $node, \Twig_Environment $env) {
    // We use this to inject a call to render_var -> twig_render_var()
    // before anything is printed.
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
   * {@inheritdoc}
   */
  function getPriority() {
    return 1;
  }

}
