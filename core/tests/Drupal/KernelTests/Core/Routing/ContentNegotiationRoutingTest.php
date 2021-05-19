<?php

namespace Drupal\KernelTests\Core\Routing;

use Drupal\Core\DependencyInjection\ContainerBuilder;
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
  protected static $modules = ['conneg_test', 'path_alias'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('path_alias');
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    // \Drupal\KernelTests\KernelTestBase::register() removes the alias path
    // processor.
    if ($container->hasDefinition('path_alias.path_processor')) {
      $definition = $container->getDefinition('path_alias.path_processor');
      $definition->addTag('path_processor_inbound', ['priority' => 100])->addTag('path_processor_outbound', ['priority' => 300]);
    }
  }

  /**
   * Tests the content negotiation aspect of routing.
   */
  public function testContentRouting() {
    // Alias with extension pointing to no extension/constant content-type.
    $this->createPathAlias('/conneg/html', '/alias.html');

    // Alias with extension pointing to dynamic extension/linked content-type.
    $this->createPathAlias('/conneg/html?_format=json', '/alias.json');

    $tests = [
      // ['path', 'accept', 'content-type'],

      // Extension is part of the route path. Constant Content-type.
      ['conneg/simple.json', '', 'application/json'],
      ['conneg/simple.json', 'application/xml', 'application/json'],
      ['conneg/simple.json', 'application/json', 'application/json'],
      // No extension. Constant Content-type.
      ['conneg/html', '', 'text/html'],
      ['conneg/html', '*/*', 'text/html'],
      ['conneg/html', 'application/xml', 'text/html'],
      ['conneg/html', 'text/xml', 'text/html'],
      ['conneg/html', 'text/html', 'text/html'],
      // Dynamic extension. Linked Content-type.
      ['conneg/html?_format=json', '', 'application/json'],
      ['conneg/html?_format=json', '*/*', 'application/json'],
      ['conneg/html?_format=json', 'application/xml', 'application/json'],
      ['conneg/html?_format=json', 'application/json', 'application/json'],
      ['conneg/html?_format=xml', '', 'application/xml'],
      ['conneg/html?_format=xml', '*/*', 'application/xml'],
      ['conneg/html?_format=xml', 'application/json', 'application/xml'],
      ['conneg/html?_format=xml', 'application/xml', 'application/xml'],

      // Path with a variable. Variable contains a period.
      ['conneg/plugin/plugin.id', '', 'text/html'],
      ['conneg/plugin/plugin.id', '*/*', 'text/html'],
      ['conneg/plugin/plugin.id', 'text/xml', 'text/html'],
      ['conneg/plugin/plugin.id', 'text/html', 'text/html'],

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
      // Verbose message since simpletest doesn't let us provide a message and
      // see the error.
      $this->assertTrue(TRUE, $message);
      $this->assertEqual(Response::HTTP_OK, $response->getStatusCode());
      $this->assertStringContainsString($content_type, $response->headers->get('Content-type'));
    }
  }

  /**
   * Full negotiation by header only.
   */
  public function testFullNegotiation() {
    $this->enableModules(['accept_header_routing_test']);
    $tests = [
      // ['path', 'accept', 'content-type'],

      // 406?
      ['conneg/negotiate', '', 'text/html'],
      // 406?
      ['conneg/negotiate', '', 'text/html'],
      // ['conneg/negotiate', '*/*', '??'],
      ['conneg/negotiate', 'application/json', 'application/json'],
      ['conneg/negotiate', 'application/xml', 'application/xml'],
      ['conneg/negotiate', 'application/json', 'application/json'],
      ['conneg/negotiate', 'application/xml', 'application/xml'],
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
      $this->assertEqual(Response::HTTP_OK, $response->getStatusCode(), "Testing path:{$path} Accept:{$accept_header} Content-type:{$content_type}");
    }
  }

}
