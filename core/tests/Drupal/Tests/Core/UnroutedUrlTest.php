<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\UnroutedUrlTest.
 */

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
  protected $unroutedExternal = 'http://drupal.org';

  /**
   * An unrouted, internal URL to test.
   *
   * @var string
   */
  protected $unroutedInternal = 'base://robots.txt';

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
   */
  public function testFromUri() {
    $urls = [
      Url::fromUri($this->unroutedExternal),
      Url::fromUri($this->unroutedInternal)
    ];

    $this->assertInstanceOf('Drupal\Core\Url', $urls[0]);
    $this->assertTrue($urls[0]->isExternal());

    $this->assertInstanceOf('Drupal\Core\Url', $urls[1]);
    $this->assertFalse($urls[1]->isExternal());

    return $urls;
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
   * Tests the fromUri() method with an non-scheme path.
   *
   * @covers ::fromUri
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFromUriWithNonScheme() {
    Url::fromUri('test');
  }

  /**
   * Tests the isExternal() method.
   *
   * @depends testFromUri
   *
   * @covers ::isExternal
   */
  public function testIsExternal(array $urls) {
    $this->assertTrue($urls[0]->isExternal());
    $this->assertFalse($urls[1]->isExternal());
  }

  /**
   * Tests the toString() method.
   *
   * @depends testFromUri
   *
   * @covers ::toString
   */
  public function testToString(array $urls) {
    $this->assertSame($this->unroutedExternal, $urls[0]->toString());
    $this->assertSame($this->unroutedInternal, $urls[1]->toString());
  }

  /**
   * Tests the getRouteName() method.
   *
   * @depends testFromUri
   *
   * @expectedException \UnexpectedValueException
   *
   * @covers ::getRouteName
   */
  public function testGetRouteName(array $urls) {
    $urls[0]->getRouteName();
  }

  /**
   * Tests the getRouteParameters() method.
   *
   * @depends testFromUri
   *
   * @expectedException \UnexpectedValueException
   *
   * @covers ::getRouteParameters
   */
  public function testGetRouteParameters(array $urls) {
    $urls[0]->getRouteParameters();
  }

  /**
   * Tests the getInternalPath() method.
   *
   * @depends testFromUri
   *
   * @covers ::getInternalPath
   *
   * @expectedException \Exception
   */
  public function testGetInternalPath(array $urls) {
    $this->assertNull($urls[0]->getInternalPath());
  }

  /**
   * Tests the getPath() method.
   *
   * @depends testFromUri
   *
   * @covers ::getUri
   */
  public function testGetUri(array $urls) {
    $this->assertNotNull($urls[0]->getUri());
    $this->assertNotNull($urls[1]->getUri());
  }

  /**
   * Tests the getOptions() method.
   *
   * @depends testFromUri
   *
   * @covers ::getOptions
   */
  public function testGetOptions(array $urls) {
    $this->assertInternalType('array', $urls[0]->getOptions());
    $this->assertInternalType('array', $urls[1]->getOptions());
  }

}
