<?php

namespace Drupal\Tests\Core\Plugin\Fixtures\Plugin\DataType;

use Drupal\Core\TypedData\TypedData;

/**
 * Provides a test data type.
 *
 * @DataType(
 *   id = "test_data_type",
 *   label = @Translation("Test data type"),
 *   deriver = "Drupal\Tests\Core\Plugin\Fixtures\Plugin\DataType\TestDataTypeDeriver"
 * )
 */
class TestDataType extends TypedData {

  /**
   * Required by the parent class.
   *
   * @var mixed
   */
  protected $value;

}
