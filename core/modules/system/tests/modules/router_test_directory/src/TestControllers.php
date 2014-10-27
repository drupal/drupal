<?php

/**
 * @file
 * Definition of Drupal\router_test\TestControllers.
 */

namespace Drupal\router_test;

use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\user\UserInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for testing the routing system.
 */
class TestControllers {

  public function test() {
    return new Response('test');
  }

  public function test1() {
    return new Response('test1');
  }

  public function test2() {
    return ['#markup' => "test2"];
  }

  public function test3($value) {
    return ['#markup' => $value];
  }

  public function test4($value) {
    return ['#markup' => $value];
  }

  public function test5() {
    return ['#markup' => "test5"];
  }

  public function test6() {
    return new Response('test6');
  }

  public function test7() {
    return new Response('test7text');
  }

  public function test8() {
    return new Response('test8');
  }

  public function test9($uid) {
    $text = 'Route not matched.';
    try {
      $match = \Drupal::service('router.no_access_checks')->match('/user/' . $uid);
      if (isset($match['user']) && $match['user'] instanceof UserInterface) {
        $text = sprintf('User route "%s" was matched.', $match[RouteObjectInterface::ROUTE_NAME]);
      }
    }
    catch (ParamNotConvertedException $e) {
    }
    return new Response($text);
  }

}
