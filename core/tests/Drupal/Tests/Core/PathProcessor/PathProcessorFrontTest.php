<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\PathProcessor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\PathProcessor\PathProcessorFront;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Test front page path processing.
 */
#[CoversClass(PathProcessorFront::class)]
#[Group('PathProcessor')]
class PathProcessorFrontTest extends UnitTestCase {

  /**
   * Tests basic inbound processing functionality.
   *
   * @legacy-covers ::processInbound
   */
  #[DataProvider('providerProcessInbound')]
  public function testProcessInbound($frontpage_path, $path, $expected, array $expected_query = []): void {
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config_factory->get('system.site')
      ->willReturn($config->reveal());
    $config->get('page.front')
      ->willReturn($frontpage_path);
    $processor = new PathProcessorFront($config_factory->reveal());
    $request = new Request();
    $this->assertEquals($expected, $processor->processInbound($path, $request));
    $this->assertEquals($expected_query, $request->query->all());
  }

  /**
   * Inbound paths and expected results.
   */
  public static function providerProcessInbound() {
    return [
      'accessing frontpage' => ['/node', '/', '/node'],
      'accessing non frontpage' => ['/node', '/user', '/user'],
      'accessing frontpage with query parameters' => ['/node?example=muh',
        '/',
        '/node',
        ['example' => 'muh'],
      ],
    ];
  }

  /**
   * Tests inbound failure with broken config.
   *
   * @legacy-covers ::processInbound
   */
  public function testProcessInboundBadConfig(): void {
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config_factory->get('system.site')
      ->willReturn($config->reveal());
    $config->get('page.front')
      ->willReturn('');
    $processor = new PathProcessorFront($config_factory->reveal());
    $this->expectException(NotFoundHttpException::class);
    $processor->processInbound('/', new Request());
  }

}
