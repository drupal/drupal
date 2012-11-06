<?php

/**
 * @file
 * Definition of Drupal\jsonld\Tests\JsonldTestBase.
 */

namespace Drupal\jsonld\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\config\Tests\ConfigEntityTest;

/**
 * Parent class for JSON-LD tests.
 */
abstract class JsonldNormalizerTestBase extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'entity_test');

  /**
   * Get the Entity ID.
   *
   * @param Drupal\Core\Entity\EntityNG $entity
   *   Entity to get URI for.
   *
   * @return string
   *   Return the entity URI.
   */
  protected function getEntityId($entity) {
    global $base_url;
    $uriInfo = $entity->uri();
    return $base_url . '/' . $uriInfo['path'];
  }

}
