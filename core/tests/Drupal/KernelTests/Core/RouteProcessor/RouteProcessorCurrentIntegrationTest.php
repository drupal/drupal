<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\RouteProcessor;

use Drupal\Core\Cache\Cache;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Route;

/**
 * Tests the <current> route processor.
 *
 * @see \Drupal\Core\RouteProcessor\RouteProcessorCurrent
 * @group route_processor
 */
class RouteProcessorCurrentIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->urlGenerator = \Drupal::urlGenerator();
  }

  /**
   * Tests the output process.
   */
  public function testProcessOutbound(): void {
    $expected_cacheability = (new BubbleableMetadata())
      ->addCacheContexts(['route'])
      ->setCacheMaxAge(Cache::PERMANENT);

    $request_stack = \Drupal::requestStack();
    /** @var \Symfony\Component\Routing\RequestContext $request_context */
    $request_context = \Drupal::service('router.request_context');

    // Test request with subdir on homepage.
    $server = [
      'SCRIPT_NAME' => '/subdir/index.php',
      'SCRIPT_FILENAME' => $this->root . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/subdir/', 'GET', [], [], [], $server);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, '<front>');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/'));
    $request->setSession(new Session(new MockArraySessionStorage()));

    $request_stack->push($request);
    $request_context->fromRequest($request);
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('/subdir/');
    $this->assertEquals($this->urlGenerator->generateFromRoute('<current>', [], [], TRUE), $url);

    // Test request with subdir on other page.
    $server = [
      'SCRIPT_NAME' => '/subdir/index.php',
      'SCRIPT_FILENAME' => $this->root . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/subdir/node/add', 'GET', [], [], [], $server);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'node.add');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/node/add'));
    $request->setSession(new Session(new MockArraySessionStorage()));

    $request_stack->push($request);
    $request_context->fromRequest($request);
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('/subdir/node/add');
    $this->assertEquals($this->urlGenerator->generateFromRoute('<current>', [], [], TRUE), $url);

    // Test request without subdir on the homepage.
    $server = [
      'SCRIPT_NAME' => '/index.php',
      'SCRIPT_FILENAME' => $this->root . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/', 'GET', [], [], [], $server);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, '<front>');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/'));
    $request->setSession(new Session(new MockArraySessionStorage()));

    $request_stack->push($request);
    $request_context->fromRequest($request);
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('/');
    $this->assertEquals($this->urlGenerator->generateFromRoute('<current>', [], [], TRUE), $url);

    // Test request without subdir on other page.
    $server = [
      'SCRIPT_NAME' => '/index.php',
      'SCRIPT_FILENAME' => $this->root . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/node/add', 'GET', [], [], [], $server);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'node.add');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/node/add'));
    $request->setSession(new Session(new MockArraySessionStorage()));

    $request_stack->push($request);
    $request_context->fromRequest($request);
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('/node/add');
    $this->assertEquals($this->urlGenerator->generateFromRoute('<current>', [], [], TRUE), $url);

    // Test request without a found route. This happens for example on an
    // not found exception page.
    $server = [
      'SCRIPT_NAME' => '/index.php',
      'SCRIPT_FILENAME' => $this->root . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/invalid-path', 'GET', [], [], [], $server);
    $request->setSession(new Session(new MockArraySessionStorage()));

    $request_stack->push($request);
    $request_context->fromRequest($request);
    // In case we have no routing, the current route should point to the front,
    // and the cacheability does not depend on the 'route' cache context, since
    // no route was involved at all: this is fallback behavior.
    $url = GeneratedUrl::createFromObject((new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT))->setGeneratedUrl('/');
    $this->assertEquals($this->urlGenerator->generateFromRoute('<current>', [], [], TRUE), $url);
  }

}
