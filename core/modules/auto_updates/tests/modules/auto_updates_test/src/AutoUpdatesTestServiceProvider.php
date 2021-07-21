<?php

namespace Drupal\auto_updates_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Service provider for the auto_updates_test module.
 */
class AutoUpdatesTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    // We cannot use the state service here because the container is still being
    // built. Therefore, our tests define a constant to trigger service
    // definition to be altered.
    // @see \Drupal\Tests\auto_updates\Kernel\ReadinessChecker\ReadinessCheckerManagerTest::testGetResults()
    // @see \Drupal\Tests\auto_updates\Kernel\ReadinessChecker\ReadinessCheckerManagerTest::testRunIfNeeded()
    if (defined('AUTO_UPDATES_TEST_SET_PRIORITY')) {
      $definition = $container->getDefinition('auto_updates_test.checker');
      $tags = $definition->getTags();
      $tags['auto_updates.readiness_checker'] = [['priority' => AUTO_UPDATES_TEST_SET_PRIORITY]];
      $definition->setTags($tags);
      $container->setDefinition('auto_updates_test.checker', $definition);
    }
  }

}
