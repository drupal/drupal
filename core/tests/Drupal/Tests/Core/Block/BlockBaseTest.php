<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Block\BlockBaseTest.
 */

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
   * @see \Drupal\Core\Block\BlockBase::getMachineNameSuggestion().
   */
  public function testGetMachineNameSuggestion() {
    $transliteration = $this->getMockBuilder('Drupal\Core\Transliteration\PhpTransliteration')
      // @todo Inject the module handler into PhpTransliteration.
      ->setMethods(array('readLanguageOverrides'))
      ->getMock();

    $config = array();
    $definition = array(
      'admin_label' => 'Admin label',
      'provider' => 'block_test',
    );
    $block_base = new TestBlockInstantiation($config, 'test_block_instantiation', $definition);
    $block_base->setTransliteration($transliteration);
    $this->assertEquals('adminlabel', $block_base->getMachineNameSuggestion());

    // Test with more unicodes.
    $definition = array(
      'admin_label' => 'über åwesome',
      'provider' => 'block_test',
    );
    $block_base = new TestBlockInstantiation($config, 'test_block_instantiation', $definition);
    $block_base->setTransliteration($transliteration);
    $this->assertEquals('uberawesome', $block_base->getMachineNameSuggestion());
  }

}
