<?php

declare(strict_types=1);

namespace Drupal\sdc_other_node_visitor\Twig\NodeVisitor;

use Drupal\sdc_other_node_visitor\Twig\Profiler\EnterProfileNode;
use Drupal\sdc_other_node_visitor\Twig\Profiler\LeaveProfileNode;
use Twig\Environment;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * A node visitor that adds nodes to the Twig template.
 *
 * Most of this code is copied from
 * Twig\Profiler\NodeVisitor\ProfilerNodeVisitor.
 */
final class TestNodeVisitor implements NodeVisitorInterface {

  /**
   * The name of the extension.
   */
  private string $extensionName;

  /**
   * The variable name.
   */
  private string $varName;

  /**
   * TestNodeVisitor constructor.
   *
   * @param string $extensionName
   *   The name of the extension.
   */
  public function __construct(string $extensionName) {
    $this->extensionName = $extensionName;
    $this->varName = sprintf('__internal_%s', hash(\PHP_VERSION_ID < 80100 ? 'sha256' : 'xxh128', $extensionName));
  }

  /**
   * {@inheritdoc}
   */
  public function enterNode(Node $node, Environment $env): Node {
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function leaveNode(Node $node, Environment $env): ?Node {
    if ($node instanceof ModuleNode) {
      $node->setNode('display_start', new Nodes([
        new EnterProfileNode($this->extensionName, $this->varName),
        $node->getNode('display_start'),
      ]));
      $node->setNode('display_end', new Nodes([
        new LeaveProfileNode($this->varName),
        $node->getNode('display_end'),
      ]));
    }

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    return 0;
  }

}
