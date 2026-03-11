<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Unit;

use Drupal\block\BlockForm;
use Drupal\block\BlockRepository;
use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\block\BlockForm.
 */
#[CoversClass(BlockForm::class)]
#[Group('block')]
class BlockFormTest extends UnitTestCase {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface|\PHPUnit\Framework\MockObject\Stub
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
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $language;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $themeHandler;

  /**
   * The theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $themeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $entityTypeManager;

  /**
   * The mocked context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $contextHandler;

  /**
   * The mocked context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface|\PHPUnit\Framework\MockObject\Stub
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

    $this->conditionManager = $this->createStub(ExecutableManagerInterface::class);
    $this->language = $this->createStub(LanguageManagerInterface::class);
    $this->contextRepository = $this->createStub(ContextRepositoryInterface::class);

    $this->entityTypeManager = $this->createStub(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $this->themeHandler = $this->createStub(ThemeHandlerInterface::class);
    $this->entityTypeManager
      ->method('getStorage')
      ->willReturn($this->storage);

    $this->pluginFormFactory = $this->prophesize(PluginFormFactoryInterface::class);

    $this->themeManager = $this->createStub(ThemeManagerInterface::class);
    $this->contextHandler = $this->createStub(ContextHandlerInterface::class);
    $this->blockRepository = new BlockRepository($this->entityTypeManager, $this->themeManager, $this->contextHandler);
  }

  /**
   * Mocks a block with a block plugin.
   *
   * @param string $machine_name
   *   The machine name of the block plugin.
   *
   * @return \Drupal\block\BlockInterface|\PHPUnit\Framework\MockObject\Stub
   *   The stub block.
   */
  protected function getBlockMockWithMachineName($machine_name) {
    $plugin = $this->createStub(BlockBase::class);
    $plugin
      ->method('getMachineNameSuggestion')
      ->willReturn($machine_name);

    $block = $this->createStub(Block::class);
    $block
      ->method('getPlugin')
      ->willReturn($plugin);
    return $block;
  }

  /**
   * Tests the unique machine name generator.
   *
   * @see \Drupal\block\BlockForm::getUniqueMachineName()
   */
  public function testGetUniqueMachineName(): void {
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
