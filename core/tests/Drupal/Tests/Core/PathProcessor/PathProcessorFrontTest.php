<?php

namespace Drupal\Tests\Core\PathProcessor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\PathProcessor\PathProcessorFront;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Test front page path processing.
 *
 * @group PathProcessor
 * @coversDefaultClass \Drupal\Core\PathProcessor\PathProcessorFront
 */
class PathProcessorFrontTest extends UnitTestCase {

  /**
   * Test basic inbound processing functionality.
   *
   * @covers ::processInbound
   * @dataProvider providerProcessInbound
   */
  public function testProcessInbound($path, $expected) {
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config_factory->get('system.site')
      ->willReturn($config->reveal());
    $config->get('page.front')
      ->willReturn('/node');
    $processor = new PathProcessorFront($config_factory->reveal());
    $this->assertEquals($expected, $processor->processInbound($path, new Request()));
  }

  /**
   * Inbound paths and expected results.
   */
  public function providerProcessInbound() {
    return [
      ['/', '/node'],
      ['/user', '/user'],
    ];
  }

  /**
   * Test inbound failure with broken config.
   *
   * @covers ::processInbound
   */
  public function testProcessInboundBadConfig() {
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config_factory->get('system.site')
      ->willReturn($config->reveal());
    $config->get('page.front')
      ->willReturn('');
    $processor = new PathProcessorFront($config_factory->reveal());
    $this->setExpectedException(NotFoundHttpException::class);
    $processor->processInbound('/', new Request());
  }

  /**
   * Test basic outbound processing functionality.
   *
   * @covers ::processOutbound
   * @dataProvider providerProcessOutbound
   */
  public function testProcessOutbound($path, $expected) {
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $processor = new PathProcessorFront($config_factory->reveal());
    $this->assertEquals($expected, $processor->processOutbound($path));
  }

  /**
   * Outbound paths and expected results.
   */
  public function providerProcessOutbound() {
    return [
      ['/<front>', '/'],
      ['<front>', '<front>'],
      ['/user', '/user'],
    ];
  }

}
