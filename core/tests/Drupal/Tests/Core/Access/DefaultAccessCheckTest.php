<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\DefaultAccessCheckTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\DefaultAccessCheck;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Defines a test to check the default access checker.
 *
 * @see \Drupal\Core\Access\DefaultAccessCheck
 */
class DefaultAccessCheckTest extends UnitTestCase {

  /**
   * The access checker to test.
   *
   * @var \Drupal\Core\Access\DefaultAccessCheck
   */
  protected $accessChecker;

  public static function getInfo() {
    return array(
      'name' => 'DefaultAccessCheck access checker',
      'description' => 'Tests the DefaultAccessCheck class.',
      'group' => 'Routing',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->accessChecker = new DefaultAccessCheck();
  }


  /**
   * Test the applies method.
   */
  public function testApplies() {
    $route = new Route('/test-route');
    $this->assertFalse($this->accessChecker->applies($route), 'Access checker applied even no _access was defined as requirement.');

    $route->addRequirements(array('_access' => 'FALSE'));
    $this->assertTrue($this->accessChecker->applies($route), 'Access checker applied even a _access was defined as requirement.');

    $route->addRequirements(array('_access' => 'TRUE'));
    $this->assertTrue($this->accessChecker->applies($route), 'Access checker applied even a _access was defined as requirement.');
  }

  /**
   * Test the access method.
   */
  public function testAccess() {
    $request = new Request(array());

    $route = new Route('/test-route', array(), array('_access' => 'NULL'));
    $this->assertNull($this->accessChecker->access($route, $request));

    $route = new Route('/test-route', array(), array('_access' => 'FALSE'));
    $this->assertFalse($this->accessChecker->access($route, $request));

    $route = new Route('/test-route', array(), array('_access' => 'TRUE'));
    $this->assertTrue($this->accessChecker->access($route, $request));
  }

}
