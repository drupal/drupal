<?php

declare(strict_types=1);

namespace Drupal\sdc_other_node_visitor\Twig\Extension;

use Drupal\sdc_other_node_visitor\Twig\NodeVisitor\TestNodeVisitor;
use Twig\Extension\AbstractExtension;

/**
 * Twig extension to add a test node visitor.
 */
class TestProfilerExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors(): array {
    return [new TestNodeVisitor(static::class)];
  }

  /**
   * Dummy function called when a Twig template is entered.
   */
  public function enter() {
    // NOOP.
  }

  /**
   * Dummy function called when a Twig template is left.
   */
  public function leave() {
    // NOOP.
  }

}
