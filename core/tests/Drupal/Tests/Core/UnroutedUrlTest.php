<?php

namespace Drupal\Tests\Core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @coversDefaultClass \Drupal\Core\Url
 * @group UrlTest
 */
class UnroutedUrlTest extends UnitTestCase {

  /**
   * The URL assembler
   *
   * @var \Drupal\Core\Utility\UnroutedUrlAssemblerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlAssembler;

  /**
   * The router.
   *
   * @var \Drupal\Tests\Core\Routing\TestRouterInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $router;

  /**
   * An unrouted, external URL to test.
   *
   * @var string
   */
  protected $unroutedExternal = 'https://www.drupal.org';

  /**
   * An unrouted, internal URL to test.
   *
   * @var string
   */
  protected $unroutedInternal = 'base:robots.txt';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->urlAssembler = $this->getMock('Drupal\Core\Utility\UnroutedUrlAssemblerInterface');
    $this->urlAssembler->expects($this->any())
      ->method('assemble')
      ->will($this->returnArgument(0));

    $this->router = $this->getMock('Drupal\Tests\Core\Routing\TestRouterInterface');
    $container = new ContainerBuilder();
    $container->set('router.no_access_checks', $this->router);
    $container->set('unrouted_url_assembler', $this->urlAssembler);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the fromUri() method.
   *
   * @covers ::fromUri
   *
   * @dataProvider providerFromUri
   */
  public function testFromUri($uri, $is_external) {
    $url = Url::fromUri($uri);

    $this->assertInstanceOf('Drupal\Core\Url', $url);
  }


  /**
   * Data provider for testFromUri().
   */
  public function providerFromUri() {
    return [
      // [$uri, $is_external]
      // An external URI.
      ['https://www.drupal.org', TRUE],
      // An internal, unrouted, base-relative URI.
      ['base:robots.txt', FALSE],
      // Base-relative URIs with special characters.
      ['base:AKI@&hO@', FALSE],
      ['base:(:;2&+h^', FALSE],
      // Various token formats.
      ['base:node/[token]', FALSE],
      ['base:node/%', FALSE],
      ['base:node/[token:token]', FALSE],
      ['base:node/{{ token }}', FALSE],
    ];
  }

  /**
   * Tests the fromUri() method.
   *
   * @covers ::fromUri
   * @dataProvider providerFromInvalidUri
   * @expectedException \InvalidArgumentException
   */
  public function testFromInvalidUri($uri) {
    $url = Url::fromUri($uri);
  }

  /**
   * Data provider for testFromInvalidUri().
   */
  public function providerFromInvalidUri() {
    return [
      // Schemeless paths.
      ['test'],
      ['/test'],
      ['//test'],
      // Schemeless path with a query string.
      ['foo?bar'],
      // Only a query string.
      ['?bar'],
      // Only a fragment.
      ['#foo'],
      // Disallowed characters in the authority (host name) that are valid
      // elsewhere in the path.
      ['base://(:;2&+h^'],
      ['base://AKI@&hO@'],
    ];
  }

  /**
   * Tests the createFromRequest method.
   *
   * @covers ::createFromRequest
   *
   * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
   */
  public function testCreateFromRequest() {
    $request = Request::create('/test-path');

    $this->router->expects($this->once())
      ->method('matchRequest')
      ->with($request)
      ->will($this->throwException(new ResourceNotFoundException()));

    $this->assertNull(Url::createFromRequest($request));
  }

  /**
   * Tests the isExternal() method.
   *
   * @depends testFromUri
   * @dataProvider providerFromUri
   *
   * @covers ::isExternal
   */
  public function testIsExternal($uri, $is_external) {
    $url = Url::fromUri($uri);
    $this->assertSame($url->isExternal(), $is_external);
  }

  /**
   * Tests the toString() method.
   *
   * @depends testFromUri
   * @dataProvider providerFromUri
   *
   * @covers ::toString
   */
  public function testToString($uri) {
    $url = Url::fromUri($uri);
    $this->assertSame($uri, $url->toString());
  }

  /**
   * Tests the getRouteName() method.
   *
   * @depends testFromUri
   * @dataProvider providerFromUri
   *
   * @expectedException \UnexpectedValueException
   *
   * @covers ::getRouteName
   */
  public function testGetRouteName($uri) {
    $url = Url::fromUri($uri);
    $url->getRouteName();
  }

  /**
   * Tests the getRouteParameters() method.
   *
   * @depends testFromUri
   * @dataProvider providerFromUri
   *
   * @expectedException \UnexpectedValueException
   *
   * @covers ::getRouteParameters
   */
  public function testGetRouteParameters($uri) {
    $url = Url::fromUri($uri);
    $url->getRouteParameters();
  }

  /**
   * Tests the getInternalPath() method.
   *
   * @depends testFromUri
   * @dataProvider providerFromUri
   *
   * @covers ::getInternalPath
   *
   * @expectedException \Exception
   */
  public function testGetInternalPath($uri) {
    $url = Url::fromUri($uri);
    $this->assertNull($url->getInternalPath());
  }

  /**
   * Tests the getPath() method.
   *
   * @depends testFromUri
   * @dataProvider providerFromUri
   *
   * @covers ::getUri
   */
  public function testGetUri($uri) {
    $url = Url::fromUri($uri);
    $this->assertNotNull($url->getUri());
  }

  /**
   * Tests the getOptions() method.
   *
   * @depends testFromUri
   * @dataProvider providerFromUri
   *
   * @covers ::getOptions
   */
  public function testGetOptions($uri) {
    $url = Url::fromUri($uri);
    $this->assertInternalType('array', $url->getOptions());
  }

}
