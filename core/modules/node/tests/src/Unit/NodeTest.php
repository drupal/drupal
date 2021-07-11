<?php

namespace Drupal\Tests\node\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeListBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @group node
 */
class NodeTest extends UnitTestCase {

  /**
   * @group legacy
   * @expectedDeprecation node_mark() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. There's no replacement for this function. See https://www.drupal.org/node/3037203.
   * @see node_mark()
   */
  public function testNodeMarkDeprecation() {
    $container = new ContainerBuilder();
    $current_user = $this->prophesize(AccountProxyInterface::class);
    $container->set('current_user', $current_user->reveal());
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $container->set('module_handler', $module_handler->reveal());
    \Drupal::setContainer($container);

    require_once $this->root . '/core/modules/node/node.module';
    node_mark(123, time());
  }

}

if (!defined('MARK_READ')) {
  define('MARK_READ', 0);
}