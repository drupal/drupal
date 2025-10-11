<?php

declare(strict_types=1);

namespace Drupal\hook_loader_test\Hook;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Example to test that a hook service can depend on ModuleHandler.
 *
 * This would cause a circular dependency problem, if the hook implementations
 * in ModuleHandler were not lazy-loaded.
 */
class CircularDependencyHooks {

  public function __construct(
    public readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  #[Hook('test_hook')]
  public function testHook(): string {
    // The hook method does not need to actually use the module handler.
    // It is enough to require it in the constructor.
    return __METHOD__;
  }

}
