<?php

declare(strict_types=1);

namespace Drupal\module_test;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Counts the number of times this compiler pass runs.
 */
class ModuleTestCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    if (!isset($GLOBALS['container_rebuilt'])) {
      $GLOBALS['container_rebuilt'] = 1;
    }
    else {
      $GLOBALS['container_rebuilt']++;
    }
  }

}
