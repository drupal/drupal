<?php

namespace Drupal\Core\Template;

use Twig\Environment;
use Twig\Node\Expression\AssignNameExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Provides a Node Visitor to trigger errors if deprecated variables are used.
 *
 * Every use of a named variable is tracked, and the used variable names are
 * passed to TwigExtension::checkDeprecations at runtime for comparison against
 * those in the 'deprecated' array in the template context.
 *
 * @see \Drupal\Core\Template\TwigNodeCheckDeprecations
 */
class TwigNodeVisitorCheckDeprecations implements NodeVisitorInterface {

  /**
   * The named variables used in the template from the context.
   */
  protected array $usedNames = [];

  /**
   * The named variables set within the template.
   */
  protected array $assignedNames = [];

  /**
   * {@inheritdoc}
   */
  public function enterNode(Node $node, Environment $env): Node {
    if ($node instanceof ModuleNode) {
      $this->usedNames = [];
      $this->assignedNames = [];
    }
    elseif ($node instanceof AssignNameExpression) {
      // Setting a variable makes subsequent usage is safe.
      $this->assignedNames[$node->getAttribute('name')] = $node->getAttribute('name');
    }
    elseif ($node instanceof NameExpression) {
      // Track each usage of a variable, unless set within the template.
      $name = $node->getAttribute('name');
      if (!in_array($name, $this->assignedNames)) {
        $this->usedNames[$name] = $name;
      }
    }
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function leaveNode(Node $node, Environment $env): ?Node {
    // At the end of the template, check the used variables are not deprecated.
    if ($node instanceof ModuleNode) {
      if (!empty($this->usedNames)) {
        $checkNode = new Node([new TwigNodeCheckDeprecations($this->usedNames), $node->getNode('display_end')]);
        $node->setNode('display_end', $checkNode);
      }
    }
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    // Just above the Optimizer, which is the normal last one.
    return 256;
  }

}
