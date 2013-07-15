<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\AccessManagerTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessManager;
use Drupal\Core\Access\DefaultAccessCheck;
use Drupal\Tests\UnitTestCase;
use Drupal\router_test\Access\DefinedTestAccessCheck;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the access manager.
 *
 * @see \Drupal\Core\Access\AccessManager
 */
class AccessManagerTest extends UnitTestCase {

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The collection of routes, which are tested.
   *
   * @var \Symfony\Component\Routing\RouteCollection
   */
  protected $routeCollection;

  /**
   * The access manager to test.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

  public static function getInfo() {
    return array(
      'name' => 'Access manager tests',
      'description' => 'Test for the AccessManager object.',
      'group' => 'Routing',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $this->accessManager = new AccessManager();
    $this->accessManager->setContainer($this->container);

    $this->routeCollection = new RouteCollection();
    $this->routeCollection->add('test_route_1', new Route('/test-route-1'));
    $this->routeCollection->add('test_route_2', new Route('/test-route-2', array(), array('_access' => 'TRUE')));
    $this->routeCollection->add('test_route_3', new Route('/test-route-3', array(), array('_access' => 'FALSE')));
  }

  /**
   * Tests \Drupal\Core\Access\AccessManager::setChecks().
   */
  public function testSetChecks() {
    // Check setChecks without any access checker defined yet.
    $this->accessManager->setChecks($this->routeCollection);

    foreach ($this->routeCollection->all() as $route) {
      $this->assertNull($route->getOption('_access_checks'));
    }

    $this->setupAccessChecker();

    $this->accessManager->setChecks($this->routeCollection);

    $this->assertEquals($this->routeCollection->get('test_route_1')->getOption('_access_checks'), NULL);
    $this->assertEquals($this->routeCollection->get('test_route_2')->getOption('_access_checks'), array('test_access_default'));
    $this->assertEquals($this->routeCollection->get('test_route_3')->getOption('_access_checks'), array('test_access_default'));
  }

  /**
   * Tests \Drupal\Core\Access\AccessManager::check().
   */
  public function testCheck() {
    $request = new Request();

    // Check check without any access checker defined yet.
    foreach ($this->routeCollection->all() as $route) {
      $this->assertFalse($this->accessManager->check($route, $request));
    }

    $this->setupAccessChecker();

    // An access checker got setup, but the routes haven't been setup using
    // setChecks.
    foreach ($this->routeCollection->all() as $route) {
      $this->assertFalse($this->accessManager->check($route, $request));
    }

    $this->accessManager->setChecks($this->routeCollection);

    $this->assertFalse($this->accessManager->check($this->routeCollection->get('test_route_1'), $request));
    $this->assertTrue($this->accessManager->check($this->routeCollection->get('test_route_2'), $request));
    $this->assertFalse($this->accessManager->check($this->routeCollection->get('test_route_3'), $request));
  }

  /**
   * Provides data for the conjunction test.
   *
   * @return array
   *   An array of data for check conjunctions.
   *
   * @see \Drupal\Tests\Core\Access\AccessManagerTest::testCheckConjunctions()
   */
  public function providerTestCheckConjunctions() {
    $access_configurations = array();
    $access_configurations[] = array(
      'conjunction' => 'ALL',
      'name' => 'test_route_4',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ALL',
      'name' => 'test_route_5',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ALL',
      'name' => 'test_route_6',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ALL',
      'name' => 'test_route_7',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::ALLOW,
      'expected' => TRUE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ALL',
      'name' => 'test_route_8',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ALL',
      'name' => 'test_route_9',
      'condition_one' => AccessCheckInterface::DENY,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ANY',
      'name' => 'test_route_10',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ANY',
      'name' => 'test_route_11',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => TRUE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ANY',
      'name' => 'test_route_12',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ANY',
      'name' => 'test_route_13',
      'condition_one' => AccessCheckInterface::ALLOW,
      'condition_two' => AccessCheckInterface::ALLOW,
      'expected' => TRUE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ANY',
      'name' => 'test_route_14',
      'condition_one' => AccessCheckInterface::KILL,
      'condition_two' => AccessCheckInterface::KILL,
      'expected' => FALSE,
    );
    $access_configurations[] = array(
      'conjunction' => 'ANY',
      'name' => 'test_route_15',
      'condition_one' => AccessCheckInterface::DENY,
      'condition_two' => AccessCheckInterface::DENY,
      'expected' => FALSE,
    );

    return $access_configurations;
  }

  /**
   * Test \Drupal\Core\Access\AccessManager::check() with conjunctions.
   *
   * @dataProvider providerTestCheckConjunctions
   */
  public function testCheckConjunctions($conjunction, $name, $condition_one, $condition_two, $expected_access) {
    $this->setupAccessChecker();
    $access_check = new DefinedTestAccessCheck();
    $this->container->register('test_access_defined', $access_check);
    $this->accessManager->addCheckService('test_access_defined');

    $request = new Request();

    $route_collection = new RouteCollection();
    // Setup a test route for each access configuration.
    $requirements = array(
      '_access' => static::convertAccessCheckInterfaceToString($condition_one),
      '_test_access' => static::convertAccessCheckInterfaceToString($condition_two),
    );
    $options = array('_access_mode' => $conjunction);
    $route = new Route($name, array(), $requirements, $options);
    $route_collection->add($name, $route);

    $this->accessManager->setChecks($route_collection);
    $this->assertSame($this->accessManager->check($route, $request), $expected_access);
  }

  /**
   * Tests the static access checker interface.
   */
  public function testStaticAccessCheckInterface() {
    $mock_static = $this->getMock('Drupal\Core\Access\StaticAccessCheckInterface');
    $mock_static->expects($this->once())
      ->method('appliesTo')
      ->will($this->returnValue(array('_access')));

    $this->container->set('test_static_access', $mock_static);
    $this->accessManager->addCheckService('test_static_access');

    $this->accessManager->setChecks($this->routeCollection);
  }

  /**
   * Converts AccessCheckInterface constants to a string.
   *
   * @param mixed $constant
   *   The access constant which is tested, so either
   *   AccessCheckInterface::ALLOW, AccessCheckInterface::DENY OR
   *   AccessCheckInterface::KILL.
   *
   * @return string
   *   The corresponding string used in route requirements, so 'TRUE', 'FALSE'
   *   or 'NULL'.
   */
  protected static function convertAccessCheckInterfaceToString($constant) {
    if ($constant === AccessCheckInterface::ALLOW) {
      return 'TRUE';
    }
    if ($constant === AccessCheckInterface::DENY) {
      return 'NULL';
    }
    if ($constant === AccessCheckInterface::KILL) {
      return 'FALSE';
    }
  }

  /**
   * Adds a default access check service to the container and the access manager.
   */
  protected function setupAccessChecker() {
    $this->accessManager = new AccessManager();
    $this->accessManager->setContainer($this->container);
    $access_check = new DefaultAccessCheck();
    $this->container->register('test_access_default', $access_check);
    $this->accessManager->addCheckService('test_access_default');
  }

}
