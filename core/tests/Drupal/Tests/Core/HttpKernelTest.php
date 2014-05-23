<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\HttpKernelTest.
 */

namespace Drupal\Tests\Core;

use Drupal\Core\Controller\ControllerResolver;
use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\HttpKernel;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tests the custom http kernel of drupal.
 *
 * @see \Drupal\Core\HttpKernel
 */
class HttpKernelTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'HttpKernel (Unit)',
      'description' => 'Tests the HttpKernel.',
      'group' => 'Routing',
    );
  }

  /**
   * Tests the forward method.
   *
   * @see \Drupal\Core\HttpKernel::setupSubrequest()
   */
  public function testSetupSubrequest() {
    $container = new ContainerBuilder();

    $request = new Request();
    $container->addScope(new Scope('request'));
    $container->enterScope('request');
    $container->set('request', $request, 'request');

    $dispatcher = new EventDispatcher();
    $class_resolver = new ClassResolver();
    $class_resolver->setContainer($container);
    $controller_resolver = new ControllerResolver($class_resolver);

    $http_kernel = new HttpKernel($dispatcher, $controller_resolver);
    $http_kernel->setContainer($container);

    $test_controller = '\Drupal\Tests\Core\Controller\TestController';
    $random_attribute = $this->randomName();
    $subrequest = $http_kernel->setupSubrequest($test_controller, array('custom_attribute' => $random_attribute), array('custom_query' => $random_attribute));
    $this->assertNotSame($subrequest, $request, 'The subrequest is not the same as the main request.');
    $this->assertEquals($subrequest->attributes->get('custom_attribute'), $random_attribute, 'Attributes are set from the subrequest.');
    $this->assertEquals($subrequest->query->get('custom_query'), $random_attribute, 'Query attributes are set from the subrequest.');
    $this->assertEquals($subrequest->attributes->get('_controller'), $test_controller, 'Controller attribute got set.');

    $subrequest = $http_kernel->setupSubrequest(NULL, array(), array());
    $this->assertFalse($subrequest->attributes->has('_controller'), 'Ensure that _controller is not copied when no controller was set before.');
  }

}
