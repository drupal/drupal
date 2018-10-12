<?php

namespace Drupal\Core\Entity;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Symfony\Component\Routing\Route;

/**
 * Sets the entity route parameter converter options automatically.
 *
 * If controllers of routes with route parameters, type-hint the parameters with
 * an entity interface, upcasting is done automatically.
 */
class EntityResolverManager {
  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['entityManager' => 'entity.manager'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Constructs a new EntityRouteAlterSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ClassResolverInterface $class_resolver) {
    if ($entity_type_manager instanceof EntityManagerInterface) {
      @trigger_error('Passing the entity.manager service to EntityResolverManager::__construct() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Pass the new dependencies instead. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
    else {
      $this->entityTypeManager = $entity_type_manager;
    }
    $this->classResolver = $class_resolver;
  }

  /**
   * Gets the controller class using route defaults.
   *
   * By design we cannot support all possible routes, but just the ones which
   * use the defaults provided by core, which are _controller and _form.
   *
   * Rather than creating an instance of every controller determine the class
   * and method that would be used. This is not possible for the service:method
   * notation as the runtime container does not allow static introspection.
   *
   * @see \Drupal\Core\Controller\ControllerResolver::getControllerFromDefinition()
   * @see \Drupal\Core\Controller\ClassResolver::getInstanceFromDefinition()
   *
   * @param array $defaults
   *   The default values provided by the route.
   *
   * @return string|null
   *   Returns the controller class, otherwise NULL.
   */
  protected function getControllerClass(array $defaults) {
    $controller = NULL;
    if (isset($defaults['_controller'])) {
      $controller = $defaults['_controller'];
    }

    if (isset($defaults['_form'])) {
      $controller = $defaults['_form'];
      // Check if the class exists and if so use the buildForm() method from the
      // interface.
      if (class_exists($controller)) {
        return [$controller, 'buildForm'];
      }
    }

    if (strpos($controller, ':') === FALSE) {
      if (method_exists($controller, '__invoke')) {
        return [$controller, '__invoke'];
      }
      if (function_exists($controller)) {
        return $controller;
      }
      return NULL;
    }

    $count = substr_count($controller, ':');
    if ($count == 1) {
      // Controller in the service:method notation. Get the information from the
      // service. This is dangerous as the controller could depend on services
      // that could not exist at this point. There is however no other way to
      // do it, as the container does not allow static introspection.
      list($class_or_service, $method) = explode(':', $controller, 2);
      return [$this->classResolver->getInstanceFromDefinition($class_or_service), $method];
    }
    elseif (strpos($controller, '::') !== FALSE) {
      // Controller in the class::method notation.
      return explode('::', $controller, 2);
    }

    return NULL;
  }

  /**
   * Sets the upcasting information using reflection.
   *
   * @param string|array $controller
   *   A PHP callable representing the controller.
   * @param \Symfony\Component\Routing\Route $route
   *   The route object to populate without upcasting information.
   *
   * @return bool
   *   Returns TRUE if the upcasting parameters could be set, FALSE otherwise.
   */
  protected function setParametersFromReflection($controller, Route $route) {
    $entity_types = $this->getEntityTypes();
    $parameter_definitions = $route->getOption('parameters') ?: [];

    $result = FALSE;

    if (is_array($controller)) {
      list($instance, $method) = $controller;
      $reflection = new \ReflectionMethod($instance, $method);
    }
    else {
      $reflection = new \ReflectionFunction($controller);
    }

    $parameters = $reflection->getParameters();
    foreach ($parameters as $parameter) {
      $parameter_name = $parameter->getName();
      // If the parameter name matches with an entity type try to set the
      // upcasting information automatically. Therefore take into account that
      // the user has specified some interface, so the upcasting is intended.
      if (isset($entity_types[$parameter_name])) {
        $entity_type = $entity_types[$parameter_name];
        $entity_class = $entity_type->getClass();
        if (($reflection_class = $parameter->getClass()) && (is_subclass_of($entity_class, $reflection_class->name) || $entity_class == $reflection_class->name)) {
          $parameter_definitions += [$parameter_name => []];
          $parameter_definitions[$parameter_name] += [
            'type' => 'entity:' . $parameter_name,
          ];
          $result = TRUE;
        }
      }
    }
    if (!empty($parameter_definitions)) {
      $route->setOption('parameters', $parameter_definitions);
    }
    return $result;
  }

  /**
   * Sets the upcasting information using the _entity_* route defaults.
   *
   * Supports the '_entity_view' and '_entity_form' route defaults.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   */
  protected function setParametersFromEntityInformation(Route $route) {
    if ($entity_view = $route->getDefault('_entity_view')) {
      list($entity_type) = explode('.', $entity_view, 2);
    }
    elseif ($entity_form = $route->getDefault('_entity_form')) {
      list($entity_type) = explode('.', $entity_form, 2);
    }

    // Do not add parameter information if the route does not declare a
    // parameter in the first place. This is the case for add forms, for
    // example.
    if (isset($entity_type) && isset($this->getEntityTypes()[$entity_type]) && (strpos($route->getPath(), '{' . $entity_type . '}') !== FALSE)) {
      $parameter_definitions = $route->getOption('parameters') ?: [];

      // First try to figure out whether there is already a parameter upcasting
      // the same entity type already.
      foreach ($parameter_definitions as $info) {
        if (isset($info['type']) && (strpos($info['type'], 'entity:') === 0)) {
          // The parameter types are in the form 'entity:$entity_type'.
          list(, $parameter_entity_type) = explode(':', $info['type'], 2);
          if ($parameter_entity_type == $entity_type) {
            return;
          }
        }
      }

      if (!isset($parameter_definitions[$entity_type])) {
        $parameter_definitions[$entity_type] = [];
      }
      $parameter_definitions[$entity_type] += [
        'type' => 'entity:' . $entity_type,
      ];
      if (!empty($parameter_definitions)) {
        $route->setOption('parameters', $parameter_definitions);
      }
    }
  }

  /**
   * Set the upcasting route objects.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object to add the upcasting information onto.
   */
  public function setRouteOptions(Route $route) {
    if ($controller = $this->getControllerClass($route->getDefaults())) {
      // Try to use reflection.
      if ($this->setParametersFromReflection($controller, $route)) {
        return;
      }
    }

    // Try to use _entity_* information on the route.
    $this->setParametersFromEntityInformation($route);
  }

  /**
   * Gets the list of all entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected function getEntityTypes() {
    if (!isset($this->entityTypes)) {
      $this->entityTypes = $this->entityTypeManager->getDefinitions();
    }
    return $this->entityTypes;
  }

}
