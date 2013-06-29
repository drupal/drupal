<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\Primitive.
 */

namespace Drupal\Core\TypedData;

/**
 * Class that holds constants for all primitive data types.
 */
final class Primitive {

  /**
   * The BOOLEAN primitive data type.
   *
   * @see \Drupal\Core\TypedData\Plugin\DataType\Boolean
   */
  const BOOLEAN = 1;

  /**
   * The STRING primitive data type.
   *
   * @see \Drupal\Core\TypedData\Plugin\DataType\String
   */
  const STRING = 2;

  /**
   * The INTEGER primitive data type.
   *
   * @see \Drupal\Core\TypedData\Plugin\DataType\Integer
   */
  const INTEGER = 3;

  /**
   * The FLOAT primitive data type.
   *
   * @see \Drupal\Core\TypedData\Plugin\DataType\Float
   */
  const FLOAT = 4;

  /**
   * The DATE primitive data type.
   *
   * @see \Drupal\Core\TypedData\Plugin\DataType\Date
   */
  const DATE = 5;

  /**
   * The DURATION primitive data type.
   *
   * @see \Drupal\Core\TypedData\Plugin\DataType\Duration
   */
  const DURATION = 6;

  /**
   * The URI primitive data type.
   *
   * @see \Drupal\Core\TypedData\Plugin\DataType\Uri
   */
  const URI = 7;

  /**
   * The BINARY primitive data type.
   *
   * @see \Drupal\Core\TypedData\Plugin\DataType\Binary
   */
  const BINARY = 8;
}
