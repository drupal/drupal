<?php

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\ContextualLinkDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group Menu
 * @coversDefaultClass \Drupal\Core\Menu\ContextualLinkDefault
 */
class ContextualLinkDefaultTest extends UnitTestCase {

  /**
   * The tested contextual link default plugin.
   *
   * @var \Drupal\Core\Menu\ContextualLinkDefault
   */
  protected $contextualLinkDefault;

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
  protected $pluginId = 'contextual_link_default';

  /**
   * The used plugin definition.
   *
   * @var array
   */
  protected $pluginDefinition = array(
    'id' => 'contextual_link_default',
  );

  /**
   * The mocked translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $stringTranslation;

  protected function setUp() {
    parent::setUp();

    $this->stringTranslation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');
  }

  protected function setupContextualLinkDefault() {
    $this->contextualLinkDefault = new ContextualLinkDefault($this->config, $this->pluginId, $this->pluginDefinition);
  }

  /**
   * @covers ::getTitle
   */
  public function testGetTitle() {
    $title = 'Example';
    $this->pluginDefinition['title'] = (new TranslatableMarkup($title, [], [], $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->will($this->returnValue('Example translated'));

    $this->setupContextualLinkDefault();
    $this->assertEquals('Example translated', $this->contextualLinkDefault->getTitle());
  }

  /**
   * @covers ::getTitle
   */
  public function testGetTitleWithContext() {
    $title = 'Example';
    $this->pluginDefinition['title'] = (new TranslatableMarkup($title, array(), array('context' => 'context'), $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->will($this->returnValue('Example translated with context'));

    $this->setupContextualLinkDefault();
    $this->assertEquals('Example translated with context', $this->contextualLinkDefault->getTitle());
  }

  /**
   * @covers ::getTitle
   */
  public function testGetTitleWithTitleArguments() {
    $title = 'Example @test';
    $this->pluginDefinition['title'] = (new TranslatableMarkup($title, array('@test' => 'value'), [], $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->will($this->returnValue('Example value'));

    $this->setupContextualLinkDefault();
    $request = new Request();
    $this->assertEquals('Example value', $this->contextualLinkDefault->getTitle($request));
  }

  /**
   * @covers ::getRouteName
   */
  public function testGetRouteName($route_name = 'test_route_name') {
    $this->pluginDefinition['route_name'] = $route_name;
    $this->setupContextualLinkDefault();

    $this->assertEquals($route_name, $this->contextualLinkDefault->getRouteName());
  }

  /**
   * @covers ::getGroup
   */
  public function testGetGroup($group_name = 'test_group') {
    $this->pluginDefinition['group'] = $group_name;
    $this->setupContextualLinkDefault();

    $this->assertEquals($group_name, $this->contextualLinkDefault->getGroup());
  }

  /**
   * @covers ::getOptions
   */
  public function testGetOptions($options = array('key' => 'value')) {
    $this->pluginDefinition['options'] = $options;
    $this->setupContextualLinkDefault();

    $this->assertEquals($options, $this->contextualLinkDefault->getOptions());
  }

  /**
   * @covers ::getWeight
   */
  public function testGetWeight($weight = 5) {
    $this->pluginDefinition['weight'] = $weight;
    $this->setupContextualLinkDefault();

    $this->assertEquals($weight, $this->contextualLinkDefault->getWeight());
  }

}
