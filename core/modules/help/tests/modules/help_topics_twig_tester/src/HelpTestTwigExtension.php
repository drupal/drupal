<?php

declare(strict_types=1);

namespace Drupal\help_topics_twig_tester;

use Twig\Extension\AbstractExtension;

/**
 * Defines and registers Drupal Twig extensions for testing help topics.
 */
class HelpTestTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors(): array {
    return [
      new HelpTestTwigNodeVisitor(),
    ];
  }

}
