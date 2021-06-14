<?php

namespace Drupal\Core\Validation\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a validation constraint annotation object.
 *
 * Plugin Namespace: Plugin\Validation\Constraint
 *
 * For a working example, see
 * \Drupal\Core\Validation\Plugin\Validation\Constraint\LengthConstraint
 *
 * @see \Drupal\Core\Validation\ConstraintManager
 * @see \Symfony\Component\Validator\Constraint
 * @see hook_validation_constraint_alter()
 * @see plugin_api
 *
 * @Annotation
 */
class Constraint extends Plugin {

  /**
   * The constraint plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the constraint plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var string|\Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * An array of DataType plugin IDs for which this constraint applies. Valid
   * values are any types registered by the typed data API, or an array of
   * multiple type names. For supporting all types, FALSE may be specified. The
   * key defaults to an empty array, which indicates no types are supported.
   *
   * @var string|string[]|false
   *
   * @see \Drupal\Core\TypedData\Annotation\DataType
   */
  public $type = [];

}
