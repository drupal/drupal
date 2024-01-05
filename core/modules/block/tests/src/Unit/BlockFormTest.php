<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Unit;

use Drupal\block\BlockForm;
use Drupal\block\BlockRepository;
use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\block\BlockForm
 * @group block
 */
class BlockFormTest extends UnitTestCase {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $conditionManager;

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $language;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $themeHandler;

  /**
   * The theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $themeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $contextHandler;

  /**
   * The mocked context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $contextRepository;

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $pluginFormFactory;

  /**
   * The block repository.
   *
   * @var \Drupal\block\BlockRepositoryInterface
   */
  protected $blockRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->conditionManager = $this->createMock('Drupal\Core\Executable\ExecutableManagerInterface');
    $this->language = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->contextRepository = $this->createMock('Drupal\Core\Plugin\Context\ContextRepositoryInterface');

    $this->entityTypeManager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->storage = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $this->themeHandler = $this->createMock('Drupal\Core\Extension\ThemeHandlerInterface');
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($this->storage);

    $this->pluginFormFactory = $this->prophesize(PluginFormFactoryInterface::class);

    $this->themeManager = $this->createMock('\Drupal\Core\Theme\ThemeManagerInterface');
    $this->contextHandler = $this->createMock('Drupal\Core\Plugin\Context\ContextHandlerInterface');
    $this->blockRepository = new BlockRepository($this->entityTypeManager, $this->themeManager, $this->contextHandler);
  }

  /**
   * Mocks a block with a block plugin.
   *
   * @param string $machine_name
   *   The machine name of the block plugin.
   *
   * @return \Drupal\block\BlockInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked block.
   */
  protected function getBlockMockWithMachineName($machine_name) {
    $plugin = $this->getMockBuilder(BlockBase::class)
      ->disableOriginalConstructor()
      ->getMock();
    $plugin->expects($this->any())
      ->method('getMachineNameSuggestion')
      ->willReturn($machine_name);

    $block = $this->getMockBuilder(Block::class)
      ->disableOriginalConstructor()
      ->getMock();
    $block->expects($this->any())
      ->method('getPlugin')
      ->willReturn($plugin);
    return $block;
  }

  /**
   * Tests the unique machine name generator.
   *
   * @see \Drupal\block\BlockForm::getUniqueMachineName()
   */
  public function testGetUniqueMachineName() {
    $blocks = [];

    $blocks['test'] = $this->getBlockMockWithMachineName('test');
    $blocks['other_test'] = $this->getBlockMockWithMachineName('other_test');
    $blocks['other_test_1'] = $this->getBlockMockWithMachineName('other_test');
    $blocks['other_test_2'] = $this->getBlockMockWithMachineName('other_test');

    $query = $this->createMock('Drupal\Core\Entity\Query\QueryInterface');
    $query->expects($this->exactly(5))
      ->method('condition')
      ->willReturn($query);

    $query->expects($this->exactly(5))
      ->method('execute')
      ->willReturn(['test', 'other_test', 'other_test_1', 'other_test_2']);

    $this->storage->expects($this->exactly(5))
      ->method('getQuery')
      ->willReturn($query);

    $block_form_controller = new BlockForm($this->entityTypeManager, $this->conditionManager, $this->contextRepository, $this->language, $this->themeHandler, $this->pluginFormFactory->reveal(), $this->blockRepository);

    // Ensure that the block with just one other instance gets
    // the next available name suggestion.
    $this->assertEquals('test_2', $block_form_controller->getUniqueMachineName($blocks['test']));

    // Ensure that the block with already three instances (_0, _1, _2) gets the
    // 4th available name.
    $this->assertEquals('other_test_3', $block_form_controller->getUniqueMachineName($blocks['other_test']));
    $this->assertEquals('other_test_3', $block_form_controller->getUniqueMachineName($blocks['other_test_1']));
    $this->assertEquals('other_test_3', $block_form_controller->getUniqueMachineName($blocks['other_test_2']));

    // Ensure that a block without an instance yet gets the suggestion as
    // unique machine name.
    $last_block = $this->getBlockMockWithMachineName('last_test');
    $this->assertEquals('last_test', $block_form_controller->getUniqueMachineName($last_block));
  }

}
