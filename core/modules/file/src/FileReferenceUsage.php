<?php

declare(strict_types=1);

namespace Drupal\file;

/**
 * Provides information which field on a given entity uses a file.
 *
 * @see \Drupal\file\FileReferenceResolver::loadEntityFromUsage()
 */
readonly class FileReferenceUsage {

  /**
   * Constructs a FileReferenceUsage object.
   *
   * @param string $entityTypeId
   *   The entity type.
   * @param string $fieldName
   *   The name of the field that contains the reference.
   * @param string|int|null $id
   *   The entity ID.
   * @param int|string|null $revisionId
   *   The revision ID.
   */
  public function __construct(
    public string $entityTypeId,
    public string $fieldName,
    public int|string|null $id = NULL,
    public int|string|null $revisionId = NULL,
  ) {
    if (is_null($id) && is_null($revisionId)) {
      throw new \InvalidArgumentException('$id and $revisionId cannot both be null.');
    }
  }

}
