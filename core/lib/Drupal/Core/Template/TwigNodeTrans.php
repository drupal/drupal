<?php

namespace Drupal\Core\Template;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Node\CheckToStringNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Expression\TempNameExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\PrintNode;

/**
 * A class that defines the Twig 'trans' tag for Drupal.
 *
 * This Twig extension was originally based on Twig i18n extension. It has been
 * severely modified to work properly with the complexities of the Drupal
 * translation system.
 *
 * @see https://twig-extensions.readthedocs.io/en/latest/i18n.html
 * @see https://github.com/fabpot/Twig-extensions
 */
#[YieldReady]
class TwigNodeTrans extends Node {

  /**
   * {@inheritdoc}
   */
  public function __construct(Node $body, ?Node $plural = NULL, ?AbstractExpression $count = NULL, ?AbstractExpression $options = NULL, $lineno = 0) {
    $nodes['body'] = $body;
    if ($count !== NULL) {
      $nodes['count'] = $count;
    }
    if ($plural !== NULL) {
      $nodes['plural'] = $plural;
    }
    if ($options !== NULL) {
      $nodes['options'] = $options;
    }
    parent::__construct($nodes, [], $lineno);
  }

  /**
   * {@inheritdoc}
   */
  public function compile(Compiler $compiler) {
    $compiler->addDebugInfo($this);

    [$singular, $tokens] = $this->compileString($this->getNode('body'));
    $plural = NULL;

    if ($this->hasNode('plural')) {
      [$plural, $pluralTokens] = $this->compileString($this->getNode('plural'));
      $tokens = array_merge($tokens, $pluralTokens);
    }

    // Start writing with the function to be called.
    $compiler->write('yield ' . (empty($plural) ? 't' : '\Drupal::translation()->formatPlural') . '(');

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
    $compiler->raw(', [');
    foreach ($tokens as $token) {
      $compiler->string($token->getAttribute('placeholder'))->raw(' => ')->subcompile($token)->raw(', ');
    }
    $compiler->raw(']');

    // Write any options passed.
    if ($this->hasNode('options')) {
      $compiler->raw(', ')->subcompile($this->getNode('options'));
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
   * @param \Twig\Node\Node $body
   *   The node to compile.
   *
   * @return array
   *   Returns an array containing the two following parameters:
   *   - string $text
   *       The extracted text.
   *   - array $tokens
   *       The extracted tokens as new \Twig\Node\Expression\TempNameExpression
   *       instances.
   */
  protected function compileString(Node $body) {
    if ($body instanceof NameExpression || $body instanceof ConstantExpression || $body instanceof TempNameExpression) {
      return [$body, []];
    }

    $tokens = [];
    if (count($body)) {
      $text = '';

      foreach ($body as $node) {
        if ($node instanceof PrintNode) {
          $n = $node->getNode('expr');
          while ($n instanceof FilterExpression) {
            $n = $n->getNode('node');
          }

          if ($n instanceof CheckToStringNode) {
            $n = $n->getNode('expr');
          }
          $args = $n;

          // Support TwigExtension->renderVar() function in chain.
          if ($args instanceof FunctionExpression) {
            $args = $n->getNode('arguments')->getNode(0);
          }

          // Detect if a token implements one of the filters reserved for
          // modifying the prefix of a token. The default prefix used for
          // translations is "@". This escapes the printed token and makes them
          // safe for templates.
          // @see TwigExtension::getFilters()
          $argPrefix = '@';
          while ($args instanceof FilterExpression) {
            switch ($args->getAttribute('twig_callable')->getName()) {
              case 'placeholder':
                $argPrefix = '%';
                break;
            }
            $args = $args->getNode('node');
          }
          if ($args instanceof CheckToStringNode) {
            $args = $args->getNode('expr');
          }
          if ($args instanceof GetAttrExpression) {
            $argName = [];
            // Reuse the incoming expression.
            $expr = $args;
            // Assemble a valid argument name by walking through the expression.
            $argName[] = $args->getNode('attribute')->getAttribute('value');
            while ($args->hasNode('node')) {
              $args = $args->getNode('node');
              if ($args instanceof NameExpression) {
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
            $expr = new ContextVariable($argName, $n->getTemplateLine());
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
      throw new SyntaxError('{% trans %} tag cannot be empty');
    }
    else {
      $text = $body->getAttribute('data');
    }

    return [
      new Nodes([new ConstantExpression(trim($text), $body->getTemplateLine())]),
      $tokens,
    ];
  }

}
