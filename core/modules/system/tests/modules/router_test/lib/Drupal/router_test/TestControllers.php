<?php

/**
 * @file
 * Definition of Drupal\router_test\TestControllers.
 */

namespace Drupal\router_test;

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
    return "test2";
  }

  public function test3($value) {
    return $value;
  }

  public function test4($value) {
    return $value;
  }

  public function test5() {
    return "test5";
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

}
