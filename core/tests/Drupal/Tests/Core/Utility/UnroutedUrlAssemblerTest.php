<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Utility\UnroutedUrlAssemblerTest.
 */

namespace Drupal\Tests\Core\Utility;

use Drupal\Core\Utility\UnroutedUrlAssembler;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Utility\UnroutedUrlAssembler
 * @group Utility
 */
class UnroutedUrlAssemblerTest extends UnitTestCase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The tested unrouted URL assembler.
   *
   * @var \Drupal\Core\Utility\UnroutedUrlAssembler
   */
  protected $unroutedUrlAssembler;

  /**
   * The mocked outbound path processor.
   *
   * @var \Drupal\Core\PathProcessor\OutboundPathProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pathProcessor;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->requestStack = new RequestStack();
    $this->configFactory = $this->getConfigFactoryStub(['system.filter' => []]);
    $this->pathProcessor = $this->getMock('Drupal\Core\PathProcessor\OutboundPathProcessorInterface');
    $this->unroutedUrlAssembler = new UnroutedUrlAssembler($this->requestStack, $this->configFactory, $this->pathProcessor);
  }

  /**
   * @covers ::assemble
   * @expectedException \InvalidArgumentException
   */
  public function testAssembleWithNeitherExternalNorDomainLocalUri() {
    $this->unroutedUrlAssembler->assemble('wrong-url');
  }

  /**
   * @covers ::assemble
   * @covers ::buildExternalUrl
   *
   * @dataProvider providerTestAssembleWithExternalUrl
   */
  public function testAssembleWithExternalUrl($uri, array $options, $expected) {
   $this->setupRequestStack(FALSE);
   $this->assertEquals($expected, $this->unroutedUrlAssembler->assemble($uri, $options));
  }

  /**
   * Provides test data for testAssembleWithExternalUrl
   */
  public function providerTestAssembleWithExternalUrl() {
    return [
      ['http://example.com/test', [], 'http://example.com/test'],
      ['http://example.com/test', ['fragment' => 'example'], 'http://example.com/test#example'],
      ['http://example.com/test', ['fragment' => 'example'], 'http://example.com/test#example'],
      ['http://example.com/test', ['query' => ['foo' => 'bar']], 'http://example.com/test?foo=bar'],
      ['http://example.com/test', ['https' => TRUE], 'https://example.com/test'],
      ['https://example.com/test', ['https' => FALSE], 'http://example.com/test'],
      ['https://example.com/test?foo=1#bar', [], 'https://example.com/test?foo=1#bar'],
    ];
  }

  /**
   * @covers ::assemble
   * @covers::buildLocalUrl
   *
   * @dataProvider providerTestAssembleWithLocalUri
   */
  public function testAssembleWithLocalUri($uri, array $options, $subdir, $expected) {
    $this->setupRequestStack($subdir);

    $this->assertEquals($expected, $this->unroutedUrlAssembler->assemble($uri, $options));
  }

  /**
   * @return array
   */
  public function providerTestAssembleWithLocalUri() {
    return [
      ['base://example', [], FALSE, '/example'],
      ['base://example', ['query' => ['foo' => 'bar']], FALSE, '/example?foo=bar'],
      ['base://example', ['fragment' => 'example', ], FALSE, '/example#example'],
      ['base://example', [], TRUE, '/subdir/example'],
      ['base://example', ['query' => ['foo' => 'bar']], TRUE, '/subdir/example?foo=bar'],
      ['base://example', ['fragment' => 'example', ], TRUE, '/subdir/example#example'],
    ];
  }

  /**
   * @covers ::assemble
   */
  public function testAssembleWithNotEnabledProcessing() {
    $this->setupRequestStack(FALSE);
    $this->pathProcessor->expects($this->never())
      ->method('processOutbound');
    $result = $this->unroutedUrlAssembler->assemble('base://test-uri', []);
    $this->assertEquals('/test-uri', $result);
  }

  /**
   * @covers ::assemble
   */
  public function testAssembleWithEnabledProcessing() {
    $this->setupRequestStack(FALSE);
    $this->pathProcessor->expects($this->once())
      ->method('processOutbound')
      ->with('test-uri', ['path_processing' => TRUE, 'fragment' => NULL, 'query' => [], 'absolute' => NULL, 'prefix' => NULL, 'script' => NULL])
      ->willReturn('test-other-uri');
    $result = $this->unroutedUrlAssembler->assemble('base://test-uri', ['path_processing' => TRUE]);
    $this->assertEquals('/test-other-uri', $result);
  }

  /**
   * Setups the request stack for a given subdir.
   *
   * @param string $subdir
   *   The wanted subdir.
   */
  protected function setupRequestStack($subdir) {
    $server = [];
    if ($subdir) {
      // Setup a fake request which looks like a Drupal installed under the
      // subdir "subdir" on the domain www.example.com.
      // To reproduce the values install Drupal like that and use a debugger.
      $server = [
        'SCRIPT_NAME' => '/subdir/index.php',
        'SCRIPT_FILENAME' => $this->root . '/index.php',
        'SERVER_NAME' => 'http://www.example.com',
      ];
      $request = Request::create('/subdir');
    }
    else {
      $request = Request::create('/');
    }
    $request->server->add($server);
    $this->requestStack->push($request);
  }

}
