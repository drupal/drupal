<?php

namespace Drupal\Tests\Core\Common;

use Drupal\Component\Utility\DiffArray;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the DiffArray helper class.
 *
 * @group Common
 */
class DiffArrayTest extends UnitTestCase {

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

  protected function setUp() {
    parent::setUp();

    $this->array1 = array(
      'same' => 'yes',
      'different' => 'no',
      'array_empty_diff' => array(),
      'null' => NULL,
      'int_diff' => 1,
      'array_diff' => array('same' => 'same', 'array' => array('same' => 'same')),
      'array_compared_to_string' => array('value'),
      'string_compared_to_array' => 'value',
      'new' => 'new',
    );
    $this->array2 = array(
      'same' => 'yes',
      'different' => 'yes',
      'array_empty_diff' => array(),
      'null' => NULL,
      'int_diff' => '1',
      'array_diff' => array('same' => 'different', 'array' => array('same' => 'same')),
      'array_compared_to_string' => 'value',
      'string_compared_to_array' => array('value'),
    );
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
      'array_compared_to_string' => array('value'),
      'string_compared_to_array' => 'value',
      'new' => 'new',
    );

    $this->assertSame(DiffArray::diffAssocRecursive($this->array1, $this->array2), $expected);
  }

}
