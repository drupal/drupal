<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockFormTest.
 */

namespace Drupal\block\Tests;

use Drupal\block\BlockForm;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the block form.
 *
 * @see \Drupal\block\BlockForm
 */
class BlockFormTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Block form',
      'description' => 'Tests the block form.',
      'group' => 'Block',
    );
  }

  /**
   * Tests the unique machine name generator.
   *
   * @see \Drupal\block\BlockForm::getUniqueMachineName()
   */
  public function testGetUniqueMachineName() {
    $blocks = array();

    $blocks['test'] = $this->getBlockMockWithMachineName('test');
    $blocks['other_test'] = $this->getBlockMockWithMachineName('other_test');
    $blocks['other_test_1'] = $this->getBlockMockWithMachineName('other_test');
    $blocks['other_test_2'] = $this->getBlockMockWithMachineName('other_test');

    $query = $this->getMock('Drupal\Core\Entity\Query\QueryInterface');
    $query->expects($this->exactly(5))
      ->method('condition')
      ->will($this->returnValue($query));

    $query->expects($this->exactly(5))
      ->method('execute')
      ->will($this->returnValue(array('test', 'other_test', 'other_test_1', 'other_test_2')));

    $block_storage = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $block_storage->expects($this->exactly(5))
      ->method('getQuery')
      ->will($this->returnValue($query));

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $entity_manager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValue($block_storage));

    $language_manager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');

    $config_factory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');

    $block_form_controller = new BlockForm($entity_manager, $language_manager, $config_factory);

    // Ensure that the block with just one other instance gets the next available
    // name suggestion.
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
