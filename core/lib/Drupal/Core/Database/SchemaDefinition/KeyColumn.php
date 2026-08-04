<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

/**
 * Describes a column of a database index or key.
 */
final class KeyColumn implements SchemaDefinitionInterface {

  /**
   * Constructor.
   *
   * @param string $name
   *   The column name.
   * @param int|null $length
   *   (Optional) if set, specifies a prefix of the named column.
   */
  public function __construct(
    public readonly string $name,
    public readonly ?int $length = NULL,
  ) {
  }

  /**
   * The key column cannot be converted to an array.
   *
   * @throws \LogicException
   */
  public function toArray(): array {
    throw new \LogicException(__METHOD__ . ' is not implemented');
  }

}
