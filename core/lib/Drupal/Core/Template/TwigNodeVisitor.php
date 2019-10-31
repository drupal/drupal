<?php

namespace Drupal\Core\Template;

use Twig\Environment;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\NodeVisitor\AbstractNodeVisitor;

/**
 * Provides a TwigNodeVisitor to change the generated parse-tree.
 *
 * This is used to ensure that everything printed is wrapped via the
 * TwigExtension->renderVar() function in order to just write {{ content }}
 * in templates instead of having to write {{ render_var(content) }}.
 *
 * @see twig_render
 */
class TwigNodeVisitor extends AbstractNodeVisitor {

  /**
   * {@inheritdoc}
   */
  protected function doEnterNode(Node $node, Environment $env) {
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLeaveNode(Node $node, Environment $env) {
    // We use this to inject a call to render_var -> TwigExtension->renderVar()
    // before anything is printed.
    if ($node instanceof PrintNode) {
      if (!empty($this->skipRenderVarFunction)) {
        // No need to add the callback, we have escape active already.
        unset($this->skipRenderVarFunction);
        return $node;
      }
      $class = get_class($node);
      $line = $node->getTemplateLine();
      return new $class(
        new FunctionExpression('render_var', new Node([$node->getNode('expr')]), $line),
        $line
      );
    }
    // Change the 'escape' filter to our own 'drupal_escape' filter.
    elseif ($node instanceof FilterExpression) {
      $name = $node->getNode('filter')->getAttribute('value');
      if ('escape' == $name || 'e' == $name) {
        // Use our own escape filter that is MarkupInterface aware.
        $node->getNode('filter')->setAttribute('value', 'drupal_escape');

        // Store that we have a filter active already that knows
        // how to deal with render arrays.
        $this->skipRenderVarFunction = TRUE;
      }
    }

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority() {
    // Just above the Optimizer, which is the normal last one.
    return 256;
  }

}
