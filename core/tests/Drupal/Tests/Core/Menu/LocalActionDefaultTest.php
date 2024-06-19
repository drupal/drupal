<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Menu;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
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
  protected $config = [];

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
  protected $pluginDefinition = [
    'id' => 'local_action_default',
  ];

  /**
   * The mocked translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The mocked route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->stringTranslation = $this->createMock('Drupal\Core\StringTranslation\TranslationInterface');
    $this->routeProvider = $this->createMock('Drupal\Core\Routing\RouteProviderInterface');
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
  public function testGetTitle(): void {
    $this->pluginDefinition['title'] = (new TranslatableMarkup('Example', [], [], $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->willReturn('Example translated');

    $this->setupLocalActionDefault();
    $this->assertEquals('Example translated', $this->localActionDefault->getTitle());
  }

  /**
   * Tests the getTitle method with a translation context.
   *
   * @see \Drupal\Core\Menu\LocalTaskDefault::getTitle()
   */
  public function testGetTitleWithContext(): void {
    $this->pluginDefinition['title'] = (new TranslatableMarkup('Example', [], ['context' => 'context'], $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->willReturn('Example translated with context');

    $this->setupLocalActionDefault();
    $this->assertEquals('Example translated with context', $this->localActionDefault->getTitle());
  }

  /**
   * Tests the getTitle method with title arguments.
   */
  public function testGetTitleWithTitleArguments(): void {
    $this->pluginDefinition['title'] = (new TranslatableMarkup('Example @test', ['@test' => 'value'], [], $this->stringTranslation));
    $this->stringTranslation->expects($this->once())
      ->method('translateString')
      ->with($this->pluginDefinition['title'])
      ->willReturn('Example value');

    $this->setupLocalActionDefault();
    $request = new Request();
    $this->assertEquals('Example value', $this->localActionDefault->getTitle($request));
  }

}
