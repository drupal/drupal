<?php

namespace Drupal\system\Tests\Routing;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\simpletest\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests content negotiation routing variations.
 *
 * @group ContentNegotiation
 */
class ContentNegotiationRoutingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'conneg_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    \Drupal::unsetContainer();
    parent::setUp();

    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function containerBuild(ContainerBuilder $container) {
    parent::containerBuild($container);

    // \Drupal\simpletest\KernelTestBase::containerBuild() removes the alias path
    // processor.
    if ($container->hasDefinition('path_processor_alias')) {
      $definition = $container->getDefinition('path_processor_alias');
      $definition->addTag('path_processor_inbound', ['priority' => 100])->addTag('path_processor_outbound', ['priority' => 300]);
    }
  }

  /**
   * Tests the content negotiation aspect of routing.
   */
  function testContentRouting() {
    /** @var \Drupal\Core\Path\AliasStorageInterface $path_alias_storage */
    $path_alias_storage = $this->container->get('path.alias_storage');
    // Alias with extension pointing to no extension/constant content-type.
    $path_alias_storage->save('/conneg/html', '/alias.html');

    // Alias with extension pointing to dynamic extension/linked content-type.
    $path_alias_storage->save('/conneg/html?_format=json', '/alias.json');

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
      $this->assertEqual($response->getStatusCode(), Response::HTTP_OK);
      $this->assertTrue(strpos($response->headers->get('Content-type'), $content_type) !== FALSE);
    }
  }

  /**
   * Full negotiation by header only.
   */
  public function testFullNegotiation() {
    $this->enableModules(['accept_header_routing_test']);
    \Drupal::service('router.builder')->rebuild();
    $tests = [
      // ['path', 'accept', 'content-type'],

      ['conneg/negotiate', '', 'text/html'], // 406?
      ['conneg/negotiate', '', 'text/html'], // 406?
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
      $message = "Testing path:$path Accept:$accept_header Content-type:$content_type";
      $request = Request::create('/' . $path);
      $request->headers->set('Accept', $accept_header);

      /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
      $kernel = \Drupal::getContainer()->get('http_kernel');
      $response = $kernel->handle($request);
      // Verbose message since simpletest doesn't let us provide a message and
      // see the error.
      $this->pass($message);
      $this->assertEqual($response->getStatusCode(), Response::HTTP_OK);
    }
  }

}
