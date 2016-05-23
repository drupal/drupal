<?php

namespace Drupal\KernelTests\Core\RouteProcessor;

use Drupal\Core\Cache\Cache;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
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
  public static $modules = ['system'];

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->urlGenerator = \Drupal::urlGenerator();
  }

  /**
   * Tests the output process.
   */
  public function testProcessOutbound() {
    $expected_cacheability = (new BubbleableMetadata())
      ->addCacheContexts(['route'])
      ->setCacheMaxAge(Cache::PERMANENT);

    $request_stack = \Drupal::requestStack();
    /** @var \Symfony\Component\Routing\RequestContext $request_context */
    $request_context = \Drupal::service('router.request_context');

    // Test request with subdir on homepage.
    $server = [
      'SCRIPT_NAME' => '/subdir/index.php',
      'SCRIPT_FILENAME' => \Drupal::root() . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/subdir/', 'GET', [], [], [], $server);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, '<front>');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/'));

    $request_stack->push($request);
    $request_context->fromRequest($request);
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('/subdir/');
    $this->assertEqual($url, $this->urlGenerator->generateFromRoute('<current>', [], [], TRUE));

    // Test request with subdir on other page.
    $server = [
      'SCRIPT_NAME' => '/subdir/index.php',
      'SCRIPT_FILENAME' => \Drupal::root() . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/subdir/node/add', 'GET', [], [], [], $server);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'node.add');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/node/add'));

    $request_stack->push($request);
    $request_context->fromRequest($request);
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('/subdir/node/add');
    $this->assertEqual($url, $this->urlGenerator->generateFromRoute('<current>', [], [], TRUE));

    // Test request without subdir on the homepage.
    $server = [
      'SCRIPT_NAME' => '/index.php',
      'SCRIPT_FILENAME' => \Drupal::root() . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/', 'GET', [], [], [], $server);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, '<front>');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/'));

    $request_stack->push($request);
    $request_context->fromRequest($request);
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('/');
    $this->assertEqual($url, $this->urlGenerator->generateFromRoute('<current>', [], [], TRUE));

    // Test request without subdir on other page.
    $server = [
      'SCRIPT_NAME' => '/index.php',
      'SCRIPT_FILENAME' => \Drupal::root() . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/node/add', 'GET', [], [], [], $server);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'node.add');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/node/add'));

    $request_stack->push($request);
    $request_context->fromRequest($request);
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('/node/add');
    $this->assertEqual($url, $this->urlGenerator->generateFromRoute('<current>', [], [], TRUE));

    // Test request without a found route. This happens for example on an
    // not found exception page.
    $server = [
      'SCRIPT_NAME' => '/index.php',
      'SCRIPT_FILENAME' => \Drupal::root() . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/invalid-path', 'GET', [], [], [], $server);

    $request_stack->push($request);
    $request_context->fromRequest($request);
    // In case we have no routing, the current route should point to the front,
    // and the cacheability does not depend on the 'route' cache context, since
    // no route was involved at all: this is fallback behavior.
    $url = GeneratedUrl::createFromObject((new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT))->setGeneratedUrl('/');
    $this->assertEqual($url, $this->urlGenerator->generateFromRoute('<current>', [], [], TRUE));
  }

}
