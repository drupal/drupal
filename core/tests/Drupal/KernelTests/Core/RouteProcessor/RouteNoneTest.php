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
 * Tests the <none> route processor.
 *
 * @see system.routing.yml
 * @see \Drupal\Core\Routing\UrlGenerator
 * @group route_processor
 */
class RouteNoneTest extends KernelTestBase {

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
    $expected_cacheability = (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT);

    $request_stack = \Drupal::requestStack();
    /** @var \Symfony\Component\Routing\RequestContext $request_context */
    $request_context = \Drupal::service('router.request_context');

    // Test request with subdir on homepage.
    $server = [
      'SCRIPT_NAME' => '/subdir/index.php',
      'SCRIPT_FILENAME' => $this->root . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/subdir', 'GET', [], [], [], $server);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, '<front>');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/'));

    $request_stack->push($request);
    $request_context->fromRequest($request);
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('');
    $this->assertEqual($url, $this->urlGenerator->generateFromRoute('<none>', [], [], TRUE, TRUE));
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('#test-fragment');
    $this->assertEqual($url, $this->urlGenerator->generateFromRoute('<none>', [], ['fragment' => 'test-fragment'], TRUE));

    // Test request with subdir on other page.
    $server = [
      'SCRIPT_NAME' => '/subdir/index.php',
      'SCRIPT_FILENAME' => $this->root . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/subdir/node/add', 'GET', [], [], [], $server);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'node.add');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/node/add'));

    $request_stack->push($request);
    $request_context->fromRequest($request);
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('');
    $this->assertEqual($url, $this->urlGenerator->generateFromRoute('<none>', [], [], TRUE, TRUE));
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('#test-fragment');
    $this->assertEqual($url, $this->urlGenerator->generateFromRoute('<none>', [], ['fragment' => 'test-fragment'], TRUE));

    // Test request without subdir on the homepage.
    $server = [
      'SCRIPT_NAME' => '/index.php',
      'SCRIPT_FILENAME' => $this->root . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/', 'GET', [], [], [], $server);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, '<front>');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/'));

    $request_stack->push($request);
    $request_context->fromRequest($request);
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('');
    $this->assertEqual($url, $this->urlGenerator->generateFromRoute('<none>', [], [], TRUE, TRUE));
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('#test-fragment');
    $this->assertEqual($url, $this->urlGenerator->generateFromRoute('<none>', [], ['fragment' => 'test-fragment'], TRUE));

    // Test request without subdir on other page.
    $server = [
      'SCRIPT_NAME' => '/index.php',
      'SCRIPT_FILENAME' => $this->root . '/index.php',
      'SERVER_NAME' => 'http://www.example.com',
    ];
    $request = Request::create('/node/add', 'GET', [], [], [], $server);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'node.add');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/node/add'));

    $request_stack->push($request);
    $request_context->fromRequest($request);
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('');
    $this->assertEqual($url, $this->urlGenerator->generateFromRoute('<none>', [], [], TRUE, TRUE));
    $url = GeneratedUrl::createFromObject($expected_cacheability)->setGeneratedUrl('#test-fragment');
    $this->assertEqual($url, $this->urlGenerator->generateFromRoute('<none>', [], ['fragment' => 'test-fragment'], TRUE));
  }

}
