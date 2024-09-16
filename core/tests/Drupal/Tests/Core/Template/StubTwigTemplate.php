<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Template;

use Twig\Source;
use Twig\Template;

/**
 * A stub of the Twig Template class for testing.
 */
class StubTwigTemplate extends Template {

  /**
   * {@inheritdoc}
   */
  public function getTemplateName(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDebugInfo(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceContext(): Source {
    throw new \LogicException(__METHOD__ . '() not implemented.');
  }

  /**
   * {@inheritdoc}
   */
  protected function doDisplay(array $context, array $blocks = []): iterable {
    throw new \LogicException(__METHOD__ . '() not implemented.');
  }

}
