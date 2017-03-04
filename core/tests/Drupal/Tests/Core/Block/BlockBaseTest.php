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
   */
  public function testGetMachineNameSuggestion() {
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $transliteration = $this->getMockBuilder('Drupal\Core\Transliteration\PhpTransliteration')
      ->setConstructorArgs([NULL, $module_handler])
      ->setMethods(['readLanguageOverrides'])
      ->getMock();

    $config = [];
    $definition = [
      'admin_label' => 'Admin label',
      'provider' => 'block_test',
    ];
    $block_base = new TestBlockInstantiation($config, 'test_block_instantiation', $definition);
    $block_base->setTransliteration($transliteration);
    $this->assertEquals('adminlabel', $block_base->getMachineNameSuggestion());

    // Test with more unicodes.
    $definition = [
      'admin_label' => 'über åwesome',
      'provider' => 'block_test',
    ];
    $block_base = new TestBlockInstantiation($config, 'test_block_instantiation', $definition);
    $block_base->setTransliteration($transliteration);
    $this->assertEquals('uberawesome', $block_base->getMachineNameSuggestion());
  }

}
