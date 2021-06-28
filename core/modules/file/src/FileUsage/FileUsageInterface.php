<?php

namespace Drupal\file\FileUsage;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\file\FileInterface;

/**
 * File usage backend interface.
 */
interface FileUsageInterface {

  /**
   * Records that a module is using a file.
   *
   * Examples:
   * - A module that associates files with nodes, so $type would be
   *   'node' and $id would be the node's nid. Files for all revisions are
   *   stored within a single nid.
   * - The User module associates an image with a user, so $type would be 'user'
   *   and the $id would be the user's uid.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   * @param string $module
   *   The name of the module using the file.
   * @param string $type
   *   The type of the object that contains the referenced file.
   * @param string $id
   *   The unique ID of the object containing the referenced file.
   * @param int $count
   *   (optional) The number of references to add to the object. Defaults to 1.
   */
  public function add(FileInterface $file, $module, $type, $id, $count = 1);

  /**
   * Removes a record to indicate that a module is no longer using a file.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   * @param string $module
   *   The name of the module using the file.
   * @param string $type
   *   (optional) The type of the object that contains the referenced file. May
   *   be omitted if all module references to a file are being deleted. Defaults
   *   to NULL.
   * @param string $id
   *   (optional) The unique ID of the object containing the referenced file.
   *   May be omitted if all module references to a file are being deleted.
   *   Defaults to NULL.
   * @param int $count
   *   (optional) The number of references to delete from the object. Defaults
   *   to 1. Zero may be specified to delete all references to the file within a
   *   specific object.
   */
  public function delete(FileInterface $file, $module, $type = NULL, $id = NULL, $count = 1);

  /**
   * Determines where a file is used.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   *
   * @return array
   *   A nested array with usage data. The first level is keyed by module name,
   *   the second by object type and the third by the object id. The value of
   *   the third level contains the usage count.
   */
  public function listUsage(FileInterface $file);

  /**
   * Retrieves a list of references to a file.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   * @param \Drupal\Core\Field\FieldDefinitionInterface|null $field
   *   (optional) A field definition to be used for this check. If given, limits
   *   the reference check to the given field. Defaults to NULL.
   * @param string $age
   *   (optional) A constant that specifies which references to count. Use
   *   EntityStorageInterface::FIELD_LOAD_REVISION (the default) to retrieve all
   *   references within all revisions or
   *   EntityStorageInterface::FIELD_LOAD_CURRENT to retrieve references only in
   *   the current revisions of all entities that have references to this file.
   * @param string $field_type
   *   (optional) The name of a field type. If given, limits the reference check
   *   to fields of the given type. If both $field and $field_type are given but
   *   $field is not the same type as $field_type, an empty array will be
   *   returned. Defaults to 'file'.
   *
   * @return array
   *   A multidimensional array. The keys are field_name, entity_type,
   *   entity_id and the value is an entity referencing this file.
   */
  public function getReferences(FileInterface $file, FieldDefinitionInterface $field = NULL, $age = EntityStorageInterface::FIELD_LOAD_REVISION, $field_type = 'file');

}
