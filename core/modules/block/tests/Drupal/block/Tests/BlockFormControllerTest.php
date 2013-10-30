<?php

/**
 * @file
 * Contains \Drupal\Tests\block\BlockFormControllerTest.
 */

namespace Drupal\Tests\block;

use Drupal\block\BlockFormController;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the block form controller.
 *
 * @see \Drupal\block\BlockFormController
 */
class BlockFormControllerTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Block form controller',
      'description' => 'Tests the block form controller.',
      'group' => 'Block',
    );
  }

  /**
   * Tests the unique machine name generator.
   *
   * @see \Drupal\block\BlockFormController::getUniqueMachineName()
   */
  public function testGetUniqueMachineName() {
    $block_storage = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigStorageController')
      ->disableOriginalConstructor()
      ->getMock();
    $blocks = array();

    $blocks['test'] = $this->getBlockMockWithMachineName('test');
    $blocks['other_test'] = $this->getBlockMockWithMachineName('other_test');
    $blocks['other_test_1'] = $this->getBlockMockWithMachineName('other_test');
    $blocks['other_test_2'] = $this->getBlockMockWithMachineName('other_test');

    $query = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $query->expects($this->exactly(5))
      ->method('condition')
      ->will($this->returnValue($query));

    $query->expects($this->exactly(5))
      ->method('execute')
      ->will($this->returnValue(array('test', 'other_test', 'other_test_1', 'other_test_2')));

    $query_factory = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $query_factory->expects($this->exactly(5))
      ->method('get')
      ->with('block', 'AND')
      ->will($this->returnValue($query));

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $entity_manager->expects($this->any())
      ->method('getStorageController')
      ->will($this->returnValue($block_storage));

    $language_manager = $this->getMockBuilder('Drupal\Core\Language\LanguageManager')
      ->disableOriginalConstructor()
      ->getMock();

    $config_factory = $this->getMockBuilder('Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $block_form_controller = new BlockFormController($entity_manager, $query_factory, $language_manager, $config_factory);

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
