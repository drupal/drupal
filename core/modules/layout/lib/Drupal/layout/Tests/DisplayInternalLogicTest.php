<?php

/**
 * @file
 * Definition of \Drupal\layout\Tests\DisplayInternalLogicTest.
 */

namespace Drupal\layout\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\layout\Plugin\Core\Entity\Display;
use Drupal\layout\Plugin\Core\Entity\UnboundDisplay;

/**
 * Tests the API and internal logic offered by Displays.
 */
class DisplayInternalLogicTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('layout', 'layout_test');

  /**
   * The twocol test display.
   *
   * @var \Drupal\layout\Plugin\Core\Entity\Display
   */
  public $twocol;

  /**
   * The onecol test display.
   *
   * @var \Drupal\layout\Plugin\Core\Entity\Display
   */
  public $onecol;

  /**
   * The unbound test display.
   *
   * @var \Drupal\layout\Plugin\Core\Entity\UnboundDisplay
   */
  public $unbound;

  public static function getInfo() {
    return array(
      'name' => 'Display behaviors',
      'description' => 'Tests internal behaviors of DisplayInterface implementations, such as layout remapping.',
      'group' => 'Display',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->twocol = entity_load('display', 'test_twocol');
    $this->onecol = entity_load('display', 'test_onecol');
    $this->unbound = entity_load('unbound_display', 'test_unbound_display');
  }

  /**
   * Tests block sorting within regions.
   */
  public function testBlockSorting() {
    $expected = array(
      'left' => array('block.test_block_3', 'block.test_block_1'),
      'right' => array('block.test_block_2'),
    );
    $this->assertIdentical($this->twocol->getSortedBlocksByRegion('left'), $expected['left']);
    $this->assertIdentical($this->twocol->getSortedBlocksByRegion('right'), $expected['right']);
    $this->assertIdentical($this->twocol->getAllSortedBlocks(), $expected);
  }

  /**
   * Test the various block remapping scenarios allowed for by the assorted
   * Display types.
   *
   * This includes remapping a Display's blocks to a new layout, binding an
   * UnboundDisplay with a layout to generate a new Display, and releasing a
   * Display from its layout binding to generate an UnboundDisplay.
   */
  public function testBlockMapping() {
    // Remap from twocol to onecol. All blocks are expected to move to the one
    // and only region and be sorted by their original weights.
    $expected = array(
      'middle' => array('block.test_block_3', 'block.test_block_2', 'block.test_block_1'),
    );
    $two_to_one = clone($this->twocol);
    $two_to_one->remapToLayout($this->onecol->getLayoutInstance());
    $this->assertIdentical($two_to_one->getAllSortedBlocks(), $expected);

    // Remap from onecol to twocol. Since the blocks are assigned the 'content'
    // region type, and twocol's 'left' region has that type, the blocks are
    // expected to move to there and be sorted by their original weights.
    $expected = array(
      'left' => array('block.test_block_2', 'block.test_block_1'),
      'right' => array(),
    );
    $one_to_two = clone($this->onecol);
    $one_to_two->remapToLayout($this->twocol->getLayoutInstance());
    $this->assertIdentical($one_to_two->getAllSortedBlocks(), $expected);

    // Bind the unbound display to the twocol layout:
    // - Block 1 is assigned the 'content' region type, so is expected to be
    //   mapped to the 'left' region, which has that type.
    // - Block 2 is assigned the 'aside' region type, so is expected to be
    //   mapped to the 'right' region, which has that type.
    // - Block 3 is assigned the 'nav' region type, and there is no twocol
    //   region with that type, so it is expected to be mapped to twocol's
    //   first region, which is 'left'.
    $expected = array(
      'left' => array('block.test_block_1', 'block.test_block_3'),
      'right' => array('block.test_block_2'),
    );
    $unbound_to_twocol = $this->unbound->generateDisplay($this->twocol->getLayoutInstance(), 'unbound_to_twocol');
    $this->assertTrue($unbound_to_twocol instanceof Display, 'Binding the unbound display successfully created a Display object');
    $this->assertIdentical($unbound_to_twocol->getAllSortedBlocks(), $expected);

    // Generate an unbound display from the twocol display.
    $expected = array(
      'block.test_block_1' => array('region-type' => 'content', 'weight' => 100),
      'block.test_block_2' => array('region-type' => 'aside', 'weight' => 0),
      'block.test_block_3' => array('region-type' => 'content', 'weight' => -100),
    );
    $twocol_to_unbound = $this->twocol->generateUnboundDisplay('twocol_to_unbound');
    $this->assertTrue($twocol_to_unbound instanceof UnboundDisplay, 'Unbinding the twocol display successfully created an UnboundDisplay object');
    // We can use Equal instead of Identical, because for this, array order and
    // integer vs. string data types do not matter.
    $this->assertEqual($twocol_to_unbound->getAllBlockInfo(), $expected);
  }
}
