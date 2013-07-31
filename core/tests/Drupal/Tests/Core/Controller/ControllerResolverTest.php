<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Controller\ControllerResolverTest.
 */

namespace Drupal\Tests\Core\Controller;

use Drupal\Core\Controller\ControllerResolver;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Guzzle\Http\Message\Request;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests that the Drupal-extended ControllerResolver is functioning properly.
 *
 * @see \Drupal\Core\Controller\ControllerResolver
 */
class ControllerResolverTest extends UnitTestCase {

  /**
   * The tested controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolver
   */
  public $controllerResolver;

  public static function getInfo() {
    return array(
      'name' => 'Controller Resolver tests',
      'description' => 'Tests that the Drupal-extended ControllerResolver is functioning properly.',
      'group' => 'Routing',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    $this->controllerResolver = new ControllerResolver($container);
  }

  /**
   * Tests getArguments().
   *
   * Ensure that doGetArguments uses converted arguments if available.
   *
   * @see \Drupal\Core\Controller\ControllerResolver::getArguments()
   * @see \Drupal\Core\Controller\ControllerResolver::doGetArguments()
   */
  public function testGetArguments() {
    $controller = function(EntityInterface $entity, $user) {
    };
    $mock_entity = $this->getMockBuilder('Drupal\Core\Entity\Entity')
      ->disableOriginalConstructor()
      ->getMock();
    $mock_account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $request = new \Symfony\Component\HttpFoundation\Request(array(), array(), array(
      'entity' => $mock_entity,
      'user' => $mock_account,
      '_raw_variables' => new ParameterBag(array('entity' => 1, 'user' => 1)),
    ));
    $arguments = $this->controllerResolver->getArguments($request, $controller);

    $this->assertEquals($mock_entity, $arguments[0], 'Type hinted variables should use upcasted values.');
    $this->assertEquals(1, $arguments[1], 'Not type hinted variables should use not upcasted values.');
  }

}
