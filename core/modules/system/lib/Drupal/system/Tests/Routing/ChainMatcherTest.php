<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Routing\ChainMatcherTest.
 */

namespace Drupal\system\Tests\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

use Drupal\simpletest\UnitTestBase;
use Drupal\Core\Routing\ChainMatcher;

use Exception;

/**
 * Basic tests for the ChainMatcher.
 */
class ChainMatcherTest extends UnitTestBase {
  
  public static function getInfo() {
    return array(
      'name' => 'Chain matcher tests',
      'description' => 'Confirm that the chain matcher is working correctly.',
      'group' => 'Routing',
    );
  }

  /**
   * Confirms that the expected exception is thrown.
   */
  public function testMethodNotAllowed() {

    $chain = new ChainMatcher();

    $method_not_allowed = new MockMatcher(function(Request $request) {
      throw new MethodNotAllowedException(array('POST'));
    });

    try {
      $chain->add($method_not_allowed);
      $chain->matchRequest(Request::create('my/path'));
    }
    catch (MethodNotAllowedException $e) {
      $this->pass('Correct exception thrown.');
    }
    catch (Exception $e) {
      $this->fail('Incorrect exception thrown: ' . get_class($e));
    }
  }

  /**
   * Confirms that the expected exception is thrown.
   */
  public function testRequestNotFound() {

    $chain = new ChainMatcher();

    $resource_not_found = new MockMatcher(function(Request $request) {
      throw new ResourceNotFoundException();
    });

    try {
      $chain->add($resource_not_found);
      $chain->matchRequest(Request::create('my/path'));
    }
    catch (ResourceNotFoundException $e) {
      $this->pass('Correct exception thrown.');
    }
    catch (Exception $e) {
      $this->fail('Incorrect exception thrown: ' . get_class($e));
    }
  }

  /**
   * Confirms that the expected exception is thrown.
   */
  public function testRequestFound() {

    $chain = new ChainMatcher();

    $method_not_allowed = new MockMatcher(function(Request $request) {
      throw new MethodNotAllowedException(array('POST'));
    });

    $resource_not_found = new MockMatcher(function(Request $request) {
      throw new ResourceNotFoundException();
    });

    $found_data = new MockMatcher(function(Request $request) {
      return array('_controller' => 'foo');
    });

    try {
      $chain->add($method_not_allowed);
      $chain->add($resource_not_found);
      $chain->add($found_data);
      $request = Request::create('my/path');
      $attributes = $chain->matchRequest($request);
      $this->assertEqual($attributes['_controller'], 'foo', 'Correct attributes returned.');
    }
    catch (Exception $e) {
      $this->fail('Exception thrown when a match should have been successful: ' . get_class($e));
    }
  }

}
