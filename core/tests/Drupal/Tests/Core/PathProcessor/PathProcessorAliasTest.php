<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\PathProcessor\PathProcessorAliasTest.
 */

namespace Drupal\Tests\Core\PathProcessor;

use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the path alias path processor.
 *
 * @group Drupal
 *
 * @see \Drupal\Core\PathProcessor\PathProcessorAlias
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

  public static function getInfo() {
    return array(
      'name' => t('Path Processor alias'),
      'description' => t('Tests the path alias path processor.'),
      'group' => t('Path API'),
    );
  }

  protected function setUp() {
    $this->aliasManager = $this->getMock('Drupal\Core\Path\AliasManagerInterface');
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
      ->will($this->returnValueMap(array(
        array('urlalias', NULL, 'internal-url'),
        array('url', NULL, 'url'),
      )));

    $request = Request::create('/urlalias');
    $this->assertEquals('internal-url', $this->pathProcessor->processInbound('urlalias', $request));
    $request = Request::create('/url');
    $this->assertEquals('url', $this->pathProcessor->processInbound('url', $request));
  }

  /**
   * Tests the processOutbound method.
   *
   * @see \Drupal\Core\PathProcessor\PathProcessorAlias::processOutbound
   */
  public function testProcessOutbound() {
    $this->aliasManager->expects($this->exactly(2))
      ->method('getAliasByPath')
      ->will($this->returnValueMap(array(
        array('internal-url', NULL, 'urlalias'),
        array('url', NULL, 'url'),
      )));

    $this->assertEquals('urlalias', $this->pathProcessor->processOutbound('internal-url'));
    $options = array('alias' => TRUE);
    $this->assertEquals('internal-url', $this->pathProcessor->processOutbound('internal-url', $options));

    $this->assertEquals('url', $this->pathProcessor->processOutbound('url'));
  }

}
