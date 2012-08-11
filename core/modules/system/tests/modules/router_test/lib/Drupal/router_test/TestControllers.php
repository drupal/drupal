<?php

namespace Drupal\router_test;

use Symfony\Component\HttpFoundation\Response;

/**
 * Description of TestControllers
 */
class TestControllers {

  public function test1() {
    return new Response('test1');
  }

}

