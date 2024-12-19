<?php

declare(strict_types=1);

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
  protected $config = [];

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
  protected $pluginDefinition = [
    'id' => 'contextual_link_default',
  ];

  /**
   * The mocked translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->stringTranslation = $this->createMock('Drupal\Core\StringTranslation\TranslationInterface');
  }

  protected function setupContextualLinkDefault(): void {
    $this->contextualLinkDefault = new ContextualLinkDefault($this->config, $this->pluginId, $this->pluginDefinition);
  }

  /**
   * @covers ::getTitle
   */
  public function testGetTitle(): void {
    $title = 'Example';
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    $this->pluginDefinition['title'] = (new TranslatableMarkup($title, [], [], $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->willReturn('Example translated');

    $this->setupContextualLinkDefault();
    $this->assertEquals('Example translated', $this->contextualLinkDefault->getTitle());
  }

  /**
   * @covers ::getTitle
   */
  public function testGetTitleWithContext(): void {
    $title = 'Example';
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    $this->pluginDefinition['title'] = (new TranslatableMarkup($title, [], ['context' => 'context'], $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->willReturn('Example translated with context');

    $this->setupContextualLinkDefault();
    $this->assertEquals('Example translated with context', $this->contextualLinkDefault->getTitle());
  }

  /**
   * @covers ::getTitle
   */
  public function testGetTitleWithTitleArguments(): void {
    $title = 'Example @test';
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    $this->pluginDefinition['title'] = (new TranslatableMarkup($title, ['@test' => 'value'], [], $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->willReturn('Example value');

    $this->setupContextualLinkDefault();
    $request = new Request();
    $this->assertEquals('Example value', $this->contextualLinkDefault->getTitle($request));
  }

  /**
   * @covers ::getRouteName
   */
  public function testGetRouteName($route_name = 'test_route_name'): void {
    $this->pluginDefinition['route_name'] = $route_name;
    $this->setupContextualLinkDefault();

    $this->assertEquals($route_name, $this->contextualLinkDefault->getRouteName());
  }

  /**
   * @covers ::getGroup
   */
  public function testGetGroup($group_name = 'test_group'): void {
    $this->pluginDefinition['group'] = $group_name;
    $this->setupContextualLinkDefault();

    $this->assertEquals($group_name, $this->contextualLinkDefault->getGroup());
  }

  /**
   * @covers ::getOptions
   */
  public function testGetOptions($options = ['key' => 'value']): void {
    $this->pluginDefinition['options'] = $options;
    $this->setupContextualLinkDefault();

    $this->assertEquals($options, $this->contextualLinkDefault->getOptions());
  }

  /**
   * @covers ::getWeight
   */
  public function testGetWeight($weight = 5): void {
    $this->pluginDefinition['weight'] = $weight;
    $this->setupContextualLinkDefault();

    $this->assertEquals($weight, $this->contextualLinkDefault->getWeight());
  }

}
