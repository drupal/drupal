<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Stores hook implementations in keyvalue and clears cache.
 *
 * This is done in a separate, late compiler pass to ensure that a possibly
 * altered keyvalue service is respected.
 *
 * @internal
 *
 * @see \Drupal\Core\Hook\HookCollectorPass::writeToContainer
 */
class HookCollectorKeyValueWritePass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $hookData = $container->getParameter('.hook_data');
    $keyvalue = $container->get('keyvalue')->get('hook_data');
    assert($keyvalue instanceof KeyValueStoreInterface);
    $keyvalue->setMultiple($hookData);
    $container->get('cache.bootstrap')->delete('hook_data');
  }

}
