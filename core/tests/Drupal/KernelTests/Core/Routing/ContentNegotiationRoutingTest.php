<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Routing;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests content negotiation routing variations.
 *
 * @group ContentNegotiation
 */
class ContentNegotiationRoutingTest extends KernelTestBase {

  use PathAliasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['content_negotiation_test', 'path_alias', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('path_alias');
  }

  /**
   * Tests the content negotiation aspect of routing.
   */
  public function testContentRouting(): void {
    // Alias with extension pointing to no extension/constant content-type.
    $this->createPathAlias('/content_negotiation/html', '/alias.html');

    // Alias with extension pointing to dynamic extension/linked content-type.
    $this->createPathAlias('/content_negotiation/html?_format=json', '/alias.json');

    $tests = [
      // ['path', 'accept', 'content-type'],

      // Extension is part of the route path. Constant Content-type.
      ['content_negotiation/simple.json', '', 'application/json'],
      ['content_negotiation/simple.json', 'application/xml', 'application/json'],
      ['content_negotiation/simple.json', 'application/json', 'application/json'],
      // No extension. Constant Content-type.
      ['content_negotiation/html', '', 'text/html'],
      ['content_negotiation/html', '*/*', 'text/html'],
      ['content_negotiation/html', 'application/xml', 'text/html'],
      ['content_negotiation/html', 'text/xml', 'text/html'],
      ['content_negotiation/html', 'text/html', 'text/html'],
      // Dynamic extension. Linked Content-type.
      ['content_negotiation/html?_format=json', '', 'application/json'],
      ['content_negotiation/html?_format=json', '*/*', 'application/json'],
      ['content_negotiation/html?_format=json', 'application/xml', 'application/json'],
      ['content_negotiation/html?_format=json', 'application/json', 'application/json'],
      ['content_negotiation/html?_format=xml', '', 'application/xml'],
      ['content_negotiation/html?_format=xml', '*/*', 'application/xml'],
      ['content_negotiation/html?_format=xml', 'application/json', 'application/xml'],
      ['content_negotiation/html?_format=xml', 'application/xml', 'application/xml'],

      // Path with a variable. Variable contains a period.
      ['content_negotiation/plugin/plugin.id', '', 'text/html'],
      ['content_negotiation/plugin/plugin.id', '*/*', 'text/html'],
      ['content_negotiation/plugin/plugin.id', 'text/xml', 'text/html'],
      ['content_negotiation/plugin/plugin.id', 'text/html', 'text/html'],

      // Alias with extension pointing to no extension/constant content-type.
      ['alias.html', '', 'text/html'],
      ['alias.html', '*/*', 'text/html'],
      ['alias.html', 'text/xml', 'text/html'],
      ['alias.html', 'text/html', 'text/html'],
    ];

    foreach ($tests as $test) {
      $path = $test[0];
      $accept_header = $test[1];
      $content_type = $test[2];
      $message = "Testing path:$path Accept:$accept_header Content-type:$content_type";
      $request = Request::create('/' . $path);
      if ($accept_header) {
        $request->headers->set('Accept', $accept_header);
      }

      /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
      $kernel = \Drupal::getContainer()->get('http_kernel');
      $response = $kernel->handle($request);
      $this->assertEquals(Response::HTTP_OK, $response->getStatusCode(), $message);
      $this->assertStringContainsString($content_type, $response->headers->get('Content-type'), $message);
    }
  }

  /**
   * Full negotiation by header only.
   */
  public function testFullNegotiation(): void {
    $this->enableModules(['accept_header_routing_test']);
    $tests = [
      // ['path', 'accept', 'content-type'],

      // 406?
      ['content_negotiation/negotiate', '', 'text/html'],
      // 406?
      ['content_negotiation/negotiate', '', 'text/html'],
      // ['content_negotiation/negotiate', '*/*', '??'],
      ['content_negotiation/negotiate', 'application/json', 'application/json'],
      ['content_negotiation/negotiate', 'application/xml', 'application/xml'],
      ['content_negotiation/negotiate', 'application/json', 'application/json'],
      ['content_negotiation/negotiate', 'application/xml', 'application/xml'],
    ];

    foreach ($tests as $test) {
      $path = $test[0];
      $accept_header = $test[1];
      $content_type = $test[2];
      $request = Request::create('/' . $path);
      $request->headers->set('Accept', $accept_header);

      /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
      $kernel = \Drupal::getContainer()->get('http_kernel');
      $response = $kernel->handle($request);
      $this->assertEquals(Response::HTTP_OK, $response->getStatusCode(), "Testing path:{$path} Accept:{$accept_header} Content-type:{$content_type}");
    }
  }

}
