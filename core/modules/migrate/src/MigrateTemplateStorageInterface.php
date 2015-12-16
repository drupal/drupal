<?php

/**
 * @file
 * Contains \Drupal\migrate\MigrateTemplateStorageInterface.
 */

namespace Drupal\migrate;

/**
 * The MigrateTemplateStorageInterface interface.
 */
interface MigrateTemplateStorageInterface {

  /**
   * Find all migration templates with the specified tag.
   *
   * @param $tag
   *   The tag to match.
   *
   * @return array
   *   Any templates (parsed YAML config) that matched, keyed by the ID.
   */
  public function findTemplatesByTag($tag);

  /**
   * Retrieve a template given a specific name.
   *
   * @param string $name
   *   A migration template name.
   *
   * @return NULL|array
   *   A parsed migration template, or NULL if it doesn't exist.
   */
  public function getTemplateByName($name);

  /**
   * Retrieves all migration templates belonging to enabled extensions.
   *
   * @return array
   *   Array of parsed templates, keyed by the fully-qualified id.
   */
  public function getAllTemplates();

}
