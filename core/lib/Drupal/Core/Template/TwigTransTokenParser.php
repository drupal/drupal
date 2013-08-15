<?php

/**
 * @file
 * Contains \Drupal\Core\Template\TwigTransTokenParser.
 *
 * @see http://twig.sensiolabs.org/doc/extensions/i18n.html
 * @see https://github.com/fabpot/Twig-extensions
 */

namespace Drupal\Core\Template;

/**
 * A class that defines the Twig 'trans' token parser for Drupal.
 *
 * The token parser converts a token stream created from template source
 * code into an Abstract Syntax Tree (AST).  The AST will later be compiled
 * into PHP code usable for runtime execution of the template.
 *
 * @see \Twig_TokenParser
 */
class TwigTransTokenParser extends \Twig_TokenParser {

  /**
   * {@inheritdoc}
   */
  public function parse(\Twig_Token $token) {
    $lineno = $token->getLine();
    $stream = $this->parser->getStream();
    $body = NULL;
    $options = NULL;
    $count = NULL;
    $plural = NULL;

    if (!$stream->test(\Twig_Token::BLOCK_END_TYPE) && $stream->test(\Twig_Token::STRING_TYPE)) {
      $body = $this->parser->getExpressionParser()->parseExpression();
    }
    if (!$stream->test(\Twig_Token::BLOCK_END_TYPE) && $stream->test(\Twig_Token::NAME_TYPE, 'with')) {
      $stream->next();
      $options = $this->parser->getExpressionParser()->parseExpression();
    }
    if (!$body) {
      $stream->expect(\Twig_Token::BLOCK_END_TYPE);
      $body = $this->parser->subparse(array($this, 'decideForFork'));
      if ('plural' === $stream->next()->getValue()) {
        $count = $this->parser->getExpressionParser()->parseExpression();
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        $plural = $this->parser->subparse(array($this, 'decideForEnd'), TRUE);
      }
    }

    $stream->expect(\Twig_Token::BLOCK_END_TYPE);

    $this->checkTransString($body, $lineno);

    $node = new TwigNodeTrans($body, $plural, $count, $options, $lineno, $this->getTag());

    return $node;
  }

  /**
   * Detect a 'plural' switch or the end of a 'trans' tag.
   */
  public function decideForFork($token) {
    return $token->test(array('plural', 'endtrans'));
  }

  /**
   * Detect the end of a 'trans' tag.
   */
  public function decideForEnd($token) {
    return $token->test('endtrans');
  }

  /**
   * {@inheritdoc}
   */
  public function getTag() {
    return 'trans';
  }

  /**
   * Ensure that any nodes that are parsed are only of allowed types.
   *
   * @param \Twig_NodeInterface $body
   *   The expression to check.
   * @param integer $lineno
   *   The source line.
   *
   * @throws \Twig_Error_Syntax
   */
  protected function checkTransString(\Twig_NodeInterface $body, $lineno) {
    foreach ($body as $node) {
      if (
        $node instanceof \Twig_Node_Text
        ||
        ($node instanceof \Twig_Node_Print && $node->getNode('expr') instanceof \Twig_Node_Expression_Name)
        ||
        ($node instanceof \Twig_Node_Print && $node->getNode('expr') instanceof \Twig_Node_Expression_GetAttr)
        ||
        ($node instanceof \Twig_Node_Print && $node->getNode('expr') instanceof \Twig_Node_Expression_Filter)
      ) {
        continue;
      }
      throw new \Twig_Error_Syntax(sprintf('The text to be translated with "trans" can only contain references to simple variables'), $lineno);
    }
  }

}
