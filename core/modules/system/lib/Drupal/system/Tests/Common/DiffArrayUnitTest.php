<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Common\DiffArrayUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Component\Utility\DiffArray;
use Drupal\simpletest\UnitTestBase;

/**
 * Tests the DiffArray helper class.
 */
class DiffArrayUnitTest extends UnitTestBase {

  /**
   * Array to use for testing.
   *
   * @var array
   */
  protected $array1;

  /**
   * Array to use for testing.
   *
   * @var array
   */
  protected $array2;

  public static function getInfo() {
    return array(
      'name' => 'DiffArray functionality',
      'description' => 'Tests the DiffArray helper class.',
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp();

    $this->array1 = array(
      'same' => 'yes',
      'different' => 'no',
      'array_empty_diff' => array(),
      'null' => NULL,
      'int_diff' => 1,
      'array_diff' => array('same' => 'same', 'array' => array('same' => 'same')),
      'new' => 'new',
    );
    $this->array2 = array(
      'same' => 'yes',
      'different' => 'yes',
      'array_empty_diff' => array(),
      'null' => NULL,
      'int_diff' => '1',
      'array_diff' => array('same' => 'different', 'array' => array('same' => 'same')),
    );
  }

  /**
   * Tests DiffArray::diffAssoc().
   */
  public function testDiffAssoc() {
    $expected = array(
      'different' => 'no',
      'new' => 'new',
    );

    $this->assertIdentical(DiffArray::diffAssoc($this->array1, $this->array2), $expected);
  }

  /**
   * Tests DiffArray::diffAssocRecursive().
   */
  public function testDiffAssocRecursive() {
    $expected = array(
      'different' => 'no',
      'int_diff' => 1,
      // The 'array' key should not be returned, as it's the same.
      'array_diff' => array('same' => 'same'),
      'new' => 'new',
    );

    $this->assertIdentical(DiffArray::diffAssocRecursive($this->array1, $this->array2), $expected);
  }

}
