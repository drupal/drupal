<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Unit;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\navigation\TopBarItemManager;
use Drupal\navigation\TopBarItemManagerInterface;
use Drupal\navigation\TopBarRegion;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\navigation\TopBarItemManager
 *
 * @group navigation
 */
class TopBarItemManagerTest extends UnitTestCase {

  use StringTranslationTrait;

  /**
   * The top bar item manager under test.
   *
   * @var \Drupal\navigation\TopBarItemManagerInterface
   */
  protected TopBarItemManagerInterface $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $cache_backend = $this->prophesize(CacheBackendInterface::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $this->manager = new TopBarItemManager(new \ArrayObject(), $cache_backend->reveal(), $module_handler->reveal());

    $discovery = $this->prophesize(DiscoveryInterface::class);
    // Specify the 'broken' block, as well as 3 other blocks with admin labels
    // that are purposefully not in alphabetical order.
    $discovery->getDefinitions()->willReturn([
      'tools' => [
        'label' => 'Tools',
        'region' => TopBarRegion::Tools,
      ],
      'context' => [
        'admin_label' => 'Context',
        'region' => TopBarRegion::Context,
      ],
      'actions' => [
        'label' => 'Actions',
        'region' => TopBarRegion::Actions,
      ],
      'more_actions' => [
        'label' => 'More Actions',
        'region' => TopBarRegion::Actions,
      ],
    ]);
    // Force the discovery object onto the block manager.
    $property = new \ReflectionProperty(TopBarItemManager::class, 'discovery');
    $property->setValue($this->manager, $discovery->reveal());
  }

  /**
   * @covers ::getDefinitions
   */
  public function testDefinitions(): void {
    $definitions = $this->manager->getDefinitions();
    $this->assertSame(['tools', 'context', 'actions', 'more_actions'], array_keys($definitions));
  }

  /**
   * @covers ::getDefinitionsByRegion
   */
  public function testGetDefinitionsByRegion(): void {
    $tools = $this->manager->getDefinitionsByRegion(TopBarRegion::Tools);
    $this->assertSame(['tools'], array_keys($tools));
    $context = $this->manager->getDefinitionsByRegion(TopBarRegion::Context);
    $this->assertSame(['context'], array_keys($context));
    $actions = $this->manager->getDefinitionsByRegion(TopBarRegion::Actions);
    $this->assertSame(['actions', 'more_actions'], array_keys($actions));
  }

}
