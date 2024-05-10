<?php

namespace Drupal\Core\Validation\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Constraint attribute object.
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
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Constraint extends Plugin {

  /**
   * Constructs a Constraint attribute.
   *
   * @param string $id
   *   The constraint plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name of the constraint plugin.
   * @param string|string[]|false $type
   *   (optional) DataType plugin IDs for which this constraint applies. Valid
   *   values are any types registered by the typed data API, or an array of
   *   multiple type names. For supporting all types, FALSE may be specified.
   *   The key defaults to an empty array, which indicates no types are
   *   supported.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly string|array|false $type = [],
    public readonly ?string $deriver = NULL,
  ) {}

}
