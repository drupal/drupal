<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Controller\ExceptionControllerTest
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\ContentNegotiation;
use Drupal\Core\Controller\ExceptionController;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\FlattenException;

/**
 * Tests exception controller.
 *
 * @see \Drupal\Core\Controller\ExceptionController
 *
 * @group Drupal
 */
class ExceptionControllerTest extends UnitTestCase {

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
    $this->assertEquals($response->getStatusCode(), 405, 'HTTP status of response is correct.');
    $this->assertEquals($response->getContent(), 'Method Not Allowed', 'HTTP response body is correct.');
  }

}
