<?php

/**
 * @file
 * Contains \Drupal\system\Tests\PathProcessor\PathProcessorIntegrationTest.
 */

namespace Drupal\system\Tests\PathProcessor;

use Drupal\simpletest\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @see \Drupal\Core\PathProcessor\PathProcessorCurrent
 * @group path_processor
 */
class PathProcessorCurrentIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['router']);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests the output process.
   */
  public function testProcessOutbound() {
    $request_stack = \Drupal::requestStack();
    /** @var \Symfony\Component\Routing\RequestContext $request_context */
    $request_context = \Drupal::service('router.request_context');

    // Test request with subdir on homepage.
    $server = [
      'SCRIPT_NAME' => '/subdir/index.php',
      'SCRIPT_FILENAME' => DRUPAL_ROOT . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/subdir', 'GET', [], [], [], $server);
    $request_stack->push($request);
    $request_context->fromRequest($request);
    $this->assertEqual('/subdir/', \Drupal::url('<current>'));

    // Test request with subdir on other page.
    $server = [
      'SCRIPT_NAME' => '/subdir/index.php',
      'SCRIPT_FILENAME' => DRUPAL_ROOT . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/subdir/node/add', 'GET', [], [], [], $server);
    $request_stack->push($request);
    $request_context->fromRequest($request);
    $this->assertEqual('/subdir/node/add', \Drupal::url('<current>'));

    // Test request without subdir on the homepage.
    $server = [
      'SCRIPT_NAME' => '/index.php',
      'SCRIPT_FILENAME' => DRUPAL_ROOT . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/', 'GET', [], [], [], $server);
    $request_stack->push($request);
    $request_context->fromRequest($request);
    $this->assertEqual('/', \Drupal::url('<current>'));

    // Test request without subdir on other page.
    $server = [
      'SCRIPT_NAME' => '/index.php',
      'SCRIPT_FILENAME' => DRUPAL_ROOT . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/node/add', 'GET', [], [], [], $server);
    $request_stack->push($request);
    $request_context->fromRequest($request);
    $this->assertEqual('/node/add', \Drupal::url('<current>'));
  }

}
