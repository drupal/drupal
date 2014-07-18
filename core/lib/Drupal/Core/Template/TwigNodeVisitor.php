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
      if (!empty($this->skipRenderVarFunction)) {
        // No need to add the callback, we have escape active already.
        unset($this->skipRenderVarFunction);
        return $node;
      }
      $class = get_class($node);
      $line = $node->getLine();
      return new $class(
        new \Twig_Node_Expression_Function('render_var', new \Twig_Node(array($node->getNode('expr'))), $line),
        $line
      );
    }
    // Change the 'escape' filter to our own 'drupal_escape' filter.
    else if ($node instanceof \Twig_Node_Expression_Filter) {
      $name = $node->getNode('filter')->getAttribute('value');
      if ('escape' == $name || 'e' == $name) {
        // Use our own escape filter that is SafeMarkup aware.
        $node->getNode('filter')->setAttribute('value', 'drupal_escape');

        // Store that we have a filter active already that knows how to deal with render arrays.
        $this->skipRenderVarFunction = TRUE;
      }
    }

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  function getPriority() {
    // Just above the Optimizer, which is the normal last one.
    return 256;
  }

}
