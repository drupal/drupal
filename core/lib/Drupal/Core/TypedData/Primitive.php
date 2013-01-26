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
   * @see \Drupal\Core\TypedData\Type\Boolean
   */
  const BOOLEAN = 1;

  /**
   * The STRING primitive data type.
   *
   * @see \Drupal\Core\TypedData\Type\String
   */
  const STRING = 2;

  /**
   * The INTEGER primitive data type.
   *
   * @see \Drupal\Core\TypedData\Type\Integer
   */
  const INTEGER = 3;

  /**
   * The FLOAT primitive data type.
   *
   * @see \Drupal\Core\TypedData\Type\Float
   */
  const FLOAT = 4;

  /**
   * The DATE primitive data type.
   *
   * @see \Drupal\Core\TypedData\Type\Date
   */
  const DATE = 5;

  /**
   * The DURATION primitive data type.
   *
   * @see \Drupal\Core\TypedData\Type\Duration
   */
  const DURATION = 6;

  /**
   * The URI primitive data type.
   *
   * @see \Drupal\Core\TypedData\Type\Uri
   */
  const URI = 7;

  /**
   * The BINARY primitive data type.
   *
   * @see \Drupal\Core\TypedData\Type\Binary
   */
  const BINARY = 8;
}
