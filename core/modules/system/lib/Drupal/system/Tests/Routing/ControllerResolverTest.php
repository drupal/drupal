<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Routing\ControllerResolverTest.
 */

namespace Drupal\system\Tests\Routing;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Core\Controller\ControllerResolver;
use Drupal\simpletest\UnitTestBase;

/**
 * Tests that the Drupal-extended ControllerResolver is functioning properly.
 */
class ControllerResolverTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Controller Resolver tests',
      'description' => 'Tests that the Drupal-extended ControllerResolver is functioning properly.',
      'group' => 'Routing',
    );
  }

  /**
   * Confirms that a container aware controller gets returned.
   */
  function testContainerAware() {
    $container = new Container();
    $resolver = new ControllerResolver($container);

    $request = Request::create('/some/path');
    $request->attributes->set('_controller', '\Drupal\system\Tests\Routing\MockController::run');

    $controller = $resolver->getController($request);

    $this->assertTrue($controller[0] instanceof MockController, 'The correct controller object was returned.');
  }
}
