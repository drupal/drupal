<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Menu\LocalActionDefaultTest.
 */

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\Menu\LocalActionDefault
 * @group Menu
 */
class LocalActionDefaultTest extends UnitTestCase {

  /**
   * The tested local action default plugin.
   *
   * @var \Drupal\Core\Menu\LocalActionDefault
   */
  protected $localActionDefault;

  /**
   * The used plugin configuration.
   *
   * @var array
   */
  protected $config = array();

  /**
   * The used plugin ID.
   *
   * @var string
   */
  protected $pluginId = 'local_action_default';

  /**
   * The used plugin definition.
   *
   * @var array
   */
  protected $pluginDefinition = array(
    'id' => 'local_action_default',
  );

  /**
   * The mocked translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $stringTranslation;

  /**
   * The mocked route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProvider;

  protected function setUp() {
    parent::setUp();

    $this->stringTranslation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');
    $this->routeProvider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
  }

  /**
   * Setups the local action default.
   */
  protected function setupLocalActionDefault() {
    $this->localActionDefault = new LocalActionDefault($this->config, $this->pluginId, $this->pluginDefinition, $this->routeProvider);
  }

  /**
   * Tests the getTitle method without a translation context.
   *
   * @see \Drupal\Core\Menu\LocalTaskDefault::getTitle()
   */
  public function testGetTitle() {
    $this->pluginDefinition['title'] = (new TranslatableMarkup('Example', [], [], $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->will($this->returnValue('Example translated'));

    $this->setupLocalActionDefault();
    $this->assertEquals('Example translated', $this->localActionDefault->getTitle());
  }

  /**
   * Tests the getTitle method with a translation context.
   *
   * @see \Drupal\Core\Menu\LocalTaskDefault::getTitle()
   */
  public function testGetTitleWithContext() {
    $this->pluginDefinition['title'] = (new TranslatableMarkup('Example', array(), array('context' => 'context'), $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->will($this->returnValue('Example translated with context'));

    $this->setupLocalActionDefault();
    $this->assertEquals('Example translated with context', $this->localActionDefault->getTitle());
  }

  /**
   * Tests the getTitle method with title arguments.
   */
  public function testGetTitleWithTitleArguments() {
    $this->pluginDefinition['title'] = (new TranslatableMarkup('Example @test', array('@test' => 'value'), [], $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->will($this->returnValue('Example value'));

    $this->setupLocalActionDefault();
    $request = new Request();
    $this->assertEquals('Example value', $this->localActionDefault->getTitle($request));
  }

}
