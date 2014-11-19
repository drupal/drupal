<?php

/**
 * @file
 * Contains \Drupal\Tests\block\Unit\BlockFormTest.
 */

namespace Drupal\Tests\block\Unit;

use Drupal\block\BlockForm;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\block\BlockForm
 * @group block
 */
class BlockFormTest extends UnitTestCase {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $conditionManager;

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $storage;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $dispatcher;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $language;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->conditionManager = $this->getMock('Drupal\Core\Executable\ExecutableManagerInterface');
    $this->language = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->storage = $this->getMock('Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValue($this->storage));

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

    $this->storage->expects($this->exactly(5))
      ->method('getQuery')
      ->will($this->returnValue($query));

    $block_form_controller = new BlockForm($this->entityManager, $this->conditionManager, $this->dispatcher, $this->language);

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
