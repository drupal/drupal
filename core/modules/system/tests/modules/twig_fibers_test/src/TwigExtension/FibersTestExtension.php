<?php

declare(strict_types=1);

namespace Drupal\twig_fibers_test\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Custom Twig extension that suspends a fiber.
 */
class FibersTestExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('fibers_test_function', [$this, 'fibersTestFunction']),
    ];
  }

  /**
   * Custom Twig function that calls Fiber::suspend().
   *
   * @param string $message
   *   The message to return.
   *
   * @return string
   *   The processed message.
   */
  public function fibersTestFunction(string $message): string {
    \Fiber::suspend();
    return 'Fibers test: ' . $message;
  }

}
