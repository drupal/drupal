<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\HookCollectorPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Adds hooks from kernel test to event dispatcher and module handler.
 */
class KernelTestCompilerPass implements CompilerPassInterface {

  /**
   * Constructs a KernelTestCompilerPass object.
   *
   * @param \Symfony\Component\DependencyInjection\Definition $definition
   *   The kernel test service definition that will be used to register hooks.
   */
  public function __construct(private Definition $definition) {}

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $map = $container->getParameter('.hook_data');
    // Check for #[Hook] on methods.
    $reflection_class = new \ReflectionClass($this->definition->getClass());

    foreach ($reflection_class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method_reflection) {
      foreach ($method_reflection->getAttributes(Hook::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute_reflection) {
        $hook = $attribute_reflection->newInstance();
        assert($hook instanceof Hook);
        HookCollectorPass::checkForProceduralOnlyHooks($hook, static::class);
        $map['hook_list'][$hook->hook][$this->definition->getClass() . ':' . $method_reflection->getName()] = 'core';
      }
    }
    $container->setParameter('.hook_data', $map);
  }

}
