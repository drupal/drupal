<?php

declare(strict_types=1);

namespace Drupal\sdc_other_node_visitor\Twig\Profiler;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Represents a profile leave node.
 */
#[YieldReady]
class LeaveProfileNode extends Node {

  public function __construct(string $varName) {
    parent::__construct([], ['var_name' => $varName]);
  }

  public function compile(Compiler $compiler): void {
    $compiler
      ->write("\n")
      ->write(sprintf("\$%s->leave();\n\n", $this->getAttribute('var_name')));
  }

}
