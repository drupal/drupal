<?php

/**
 * @file
 * Contains Drupal\Core\ParamConverter\EntityConverter.
 */

namespace Drupal\Core\ParamConverter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\EntityManager;

/**
 * This class allows the upcasting of entity ids to the respective entity
 * object.
 */
class EntityConverter implements ParamConverterInterface {

  /**
   * Entity manager which performs the upcasting in the end.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a new EntityConverter.
   *
   * @param \Drupal\Core\Entity\EntityManager $entityManager
   *   The entity manager.
   */
  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  /**
   * Tries to upcast every variable to an entity type.
   *
   * If there is a type denoted in the route options it will try to upcast to
   * it, if there is no definition in the options it will try to upcast to an
   * entity type of that name. If the chosen enity type does not exists it will
   * leave the variable untouched.
   * If the entity type exist, but there is no entity with the given id it will
   * convert the variable to NULL.
   *
   * Example:
   *
   * pattern: '/a/{user}/some/{foo}/and/{bar}/'
   * options:
   *   converters:
   *     foo: 'node'
   *
   * The value for {user} will be converted to a user entity and the value
   * for {foo} to a node entity, but it will not touch the value for {bar}.
   *
   * It will not process variables which are marked as converted. It will mark
   * any variable it processes as converted.
   *
   * @param array &$variables
   *   Array of values to convert to their corresponding objects, if applicable.
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   * @param array &$converted
   *   Array collecting the names of all variables which have been
   *   altered by a converter.
   */
  public function process(array &$variables, Route $route, array &$converted) {
    $variable_names = $route->compile()->getVariables();

    $options = $route->getOptions();
    $configuredTypes = isset($options['converters']) ? $options['converters'] : array();

    $entityTypes = array_keys($this->entityManager->getDefinitions());

    foreach ($variable_names as $name) {
      // Do not process this variable if it's already marked as converted.
      if (in_array($name, $converted)) {
        continue;
      }

      // Obtain entity type to convert to from the route configuration or just
      // use the variable name as default.
      if (array_key_exists($name, $configuredTypes)) {
        $type = $configuredTypes[$name];
      }
      else {
        $type = $name;
      }

      if (in_array($type, $entityTypes)) {
        $value = $variables[$name];

        $storageController = $this->entityManager->getStorageController($type);
        $entities = $storageController->load(array($value));

        // Make sure $entities is null, if upcasting fails.
        $entity = $entities ? reset($entities) : NULL;
        $variables[$name] = $entity;

        // Mark this variable as converted.
        $converted[] = $name;
      }
    }
  }
}
