<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Block;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Block\Plugin\Block\Broken;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * @coversDefaultClass \Drupal\Core\Block\BlockManager
 *
 * @group block
 */
class BlockManagerTest extends UnitTestCase {

  use StringTranslationTrait;

  /**
   * The block manager under test.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $current_user = $this->prophesize(AccountInterface::class);
    $container->set('current_user', $current_user->reveal());
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $cache_backend = $this->prophesize(CacheBackendInterface::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
    $this->blockManager = new BlockManager(new \ArrayObject(), $cache_backend->reveal(), $module_handler->reveal(), $this->logger->reveal());
    $this->blockManager->setStringTranslation($this->getStringTranslationStub());

    $discovery = $this->prophesize(DiscoveryInterface::class);
    // Specify the 'broken' block, as well as 3 other blocks with admin labels
    // that are purposefully not in alphabetical order.
    $discovery->getDefinitions()->willReturn([
      'broken' => [
        'admin_label' => $this->t('Broken/Missing'),
        'category' => $this->t('Block'),
        'class' => Broken::class,
        'provider' => 'core',
      ],
      'block1' => [
        'admin_label' => $this->t('Coconut'),
        'category' => $this->t('Group 2'),
      ],
      'block2' => [
        'admin_label' => $this->t('Apple'),
        'category' => $this->t('Group 1'),
      ],
      'block3' => [
        'admin_label' => $this->t('Banana'),
        'category' => $this->t('Group 2'),
      ],
    ]);
    // Force the discovery object onto the block manager.
    $property = new \ReflectionProperty(BlockManager::class, 'discovery');
    $property->setValue($this->blockManager, $discovery->reveal());
  }

  /**
   * @covers ::getDefinitions
   */
  public function testDefinitions(): void {
    $definitions = $this->blockManager->getDefinitions();
    $this->assertSame(['broken', 'block1', 'block2', 'block3'], array_keys($definitions));
  }

  /**
   * @covers ::getSortedDefinitions
   */
  public function testSortedDefinitions(): void {
    $definitions = $this->blockManager->getSortedDefinitions();
    $this->assertSame(['block2', 'block3', 'block1'], array_keys($definitions));
  }

  /**
   * @covers ::getGroupedDefinitions
   */
  public function testGroupedDefinitions(): void {
    $definitions = $this->blockManager->getGroupedDefinitions();
    $this->assertSame(['Group 1', 'Group 2'], array_keys($definitions));
    $this->assertSame(['block2'], array_keys($definitions['Group 1']));
    $this->assertSame(['block3', 'block1'], array_keys($definitions['Group 2']));
  }

  /**
   * @covers ::handlePluginNotFound
   */
  public function testHandlePluginNotFound(): void {
    $this->logger->warning('The "%plugin_id" block plugin was not found', ['%plugin_id' => 'invalid'])->shouldBeCalled();
    $plugin = $this->blockManager->createInstance('invalid');
    $this->assertSame('broken', $plugin->getPluginId());
  }

}
