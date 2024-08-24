<?php

declare(strict_types=1);

namespace Drupal\sdc_other_node_visitor\Twig\Profiler;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Represents a profile enter node.
 */
#[YieldReady]
class EnterProfileNode extends Node {

  public function __construct(string $extensionName, string $varName) {
    parent::__construct([], [
      'extension_name' => $extensionName,
      'var_name' => $varName,
    ]);
  }

  public function compile(Compiler $compiler): void {
    $compiler
      ->write(sprintf('$%s = $this->extensions[', $this->getAttribute('var_name')))
      /* cspell:disable-next-line */
      ->repr($this->getAttribute('extension_name'))
      ->raw("];\n")
      ->write(sprintf('$%s->enter();', $this->getAttribute('var_name')))
      ->raw("\n\n");
  }

}
