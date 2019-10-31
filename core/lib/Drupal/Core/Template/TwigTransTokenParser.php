<?php

namespace Drupal\Core\Template;

use Twig\Error\SyntaxError;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Node\TextNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * A class that defines the Twig 'trans' token parser for Drupal.
 *
 * The token parser converts a token stream created from template source
 * code into an Abstract Syntax Tree (AST).  The AST will later be compiled
 * into PHP code usable for runtime execution of the template.
 *
 * @see https://twig-extensions.readthedocs.io/en/latest/i18n.html
 * @see https://github.com/fabpot/Twig-extensions
 */
class TwigTransTokenParser extends AbstractTokenParser {

  /**
   * {@inheritdoc}
   */
  public function parse(Token $token) {
    $lineno = $token->getLine();
    $stream = $this->parser->getStream();
    $body = NULL;
    $options = NULL;
    $count = NULL;
    $plural = NULL;

    if (!$stream->test(Token::BLOCK_END_TYPE) && $stream->test(Token::STRING_TYPE)) {
      $body = $this->parser->getExpressionParser()->parseExpression();
    }
    if (!$stream->test(Token::BLOCK_END_TYPE) && $stream->test(Token::NAME_TYPE, 'with')) {
      $stream->next();
      $options = $this->parser->getExpressionParser()->parseExpression();
    }
    if (!$body) {
      $stream->expect(Token::BLOCK_END_TYPE);
      $body = $this->parser->subparse([$this, 'decideForFork']);
      if ('plural' === $stream->next()->getValue()) {
        $count = $this->parser->getExpressionParser()->parseExpression();
        $stream->expect(Token::BLOCK_END_TYPE);
        $plural = $this->parser->subparse([$this, 'decideForEnd'], TRUE);
      }
    }

    $stream->expect(Token::BLOCK_END_TYPE);

    $this->checkTransString($body, $lineno);

    $node = new TwigNodeTrans($body, $plural, $count, $options, $lineno, $this->getTag());

    return $node;
  }

  /**
   * Detect a 'plural' switch or the end of a 'trans' tag.
   */
  public function decideForFork($token) {
    return $token->test(['plural', 'endtrans']);
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
   * @param \Twig\Node\Node $body
   *   The expression to check.
   * @param int $lineno
   *   The source line.
   *
   * @throws \Twig\Error\SyntaxError
   */
  protected function checkTransString(Node $body, $lineno) {
    foreach ($body as $node) {
      if (
        $node instanceof TextNode
        ||
        ($node instanceof PrintNode && $node->getNode('expr') instanceof NameExpression)
        ||
        ($node instanceof PrintNode && $node->getNode('expr') instanceof GetAttrExpression)
        ||
        ($node instanceof PrintNode && $node->getNode('expr') instanceof FilterExpression)
      ) {
        continue;
      }
      throw new SyntaxError(sprintf('The text to be translated with "trans" can only contain references to simple variables'), $lineno);
    }
  }

}
