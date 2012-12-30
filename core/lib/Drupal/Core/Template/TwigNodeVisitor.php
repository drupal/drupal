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
   * TRUE when this node is a function getting arguments by reference.
   *
   * For example: 'hide' or 'render' are such functions.
   *
   * @var bool
   */
  protected $isReference = FALSE;

  /**
   * Implements Twig_NodeVisitorInterface::enterNode().
   */
  function enterNode(\Twig_NodeInterface $node, \Twig_Environment $env) {
   if ($node instanceof \Twig_Node_Expression_Function) {
      $name = $node->getAttribute('name');
      $func = $env->getFunction($name);

      // Optimization: Do not support nested functions.
      if ($this->isReference && $func instanceof \Twig_Function_Function) {
        $this->isReference = FALSE;
      }
      if ($func instanceof TwigReferenceFunction) {
        // We need to create a TwigReference
        $this->isReference = TRUE;
      }
    }
    if ($node instanceof \Twig_Node_Print) {
       // Our injected render_var needs arguments passed by reference -- in case of render array
      $this->isReference = TRUE;
    }

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
      $this->isReference = FALSE;

      $class = get_class($node);
      return new $class(
        new \Twig_Node_Expression_Function('render_var', new \Twig_Node(array($node->getNode('expr'))), $node->getLine()),
        $node->getLine()
      );
    }

    if ($this->isReference) {
      if ($node instanceof \Twig_Node_Expression_Name) {
        $name = $node->getAttribute('name');
        return new TwigNodeExpressionNameReference($name, $node->getLine());
      }
      elseif ($node instanceof \Twig_Function_Function) {
        // Do something!
        $this->isReference = FALSE;
      }
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
