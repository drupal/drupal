<?php

namespace Drupal\Tests\Core\Block;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Block\BlockPluginTrait;
use Drupal\Core\Block\Plugin\Block\Broken;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\Core\Block\BlockManager
 *
 * @group block
 */
class BlockManagerTest extends UnitTestCase {

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
    \Drupal::setContainer($container);

    $cache_backend = $this->prophesize(CacheBackendInterface::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
    $this->blockManager = new BlockManager(new \ArrayObject(), $cache_backend->reveal(), $module_handler->reveal(), $this->logger->reveal());
    $this->blockManager->setStringTranslation($this->getStringTranslationStub());

    // Specify the 'broken' block, as well as 3 other blocks with admin labels
    // that are purposefully not in alphabetical order.
    $this->setDefinitions([
      'broken' => [
        'admin_label' => 'Broken/Missing',
        'category' => 'Block',
        'class' => Broken::class,
        'provider' => 'core',
      ],
      'block1' => [
        'admin_label' => 'Coconut',
        'category' => 'Group 2',
        'class' => TestBlockManagerBlock::class,
      ],
      'block2' => [
        'admin_label' => 'Apple',
        'category' => 'Group 1',
        'class' => TestBlockManagerBlock::class,
      ],
      'block3' => [
        'admin_label' => 'Banana',
        'category' => 'Group 2',
        'class' => TestBlockManagerBlock::class,
      ],
    ]);
  }

  /**
   * Sets the definitions the block manager will return.
   *
   * @param array $definitions
   *   An array of plugin definitions.
   */
  protected function setDefinitions(array $definitions) {
    $discovery = $this->prophesize(DiscoveryInterface::class);
    $discovery->getDefinitions()->willReturn($definitions);
    // Force the discovery object onto the block manager.
    $property = new \ReflectionProperty(BlockManager::class, 'discovery');
    $property->setAccessible(TRUE);
    $property->setValue($this->blockManager, $discovery->reveal());
  }

  /**
   * @covers ::getDefinitions
   */
  public function testDefinitions() {
    $definitions = $this->blockManager->getDefinitions();
    $this->assertSame(['broken', 'block1', 'block2', 'block3'], array_keys($definitions));
  }

  /**
   * @covers ::getSortedDefinitions
   */
  public function testSortedDefinitions() {
    $definitions = $this->blockManager->getSortedDefinitions();
    $this->assertSame(['block2', 'block3', 'block1'], array_keys($definitions));
  }

  /**
   * @covers ::getGroupedDefinitions
   */
  public function testGroupedDefinitions() {
    $definitions = $this->blockManager->getGroupedDefinitions();
    $this->assertSame(['Group 1', 'Group 2'], array_keys($definitions));
    $this->assertSame(['block2'], array_keys($definitions['Group 1']));
    $this->assertSame(['block3', 'block1'], array_keys($definitions['Group 2']));
  }

  /**
   * @covers ::handlePluginNotFound
   */
  public function testHandlePluginNotFound() {
    $this->logger->warning('The "%plugin_id" was not found', ['%plugin_id' => 'invalid'])->shouldBeCalled();
    $plugin = $this->blockManager->createInstance('invalid');
    $this->assertSame('broken', $plugin->getPluginId());
  }
  /**
   * @group legacy
   * @expectedDeprecation Declaring ::build() without an array return typehint in Drupal\Tests\Core\Block\TestBlockManagerNoArrayReturnTypeBlock is deprecated in drupal:9.2.0. Typehinting will be required before drupal:10.0.0. See https://www.drupal.org/node/3164649.
   */
  public function testBuildNoReturnType() {
    // Overwrite the definitions from ::setUp() to have a block that does not
    // have a return type for ::build().
    $this->setDefinitions([
      'block1' => [
        'provider' => 'test',
        'class' => TestBlockManagerNoArrayReturnTypeBlock::class,
      ],
    ]);
    $expected = [];
    $definitions = $this->blockManager->getDefinitions();
    $this->assertSame($expected, $definitions);
  }

  /**
   * @group legacy
   * @expectedDeprecation Extending Drupal\Tests\Core\Block\TestBlockManagerExtendsExistingBlock from a concrete class is deprecated in drupal:9.2.0 and will be disallowed before drupal:10.0.0. Extend the class from an abstract base class instead. See https://www.drupal.org/node/xxxxxxxx.
   */
  public function testExtendsExistingBlock() {
    // Overwrite the definitions from ::setUp() to have a block that extends
    // an existing block.
    $this->setDefinitions([
      'block1' => [
        'provider' => 'test',
        'class' => TestBlockManagerExtendsExistingBlock::class,
      ],
    ]);
    $expected = [];
    $definitions = $this->blockManager->getDefinitions();
    $this->assertSame($expected, $definitions);
  }

}

class TestBlockManagerBlock extends PluginBase implements BlockPluginInterface {

  use BlockPluginTrait;
  use CacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [];
  }

}

class TestBlockManagerNoArrayReturnTypeBlock extends PluginBase implements BlockPluginInterface {

  use BlockPluginTrait;
  use CacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
  }

}

class TestBlockManagerExtendsExistingBlock extends TestBlockManagerBlock {

}
