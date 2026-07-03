<?php

declare(strict_types=1);

namespace Drupal\Core\Routing;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Form\FormInterface;
use Symfony\Component\Routing\Attribute\DeprecatedAlias;
use Symfony\Component\Routing\Attribute\Route as RouteAttribute;
use Symfony\Component\Routing\Exception\InvalidArgumentException;
use Symfony\Component\Routing\RouteCollection;

/**
 * Discovers routes using Symfony's Route attribute.
 *
 * @see \Symfony\Component\Routing\Attribute\Route
 */
class AttributeRouteDiscovery extends StaticRouteDiscoveryBase {

  /**
   * @param \Traversable<string, string|array<int, string>> $namespaces
   *   An object implementing \Traversable containing root paths keyed by the
   *   corresponding namespace to look for controller implementations.
   */
  public function __construct(
    protected readonly \Traversable $namespaces,
  ) {}

  /**
   * {@inheritdoc}
   */
  protected static function getPriority(): int {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function collectRoutes(): iterable {
    $routeTypes = [
      'Controller' => $this->createControllerRouteCollection(...),
      'Form' => $this->createFormRouteCollection(...),
    ];

    foreach ($this->namespaces as $namespace => $directories) {
      foreach ((array) $directories as $directory) {
        foreach ($routeTypes as $routeType => $factory) {
          $routeDirectory = $directory . '/' . $routeType;
          $routeNamespace = $namespace . '\\' . $routeType;
          if (is_dir($routeDirectory)) {
            $iterator = new \RecursiveIteratorIterator(
              new \RecursiveDirectoryIterator($routeDirectory, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $fileinfo) {
              if ($fileinfo->getExtension() == 'php') {
                // Skip files that do not contain a Route attribute.
                $contents = file_get_contents($fileinfo->getPathname());
                if (!str_contains($contents, '#[Route') && !str_contains($contents, 'Routing\\Attribute\\Route')) {
                  continue;
                }
                $subPath = $iterator->getSubIterator()->getSubPath();
                $subPath = $subPath ? str_replace(DIRECTORY_SEPARATOR, '\\', $subPath) . '\\' : '';
                $class = $routeNamespace . '\\' . $subPath . $fileinfo->getBasename('.php');
                $reflectionClass = $this->getReflectionClass($class);
                if ($reflectionClass !== NULL) {
                  yield $factory($reflectionClass);
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Creates a route collection from a controller class's attributed methods.
   *
   * @param \ReflectionClass<object> $class
   *   The reflection object of the class to generate a route collection for.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  private function createControllerRouteCollection(\ReflectionClass $class): RouteCollection {
    $collection = new RouteCollection();
    $globals = $this->getGlobals($class);
    $fqcnAlias = FALSE;

    if (!$class->hasMethod('__invoke')) {
      foreach ($this->getAttributes($class) as $attribute) {
        if ($attribute->aliases) {
          throw new InvalidArgumentException(\sprintf('Route aliases cannot be used on non-invokable class "%s".', $class->getName()));
        }
      }
    }

    foreach ($class->getMethods() as $method) {
      $routeNamesBefore = array_keys($collection->all());
      foreach ($this->getAttributes($method) as $attribute) {
        $controllerName = $class->getName() . '::' . $method->getName();
        if ($method->name === '__invoke') {
          $fqcnAlias = TRUE;
          $controllerName = $class->getName();
        }
        $attribute->defaults = ['_controller' => $controllerName] + $attribute->defaults;
        $this->addRoute($collection, $attribute, $globals, $class, $method);
      }

      if ($collection->count() - \count($routeNamesBefore) === 1) {
        $newRouteName = current(array_diff(array_keys($collection->all()), $routeNamesBefore));
        if ($newRouteName !== $aliasName = \sprintf('%s::%s', $class->name, $method->name)) {
          $collection->addAlias($aliasName, $newRouteName);
        }
      }
    }

    // See https://symfony.com/doc/current/controller/service.html#invokable-controllers.
    if ($collection->count() === 0 && $class->hasMethod('__invoke')) {
      $globals = $this->resetGlobals();
      foreach ($this->getAttributes($class) as $attribute) {
        $attribute->defaults = ['_controller' => $class->getName()] + $attribute->defaults;
        $this->addRoute($collection, $attribute, $globals, $class, $class->getMethod('__invoke'));
        $fqcnAlias = TRUE;
      }
    }
    if ($fqcnAlias && $collection->count() === 1) {
      $invokeRouteName = key($collection->all());
      if ($invokeRouteName !== $class->name) {
        $collection->addAlias($class->name, $invokeRouteName);
      }

      $aliasName = \sprintf('%s::__invoke', $class->name);
      if ($aliasName !== $invokeRouteName) {
        $collection->addAlias($aliasName, $invokeRouteName);
      }
    }

    return $collection;
  }

  /**
   * Gets a reflection class from the class name.
   *
   * @param class-string $className
   *   The class to reflect.
   *
   * @return \ReflectionClass<object>|null
   *   The Reflection class, is the class is a valid to check for routes,
   *   otherwise NULL. A class is invalid if there is an error on Reflection or
   *   if it is abstract.
   */
  private function getReflectionClass(string $className): ?\ReflectionClass {
    try {
      $exists = class_exists($className);
    }
    catch (\Error) {
      // Ignore errors if a class extends a missing class, interface,
      // or trait.
      return NULL;
    }

    if ($exists) {
      $class = new \ReflectionClass($className);
      if (!$class->isAbstract()) {
        return $class;
      }
    }

    return NULL;
  }

  /**
   * Creates the default route settings for a class.
   *
   * A class can use the route attribute on the class to set defaults for all
   * attributed methods on the class.
   *
   * @param \ReflectionClass $class
   *   The class to create global settings for.
   *
   * @return array
   *   An array of route defaults.
   */
  private function getGlobals(\ReflectionClass $class): array {
    $globals = $this->resetGlobals();

    /** @var \Symfony\Component\Routing\Attribute\Route $attribute */
    $attribute = ($class->getAttributes(RouteAttribute::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? NULL)?->newInstance();
    if ($attribute) {
      if ($attribute->name !== NULL) {
        $globals['name'] = $attribute->name;
      }

      if ($attribute->path !== NULL) {
        $globals['path'] = $attribute->path;
        if (is_array($attribute->path)) {
          throw new UnsupportedRouteAttributePropertyException(sprintf('The "%s" route attribute does not support arrays in class "%s"', "path", $class->getName()));
        }
      }

      if ($attribute->requirements !== NULL) {
        $globals['requirements'] = $attribute->requirements;
      }

      if ($attribute->options !== NULL) {
        $globals['options'] = $attribute->options;
      }

      if ($attribute->defaults !== NULL) {
        $globals['defaults'] = $attribute->defaults;
        if (!empty($attribute->defaults['_locale'])) {
          throw new UnsupportedRouteAttributePropertyException(sprintf('The "%s" route attribute is not supported in class "%s""', "locale", $class->getName()));
        }
      }

      if (!empty($attribute->schemes)) {
        $globals['schemes'] = $attribute->schemes;
      }

      if (!empty($attribute->methods)) {
        $globals['methods'] = $attribute->methods;
      }

      if ($attribute->host !== NULL) {
        $globals['host'] = $attribute->host;
      }

      if ($attribute->condition !== NULL) {
        throw new UnsupportedRouteAttributePropertyException(sprintf('The "%s" route attribute is not supported in class "%s"', "condition", $class->getName()));
      }

      $globals['priority'] = $attribute->priority ?? 0;

      foreach ($globals['requirements'] as $placeholder => $requirement) {
        if (\is_int($placeholder)) {
          throw new \InvalidArgumentException(sprintf('A placeholder name must be a string (%d given). Did you forget to specify the placeholder key for the requirement "%s" in "%s"?', $placeholder, $requirement, $class->getName()));
        }
      }
    }

    return $globals;
  }

  /**
   * Creates a route collection from a form class's attributed methods.
   *
   * @param \ReflectionClass<object> $class
   *   The reflection object of the class to generate a route collection for.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  private function createFormRouteCollection(\ReflectionClass $class): RouteCollection {
    $collection = new RouteCollection();
    if (!$class->implementsInterface(FormInterface::class)) {
      return $collection;
    }

    foreach ($this->getAttributes($class) as $attribute) {
      $attribute->defaults = ['_form' => $class->getName()] + $attribute->defaults;
      $this->addRoute($collection, $attribute, $this->resetGlobals(), $class);
      $formRouteName = $attribute->name;
    }
    // If there is only one route defined for the form class, add the class name
    // as an alias for the route.
    if (count($collection) === 1 && isset($formRouteName)) {
      $collection->addAlias($class->getName(), $formRouteName);
    }

    // Route attributes on form class methods are not supported.
    assert(
      Inspector::assertAll(
        fn($method) => $this->getAttributes($method)->key() === NULL,
        $class->getMethods()
      ),
      sprintf('Route attributes can not target methods on class %s. Use the attribute on the form class itself.', $class->getName())
    );

    return $collection;
  }

  /**
   * Adds a route to the provided route collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to add the route to.
   * @param \Symfony\Component\Routing\Attribute\Route $attribute
   *   The attribute object that describes the route.
   * @param array $globals
   *   The defaults for the class.
   * @param \ReflectionClass $class
   *   The class.
   * @param \ReflectionMethod|null $method
   *   The attributed method.
   */
  private function addRoute(RouteCollection $collection, RouteAttribute $attribute, array $globals, \ReflectionClass $class, ?\ReflectionMethod $method = NULL): void {
    if ($class->implementsInterface(FormInterface::class)) {
      $classMethod = $class->getName();
    }
    elseif ($method !== NULL) {
      $classMethod = $class->getName() . '::' . $method->getName() . '()';
    }
    else {
      throw new \InvalidArgumentException('Method must be specified on non-form routes.');
    }
    if ($attribute->name === NULL) {
      throw new UnsupportedRouteAttributePropertyException(sprintf('The Route attribute on "%s" is missing a required "name" property.', $classMethod));
    }
    $name = $globals['name'] . $attribute->name;

    if (is_array($attribute->path)) {
      throw new UnsupportedRouteAttributePropertyException(sprintf('The "%s" route attribute does not support arrays on route "%s" in "%s"', "path", $name, $classMethod));
    }
    if (!empty($attribute->defaults['_locale'])) {
      throw new UnsupportedRouteAttributePropertyException(sprintf('The "%s" route attribute is not supported on route "%s" in "%s"', "locale", $name, $classMethod));
    }
    if ($attribute->condition !== NULL) {
      throw new UnsupportedRouteAttributePropertyException(sprintf('The "%s" route attribute is not supported on route "%s" in "%s"', "condition", $name, $classMethod));
    }

    $requirements = $attribute->requirements;

    foreach ($requirements as $placeholder => $requirement) {
      if (\is_int($placeholder)) {
        throw new \InvalidArgumentException(sprintf('A placeholder name must be a string (%d given). Did you forget to specify the placeholder key for the requirement "%s" of route "%s" in "%s"?', $placeholder, $requirement, $name, $classMethod));
      }
    }

    $defaults = array_replace($globals['defaults'], $attribute->defaults);
    $requirements = array_replace($globals['requirements'], $requirements);
    $options = array_replace($globals['options'], $attribute->options);
    $schemes = array_unique(array_merge($globals['schemes'], $attribute->schemes));
    $methods = array_unique(array_merge($globals['methods'], $attribute->methods));

    $host = $attribute->host ?? $globals['host'];
    $priority = $attribute->priority ?? $globals['priority'];

    $path = $attribute->path;
    $prefix = $globals['path'];

    $route = $this->createRoute($prefix . $path, $defaults, $requirements, $options, $host, $schemes, $methods, NULL);
    $collection->add($name, $route, $priority);
    foreach ($attribute->aliases as $aliasAttribute) {
      if ($aliasAttribute instanceof DeprecatedAlias) {
        $alias = $collection->addAlias($aliasAttribute->aliasName, $name);
        $alias->setDeprecated(
          $aliasAttribute->package,
          $aliasAttribute->version,
          $aliasAttribute->message
        );
        continue;
      }

      $collection->addAlias($aliasAttribute, $name);
    }
  }

  /**
   * Gets the PHP attributes.
   *
   * @param \ReflectionClass|\ReflectionMethod $reflection
   *   The reflected class or method.
   *
   * @return iterable<int, RouteAttribute>
   *   The attributes.
   */
  private function getAttributes(\ReflectionClass|\ReflectionMethod $reflection): \Generator {
    foreach ($reflection->getAttributes(RouteAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
      yield $attribute->newInstance();
    }
  }

}
