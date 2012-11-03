<?php

/**
 * @file
 * Definition of Drupal\Core\Template\TwigFunctionTokenParser.
 */

namespace Drupal\Core\Template;

/**
 * A class that defines the Twig token parser for Drupal.
 *
 * The token parser converts a token stream created from template source
 * code into an Abstract Syntax Tree (AST).  The AST will later be compiled
 * into PHP code usable for runtime execution of the template.
 *
 * @see core\vendor\twig\twig\lib\Twig\TokenParser.php
 */
class TwigFunctionTokenParser extends \Twig_TokenParser {

  /**
   * The name of tag. Can be 'hide' or 'show'.
   *
   * @var string
   */
  protected $tag;

  /**
   * Constructor for TwigFunctionTokenParser.
   *
   * Locally scope variables.
   */
  public function __construct($tag = 'hide') {
    $this->tag = $tag;
  }

  /**
   * Parses a token and returns a node.
   *
   * @param Twig_Token $token A Twig_Token instance.
   *
   * @return Twig_Node_Print A Twig_Node_Print instance.
   */
  public function parse(\Twig_Token $token) {
    $lineno = $token->getLine();

    $expr = $this->parser->getExpressionParser()->parseExpression();
    $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
    return new \Twig_Node_Print(new \Twig_Node_Expression_Function($this->tag, new \Twig_Node(array($expr)), $lineno), $lineno);
  }

  /**
   * Gets the tag name associated with this token parser.
   *
   * @return string The tag name
   */
  public function getTag() {
    return $this->tag;
  }
}
