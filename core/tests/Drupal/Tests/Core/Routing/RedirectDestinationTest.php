<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Routing\RedirectDestination
 * @group Routing
 */
class RedirectDestinationTest extends UnitTestCase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The mocked URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $urlGenerator;

  /**
   * The tested redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestination
   */
  protected $redirectDestination;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->requestStack = new RequestStack();
    $this->urlGenerator = $this->createMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $this->redirectDestination = new RedirectDestination($this->requestStack, $this->urlGenerator);
  }

  protected function setupUrlGenerator() {
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->willReturnCallback(function ($route, $parameters, $options) {
        $query_string = '';
        if (!empty($options['query'])) {
          $query_string = '?' . UrlHelper::buildQuery($options['query']);
        }

        return '/current-path' . $query_string;
      });
  }

  /**
   * Tests destination passed via $_GET.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to test.
   * @param string $expected_destination
   *   The expected destination.
   *
   * @dataProvider providerGet
   *
   * @covers ::get
   */
  public function testGet(Request $request, $expected_destination) {
    $this->requestStack->push($request);
    $this->setupUrlGenerator();

    // Call in twice in order to ensure it returns the same the next time.
    $this->assertEquals($expected_destination, $this->redirectDestination->get());
    $this->assertEquals($expected_destination, $this->redirectDestination->get());
  }

  /**
   * @dataProvider providerGet
   *
   * @covers ::getAsArray
   */
  public function testGetAsArray(Request $request, $expected_destination) {
    $this->requestStack->push($request);
    $this->setupUrlGenerator();

    // Call in twice in order to ensure it returns the same the next time.
    $this->assertEquals(['destination' => $expected_destination], $this->redirectDestination->getAsArray());
    $this->assertEquals(['destination' => $expected_destination], $this->redirectDestination->getAsArray());
  }

  public function providerGet() {
    $data = [];

    $request = Request::create('/');
    $request->query->set('destination', '/example');
    // A request with a destination query.
    $data[] = [$request, '/example'];

    // A request without a destination query,
    $request = Request::create('/');
    $data[] = [$request, '/current-path'];

    // A request without destination query, but other query attributes.
    $request = Request::create('/');
    $request->query->set('other', 'value');
    $data[] = [$request, '/current-path?other=value'];

    // A request with a dedicated specified external destination.
    $request = Request::create('/');
    $request->query->set('destination', 'https://www.drupal.org');
    $data[] = [$request, '/'];

    return $data;
  }

  /**
   * @covers ::set
   * @covers ::get
   */
  public function testSetBeforeGetCall() {
    $this->redirectDestination->set('/example');
    $this->assertEquals('/example', $this->redirectDestination->get());
  }

  /**
   * @covers ::set
   * @covers ::get
   */
  public function testSetAfterGetCall() {
    $request = Request::create('/');
    $request->query->set('destination', '/other-example');
    $this->requestStack->push($request);
    $this->setupUrlGenerator();

    $this->redirectDestination->set('/example');
    $this->assertEquals('/example', $this->redirectDestination->get());
  }

}
