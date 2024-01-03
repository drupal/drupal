<?php

declare(strict_types=1);

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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->array1 = [
      'same' => 'yes',
      'different' => 'no',
      'array_empty_diff' => [],
      'null' => NULL,
      'int_diff' => 1,
      'array_diff' => ['same' => 'same', 'array' => ['same' => 'same']],
      'array_compared_to_string' => ['value'],
      'string_compared_to_array' => 'value',
      'new' => 'new',
    ];
    $this->array2 = [
      'same' => 'yes',
      'different' => 'yes',
      'array_empty_diff' => [],
      'null' => NULL,
      'int_diff' => '1',
      'array_diff' => ['same' => 'different', 'array' => ['same' => 'same']],
      'array_compared_to_string' => 'value',
      'string_compared_to_array' => ['value'],
    ];
  }

  /**
   * Tests DiffArray::diffAssocRecursive().
   */
  public function testDiffAssocRecursive() {
    $expected = [
      'different' => 'no',
      'int_diff' => 1,
      // The 'array' key should not be returned, as it's the same.
      'array_diff' => ['same' => 'same'],
      'array_compared_to_string' => ['value'],
      'string_compared_to_array' => 'value',
      'new' => 'new',
    ];

    $this->assertSame($expected, DiffArray::diffAssocRecursive($this->array1, $this->array2));
  }

}
