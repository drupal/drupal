<?php

namespace Drupal\Tests\Core\PathProcessor;

use Drupal\Core\Cache\Cache;
use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\PathProcessor\PathProcessorAlias
 * @group PathProcessor
 */
class PathProcessorAliasTest extends UnitTestCase {

  /**
   * The mocked alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $aliasManager;

  /**
   * The tested path processor.
   *
   * @var \Drupal\Core\PathProcessor\PathProcessorAlias
   */
  protected $pathProcessor;

  protected function setUp() {
    $this->aliasManager = $this->createMock('Drupal\Core\Path\AliasManagerInterface');
    $this->pathProcessor = new PathProcessorAlias($this->aliasManager);
  }

  /**
   * Tests the processInbound method.
   *
   * @see \Drupal\Core\PathProcessor\PathProcessorAlias::processInbound
   */
  public function testProcessInbound() {
    $this->aliasManager->expects($this->exactly(2))
      ->method('getPathByAlias')
      ->will($this->returnValueMap([
        ['urlalias', NULL, 'internal-url'],
        ['url', NULL, 'url'],
      ]));

    $request = Request::create('/urlalias');
    $this->assertEquals('internal-url', $this->pathProcessor->processInbound('urlalias', $request));
    $request = Request::create('/url');
    $this->assertEquals('url', $this->pathProcessor->processInbound('url', $request));
  }

  /**
   * @covers ::processOutbound
   *
   * @dataProvider providerTestProcessOutbound
   */
  public function testProcessOutbound($path, array $options, $expected_path) {
    $this->aliasManager->expects($this->any())
      ->method('getAliasByPath')
      ->will($this->returnValueMap([
        ['internal-url', NULL, 'urlalias'],
        ['url', NULL, 'url'],
      ]));

    $bubbleable_metadata = new BubbleableMetadata();
    $this->assertEquals($expected_path, $this->pathProcessor->processOutbound($path, $options, NULL, $bubbleable_metadata));
    // Cacheability of paths replaced with path aliases is permanent.
    // @todo https://www.drupal.org/node/2480077
    $this->assertEquals((new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT), $bubbleable_metadata);
  }

  /**
   * @return array
   */
  public function providerTestProcessOutbound() {
    return [
      ['internal-url', [], 'urlalias'],
      ['internal-url', ['alias' => TRUE], 'internal-url'],
      ['url', [], 'url'],
    ];
  }

}
