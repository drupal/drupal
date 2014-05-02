<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Menu\ContextualLinkDefaultTest.
 */

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\ContextualLinkDefault;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the contextual link default class.
 *
 * @group Drupal
 * @group Menu
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

  public static function getInfo() {
    return array(
      'name' => 'Contextual links default.',
      'description' => 'Tests the contextual link default class.',
      'group' => 'Menu',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->stringTranslation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');
  }

  protected function setupContextualLinkDefault() {
    $this->contextualLinkDefault = new ContextualLinkDefault($this->config, $this->pluginId, $this->pluginDefinition);
    $this->contextualLinkDefault->setStringTranslation($this->stringTranslation);
  }

  /**
   * Tests the getTitle method without a translation context.
   *
   * @see \Drupal\Core\Menu\LocalTaskDefault::getTitle()
   */
  public function testGetTitle($title = 'Example') {
    $this->pluginDefinition['title'] = $title;
    $this->stringTranslation->expects($this->once())
      ->method('translate')
      ->with($this->pluginDefinition['title'], array(), array())
      ->will($this->returnValue('Example translated'));

    $this->setupContextualLinkDefault();
    $this->assertEquals('Example translated', $this->contextualLinkDefault->getTitle());
  }

  /**
   * Tests the getTitle method with a translation context.
   *
   * @see \Drupal\Core\Menu\LocalTaskDefault::getTitle()
   */
  public function testGetTitleWithContext() {
    $this->pluginDefinition['title'] = 'Example';
    $this->pluginDefinition['title_context'] = 'context';
    $this->stringTranslation->expects($this->once())
      ->method('translate')
      ->with($this->pluginDefinition['title'], array(), array('context' => $this->pluginDefinition['title_context']))
      ->will($this->returnValue('Example translated with context'));

    $this->setupContextualLinkDefault();
    $this->assertEquals('Example translated with context', $this->contextualLinkDefault->getTitle());
  }

  /**
   * Tests the getTitle method with title arguments.
   */
  public function testGetTitleWithTitleArguments() {
    $this->pluginDefinition['title'] = 'Example @test';
    $this->pluginDefinition['title_arguments'] = array('@test' => 'value');
    $this->stringTranslation->expects($this->once())
      ->method('translate')
      ->with($this->pluginDefinition['title'], $this->arrayHasKey('@test'), array())
      ->will($this->returnValue('Example value'));

    $this->setupContextualLinkDefault();
    $request = new Request();
    $this->assertEquals('Example value', $this->contextualLinkDefault->getTitle($request));
  }

  /**
   * Tests the getRouteName() method.
   *
   * @covers \Drupal\Core\Menu\ContextualLinkDefault::getRouteName()
   */
  public function testGetRouteName($route_name = 'test_route_name') {
    $this->pluginDefinition['route_name'] = $route_name;
    $this->setupContextualLinkDefault();

    $this->assertEquals($route_name, $this->contextualLinkDefault->getRouteName());
  }

  /**
   * Tests the getGroup() method.
   *
   * @covers \Drupal\Core\Menu\ContextualLinkDefault::getGroup()
   */
  public function testGetGroup($group_name = 'test_group') {
    $this->pluginDefinition['group'] = $group_name;
    $this->setupContextualLinkDefault();

    $this->assertEquals($group_name, $this->contextualLinkDefault->getGroup());
  }

  /**
   * Tests the getOptions() method.
   *
   * @covers \Drupal\Core\Menu\ContextualLinkDefault::getOptions()
   */
  public function testGetOptions($options = array('key' => 'value')) {
    $this->pluginDefinition['options'] = $options;
    $this->setupContextualLinkDefault();

    $this->assertEquals($options, $this->contextualLinkDefault->getOptions());
  }

  /**
   * Tests the getWeight() method.
   *
   * @covers \Drupal\Core\Menu\ContextualLinkDefault::getWeight()
   */
  public function testGetWeight($weight = 5) {
    $this->pluginDefinition['weight'] = $weight;
    $this->setupContextualLinkDefault();

    $this->assertEquals($weight, $this->contextualLinkDefault->getWeight());
  }

}
