<?php

declare(strict_types=1);

namespace Drupal\Tests\path_alias\Unit\PathProcessor;

use Drupal\Core\Cache\Cache;
use Drupal\path_alias\PathProcessor\AliasPathProcessor;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\path_alias\PathProcessor\AliasPathProcessor
 * @group PathProcessor
 * @group path_alias
 */
class AliasPathProcessorTest extends UnitTestCase {

  /**
   * The mocked alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $aliasManager;

  /**
   * The tested path processor.
   *
   * @var \Drupal\path_alias\PathProcessor\AliasPathProcessor
   */
  protected $pathProcessor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->aliasManager = $this->createMock('Drupal\path_alias\AliasManagerInterface');
    $this->pathProcessor = new AliasPathProcessor($this->aliasManager);
  }

  /**
   * Tests the processInbound method.
   *
   * @see \Drupal\path_alias\PathProcessor\AliasPathProcessor::processInbound
   */
  public function testProcessInbound(): void {
    $this->aliasManager->expects($this->exactly(2))
      ->method('getPathByAlias')
      ->willReturnMap([
        ['url-alias', NULL, 'internal-url'],
        ['url', NULL, 'url'],
      ]);

    $request = Request::create('/url-alias');
    $this->assertEquals('internal-url', $this->pathProcessor->processInbound('url-alias', $request));
    $request = Request::create('/url');
    $this->assertEquals('url', $this->pathProcessor->processInbound('url', $request));
  }

  /**
   * @covers ::processOutbound
   *
   * @dataProvider providerTestProcessOutbound
   */
  public function testProcessOutbound($path, array $options, $expected_path): void {
    $this->aliasManager->expects($this->any())
      ->method('getAliasByPath')
      ->willReturnMap([
        ['internal-url', NULL, 'url-alias'],
        ['url', NULL, 'url'],
      ]);

    $bubbleable_metadata = new BubbleableMetadata();
    $this->assertEquals($expected_path, $this->pathProcessor->processOutbound($path, $options, NULL, $bubbleable_metadata));
    // Cacheability of paths replaced with path aliases is permanent.
    // @todo https://www.drupal.org/node/2480077
    $this->assertEquals((new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT), $bubbleable_metadata);
  }

  /**
   * Provides data for testing outbound processing.
   *
   * @return array
   *   The data provider for testProcessOutbound.
   */
  public static function providerTestProcessOutbound() {
    return [
      ['internal-url', [], 'url-alias'],
      ['internal-url', ['alias' => TRUE], 'internal-url'],
      ['url', [], 'url'],
    ];
  }

}
