<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityResolverManager.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Symfony\Component\Routing\Route;

/**
 * Sets the entity route parameter converter options automatically.
 *
 * If controllers of routes with route parameters, type-hint the parameters with
 * an entity interface, upcasting is done automatically.
 */
class EntityResolverManager {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Constructs a new EntityRouteAlterSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(EntityManagerInterface $entity_manager, ControllerResolverInterface $controller_resolver, ClassResolverInterface $class_resolver) {
    $this->entityManager = $entity_manager;
    $this->controllerResolver = $controller_resolver;
    $this->classResolver = $class_resolver;
  }

  /**
   * Creates a controller instance using route defaults.
   *
   * By design we cannot support all possible routes, but just the ones which
   * use the defaults provided by core, which are _content, _controller
   * and _form.
   *
   * @param array $defaults
   *   The default values provided by the route.
   *
   * @return array|null
   *   Returns the controller instance if it is possible to instantiate it, NULL
   */
  protected function getController(array $defaults) {
    $controller = NULL;
    if (isset($defaults['_content'])) {
      $controller = $this->controllerResolver->getControllerFromDefinition($defaults['_content']);
    }
    if (isset($defaults['_controller'])) {
      $controller = $this->controllerResolver->getControllerFromDefinition($defaults['_controller']);
    }

    if (isset($defaults['_form'])) {
      $form_arg = $defaults['_form'];
      // Check if the class exists first as the class resolver will throw an
      // exception if it doesn't. This also means a service cannot be used here.
      if (class_exists($form_arg)) {
        $controller = array($this->classResolver->getInstanceFromDefinition($form_arg), 'buildForm');
      }
    }

    return $controller;
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
    $parameter_definitions = $route->getOption('parameters') ?: array();

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
          $parameter_definitions += array($parameter_name => array());
          $parameter_definitions[$parameter_name] += array(
            'type' => 'entity:' . $parameter_name,
          );
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

    if (isset($entity_type) && isset($this->getEntityTypes()[$entity_type])) {
      $parameter_definitions = $route->getOption('parameters') ?: array();

      // First try to figure out whether there is already a parameter upcasting
      // the same entity type already.
      foreach ($parameter_definitions as $info) {
        if (isset($info['type'])) {
          // The parameter types are in the form 'entity:$entity_type'.
          list(, $parameter_entity_type) = explode(':', $info['type'], 2);
          if ($parameter_entity_type == $entity_type) {
            return;
          }
        }
      }

      if (!isset($parameter_definitions[$entity_type])) {
        $parameter_definitions[$entity_type] = array();
      }
      $parameter_definitions[$entity_type] += array(
        'type' => 'entity:' . $entity_type,
      );
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
    if ($controller = $this->getController($route->getDefaults())) {
      // Try to use reflection.
      if ($this->setParametersFromReflection($controller, $route)) {
        return;
      }
    }

    // Try to use _entity_* information on the route.
    $this->setParametersFromEntityInformation($route);
  }

  /**
   * Returns a list of all entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected function getEntityTypes() {
    if (!isset($this->entityTypes)) {
      $this->entityTypes = $this->entityManager->getDefinitions();
    }
    return $this->entityTypes;
  }

}
