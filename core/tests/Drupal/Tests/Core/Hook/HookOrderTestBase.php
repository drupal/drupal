<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Hook;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

abstract class HookOrderTestBase extends UnitTestCase {

  /**
   * The container builder.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected ContainerBuilder $container;

  /**
   * Set up three service listeners, "a", "b" and "c".
   *
   * The service id, the class name and the method name are all the same.
   *
   * @param bool $different_priority
   *   When TRUE, "c" will fire first, "b" second and "a" last. When FALSE,
   *   the priority will be set to be the same and the order is undefined.
   *
   * @return void
   */
  protected function setUpContainer(bool $different_priority): void {
    $this->container = new ContainerBuilder();
    foreach (['a', 'b', 'c'] as $key => $name) {
      $definition = $this->container
        ->register($name, $name)
        ->setAutowired(TRUE);
      $definition->addTag('kernel.event_listener', [
        'event' => 'drupal_hook.test',
        'method' => $name,
        // Do not use $key itself to avoid a 0 priority which could potentially
        // lead to misleading results.
        'priority' => $different_priority ? $key + 3 : 0,
      ]);
    }
  }

  /**
   * Get the priority for a service.
   */
  protected function getPriority(string $name): int {
    $definition = $this->container->getDefinition($name);
    return $definition->getTags()['kernel.event_listener'][0]['priority'];
  }

}
