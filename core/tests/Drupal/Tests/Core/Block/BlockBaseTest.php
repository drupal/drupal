<?php

namespace Drupal\Tests\Core\Block;

use Drupal\block_test\Plugin\Block\TestBlockInstantiation;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Block\BlockBase
 * @group block
 */
class BlockBaseTest extends UnitTestCase {

  /**
   * Tests the machine name suggestion.
   *
   * @see \Drupal\Core\Block\BlockBase::getMachineNameSuggestion()
   *
   * @param string $label
   *   The block label.
   * @param string $expected
   *   The expected machine name.
   *
   * @dataProvider providerTestGetMachineNameSuggestion()
   */
  public function testGetMachineNameSuggestion($label, $expected) {
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
  public function providerTestGetMachineNameSuggestion() {
    return [
      ['Admin label', 'adminlabel'],
      ['über åwesome', 'uberawesome'],
    ];
  }

}
