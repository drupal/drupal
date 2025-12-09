<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Block;

use Drupal\block_test\Plugin\Block\TestBlockInstantiation;
use Drupal\Core\Block\BlockBase;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

// cspell:ignore adminlabel
/**
 * Tests Drupal\Core\Block\BlockBase.
 */
#[CoversClass(BlockBase::class)]
#[Group('block')]
class BlockBaseTest extends UnitTestCase {

  /**
   * Tests the machine name suggestion.
   *
   * @param string $label
   *   The block label.
   * @param string $expected
   *   The expected machine name.
   *
   * @see \Drupal\Core\Block\BlockBase::getMachineNameSuggestion()
   */
  #[DataProvider('providerTestGetMachineNameSuggestion')]
  public function testGetMachineNameSuggestion($label, $expected): void {
    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $transliteration = $this->getMockBuilder('Drupal\Core\Transliteration\PhpTransliteration')
      ->setConstructorArgs([NULL, $module_handler])
      ->onlyMethods(['readLanguageOverrides'])
      ->getMock();

    $config = [];
    $definition = [
      'admin_label' => $label,
      'provider' => 'block_test',
    ];
    $block_base = new TestBlockInstantiation($config, 'test_block_instantiation', $definition);
    $block_base->setTransliteration($transliteration);
    $this->assertEquals($expected, $block_base->getMachineNameSuggestion());
  }

  /**
   * Provides data for testGetMachineNameSuggestion().
   */
  public static function providerTestGetMachineNameSuggestion(): array {
    return [
      ['Admin label', 'adminlabel'],
      // cspell:disable-next-line
      ['über åwesome', 'uberawesome'],
    ];
  }

}
