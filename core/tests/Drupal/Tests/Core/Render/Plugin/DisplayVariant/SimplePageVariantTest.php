<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render\Plugin\DisplayVariant;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Render\Plugin\DisplayVariant\SimplePageVariant;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Render\Plugin\DisplayVariant\SimplePageVariant.
 */
#[CoversClass(SimplePageVariant::class)]
#[Group('Render')]
class SimplePageVariantTest extends UnitTestCase {

  /**
   * Sets up a display variant plugin for testing.
   *
   * @param array $configuration
   *   An array of plugin configuration.
   * @param array $definition
   *   The plugin definition array.
   *
   * @return \Drupal\Core\Render\Plugin\DisplayVariant\SimplePageVariant
   *   A test display variant plugin.
   */
  public function setUpDisplayVariant($configuration = [], $definition = []) {
    $container = new Container();
    $cache_context_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->onlyMethods(['assertValidTokens'])
      ->getMock();
    $container->set('cache_contexts_manager', $cache_context_manager);
    $cache_context_manager->expects($this->any())
      ->method('assertValidTokens')
      ->willReturn(TRUE);
    \Drupal::setContainer($container);

    $plugin = new SimplePageVariant($configuration, 'test', $definition);
    $plugin->setTitle('Test');
    $plugin->setMainContent([
      '#markup' => 'Test content',
    ]);

    return $plugin;
  }

  /**
   * Tests the build method.
   *
   * @legacy-covers ::build
   */
  public function testBuild(): void {
    $title = $this->randomString();
    $content = $this->randomString();

    $display_variant = $this->setUpDisplayVariant();
    $display_variant->setTitle($title);
    $display_variant->setMainContent([
      '#markup' => $content,
    ]);

    $expected = [
      'content' => [
        'messages' => [
          '#type' => 'status_messages',
          '#weight' => -1000,
          '#include_fallback' => TRUE,
        ],
        'page_title' => [
          '#type' => 'page_title',
          '#title' => $title,
          '#weight' => -900,
        ],
        'main_content' => [
          '#weight' => -800,
          '#markup' => $content,
        ],
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
    ];
    $this->assertSame($expected, $display_variant->build());
  }

  /**
   * Tests that cache metadata in the plugin are present in the build.
   *
   * @legacy-covers ::build
   */
  public function testCacheMetadataFromPlugin(): void {
    $display_variant = $this->setUpDisplayVariant();
    $route_match = $this->createMock(RouteMatchInterface::class);

    $event = new PageDisplayVariantSelectionEvent($display_variant->getPluginId(), $route_match);
    $event->addCacheTags(['my_tag']);
    $event->addCacheContexts(['my_context']);
    $event->mergeCacheMaxAge(50);

    $display_variant->addCacheableDependency($event);

    $expectedCache = [
      'contexts' => [
        'my_context',
      ],
      'tags' => [
        'my_tag',
      ],
      'max-age' => 50,
    ];
    $this->assertSame($expectedCache, $display_variant->build()['#cache']);
  }

}
