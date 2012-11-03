<?php

/**
 * @file
 * Definition of Drupal\Core\Template\TwigNodeExpressionNameReference
 */

namespace Drupal\Core\Template;

/**
 * A class that defines a reference to the name for all nodes that represent
 * expressions.
 *
 * @see core\vendor\twig\twig\lib\Twig\Node\Expression\Name.php
 */
class TwigNodeExpressionNameReference extends \Twig_Node_Expression_Name {

  /**
   * Overrides Twig_Node_Expression_Name::compile().
   */
  public function compile(\Twig_Compiler $compiler) {
    $name = $this->getAttribute('name');
    $compiler
    ->raw('$this->getContextReference($context, ')
    ->string($name)
    ->raw(')');
  }

}
