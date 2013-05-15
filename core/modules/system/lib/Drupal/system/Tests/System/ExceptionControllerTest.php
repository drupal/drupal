<?php

/**
 * Definition of Drupal\system\Tests\System\ExceptionControllerTest.
 */

namespace Drupal\system\Tests\System;

use \Drupal\Core\ContentNegotiation;
use \Drupal\Core\Controller\ExceptionController;
use \Drupal\simpletest\UnitTestBase;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpKernel\Exception\FlattenException;

/**
 * Tests exception controller.
 */
class ExceptionControllerTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Exception controller',
      'description' => 'Performs tests on the exception handler controller class.',
      'group' => 'System',
    );
  }

  /**
   * Ensure the execute() method returns a valid response on 405 exceptions.
   */
  public function test405HTML() {
    $exception = new \Exception('Test exception');
    $flat_exception = FlattenException::create($exception, 405);
    $exception_controller = new ExceptionController(new ContentNegotiation());
    $response = $exception_controller->execute($flat_exception, new Request());
    $this->assertEqual($response->getStatusCode(), 405, 'HTTP status of response is correct.');
    $this->assertEqual($response->getContent(), 'Method Not Allowed', 'HTTP response body is correct.');
  }
}
