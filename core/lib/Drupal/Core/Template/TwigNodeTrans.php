<?php

namespace Drupal\Core\Template;

/**
 * A class that defines the Twig 'trans' tag for Drupal.
 *
 * This Twig extension was originally based on Twig i18n extension. It has been
 * severely modified to work properly with the complexities of the Drupal
 * translation system.
 *
 * @see http://twig.sensiolabs.org/doc/extensions/i18n.html
 * @see https://github.com/fabpot/Twig-extensions
 */
class TwigNodeTrans extends \Twig_Node {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Twig_Node $body, \Twig_Node $plural = NULL, \Twig_Node_Expression $count = NULL, \Twig_Node_Expression $options = NULL, $lineno, $tag = NULL) {
    parent::__construct(array(
      'count' => $count,
      'body' => $body,
      'plural' => $plural,
      'options' => $options,
    ), array(), $lineno, $tag);
  }

  /**
   * {@inheritdoc}
   */
  public function compile(\Twig_Compiler $compiler) {
    $compiler->addDebugInfo($this);

    $options = $this->getNode('options');

    list($singular, $tokens) = $this->compileString($this->getNode('body'));
    $plural = NULL;

    if (NULL !== $this->getNode('plural')) {
      list($plural, $pluralTokens) = $this->compileString($this->getNode('plural'));
      $tokens = array_merge($tokens, $pluralTokens);
    }

    // Start writing with the function to be called.
    $compiler->write('echo ' . (empty($plural) ? 't' : '\Drupal::translation()->formatPlural') . '(');

    // Move the count to the beginning of the parameters list.
    if (!empty($plural)) {
      $compiler->raw('abs(')->subcompile($this->getNode('count'))->raw('), ');
    }

    // Write the singular text parameter.
    $compiler->subcompile($singular);

    // Write the plural text parameter, if necessary.
    if (!empty($plural)) {
      $compiler->raw(', ')->subcompile($plural);
    }

    // Write any tokens found as an associative array parameter, otherwise just
    // leave as an empty array.
    $compiler->raw(', array(');
    foreach ($tokens as $token) {
      $compiler->string($token->getAttribute('placeholder'))->raw(' => ')->subcompile($token)->raw(', ');
    }
    $compiler->raw(')');

    // Write any options passed.
    if (!empty($options)) {
      $compiler->raw(', ')->subcompile($options);
    }

    // Write function closure.
    $compiler->raw(')');

    // @todo Add debug output, see https://www.drupal.org/node/2512672

    // End writing.
    $compiler->raw(";\n");
  }

  /**
   * Extracts the text and tokens for the "trans" tag.
   *
   * @param \Twig_Node $body
   *   The node to compile.
   *
   * @return array
   *   Returns an array containing the two following parameters:
   *   - string $text
   *       The extracted text.
   *   - array $tokens
   *       The extracted tokens as new \Twig_Node_Expression_Name instances.
   */
  protected function compileString(\Twig_Node $body) {
    if ($body instanceof \Twig_Node_Expression_Name || $body instanceof \Twig_Node_Expression_Constant || $body instanceof \Twig_Node_Expression_TempName) {
      return array($body, array());
    }

    $tokens = array();
    if (count($body)) {
      $text = '';

      foreach ($body as $node) {
        if (get_class($node) === 'Twig_Node' && $node->getNode(0) instanceof \Twig_Node_SetTemp) {
          $node = $node->getNode(1);
        }

        if ($node instanceof \Twig_Node_Print) {
          $n = $node->getNode('expr');
          while ($n instanceof \Twig_Node_Expression_Filter) {
            $n = $n->getNode('node');
          }

          $args = $n;

          // Support TwigExtension->renderVar() function in chain.
          if ($args instanceof \Twig_Node_Expression_Function) {
            $args = $n->getNode('arguments')->getNode(0);
          }

          // Detect if a token implements one of the filters reserved for
          // modifying the prefix of a token. The default prefix used for
          // translations is "@". This escapes the printed token and makes them
          // safe for templates.
          // @see TwigExtension::getFilters()
          $argPrefix = '@';
          while ($args instanceof \Twig_Node_Expression_Filter) {
            switch ($args->getNode('filter')->getAttribute('value')) {
              case 'placeholder':
                $argPrefix = '%';
                break;
            }
            $args = $args->getNode('node');
          }
          if ($args instanceof \Twig_Node_Expression_GetAttr) {
            $argName = array();
            // Reuse the incoming expression.
            $expr = $args;
            // Assemble a valid argument name by walking through the expression.
            $argName[] = $args->getNode('attribute')->getAttribute('value');
            while ($args->hasNode('node')) {
              $args = $args->getNode('node');
              if ($args instanceof \Twig_Node_Expression_Name) {
                $argName[] = $args->getAttribute('name');
              }
              else {
                $argName[] = $args->getNode('attribute')->getAttribute('value');
              }
            }
            $argName = array_reverse($argName);
            $argName = implode('.', $argName);
          }
          else {
            $argName = $n->getAttribute('name');
            if (!is_null($args)) {
              $argName = $args->getAttribute('name');
            }
            $expr = new \Twig_Node_Expression_Name($argName, $n->getLine());
          }
          $placeholder = sprintf('%s%s', $argPrefix, $argName);
          $text .= $placeholder;
          $expr->setAttribute('placeholder', $placeholder);
          $tokens[] = $expr;
        }
        else {
          $text .= $node->getAttribute('data');
        }
      }
    }
    elseif (!$body->hasAttribute('data')) {
      throw new \Twig_Error_Syntax('{% trans %} tag cannot be empty');
    }
    else {
      $text = $body->getAttribute('data');
    }

    return array(new \Twig_Node(array(new \Twig_Node_Expression_Constant(trim($text), $body->getLine()))), $tokens);
  }

}
