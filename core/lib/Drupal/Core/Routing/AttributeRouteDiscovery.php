<?php

declare(strict_types=1);

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Attribute\DeprecatedAlias;
use Symfony\Component\Routing\Attribute\Route as RouteAttribute;
use Symfony\Component\Routing\Exception\InvalidArgumentException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Discovers routes using Symfony's Route attribute.
 *
 * @see \Symfony\Component\Routing\Attribute\Route
 */
class AttributeRouteDiscovery extends StaticRouteDiscoveryBase {

  /**
   * @param \Traversable<string, string> $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
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
    foreach ($this->namespaces as $namespace => $directory) {
      $directory .= '/Controller';
      $namespace .= '\\Controller';
      if (is_dir($directory)) {
        $iterator = new \RecursiveIteratorIterator(
          new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileinfo) {
          if ($fileinfo->getExtension() == 'php') {
            $subPath = $iterator->getSubIterator()->getSubPath();
            $subPath = $subPath ? str_replace(DIRECTORY_SEPARATOR, '\\', $subPath) . '\\' : '';
            $class = $namespace . '\\' . $subPath . $fileinfo->getBasename('.php');
            yield $this->createRouteCollection($class);
          }
        }
      }
    }
  }

  /**
   * Creates a route collection from a class's attributed methods.
   *
   * @param class-string $className
   *   The class to generate a route collection for.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  private function createRouteCollection(string $className): RouteCollection {
    $collection = new RouteCollection();

    if (!class_exists($className)) {
      // In Symfony code this triggers an exception. It is removed here because
      // Drupal already has traits, interfaces and other things in this folder.
      // Alternatively, we could remove this if clause and then check what the
      // resulting reflection object is.
      return $collection;
    }
    $class = new \ReflectionClass($className);
    if ($class->isAbstract()) {
      return $collection;
    }

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
        $this->addRoute($collection, $attribute, $globals, $class, $method);
        if ($method->name === '__invoke') {
          $fqcnAlias = TRUE;
        }
      }

      if ($collection->count() - \count($routeNamesBefore) === 1) {
        $newRouteName = current(array_diff(array_keys($collection->all()), $routeNamesBefore));
        if ($newRouteName !== $aliasName = \sprintf('%s::%s', $class->name, $method->name)) {
          $collection->addAlias($aliasName, $newRouteName);
        }
      }
    }

    // See https://symfony.com/doc/current/controller/service.html#invokable-controllers.
    if ($collection->count() && $class->hasMethod('__invoke') === 0) {
      $globals = $this->resetGlobals();
      foreach ($this->getAttributes($class) as $attribute) {
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

      if ($attribute->schemes !== NULL) {
        $globals['schemes'] = $attribute->schemes;
      }

      if ($attribute->methods !== NULL) {
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
   * Adds a route to the provided route collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection to add the route to.
   * @param \Symfony\Component\Routing\Annotation\Route $attribute
   *   The attribute object that describes the route.
   * @param array $globals
   *   The defaults for the class.
   * @param \ReflectionClass $class
   *   The class.
   * @param \ReflectionMethod $method
   *   The attributed method.
   */
  private function addRoute(RouteCollection $collection, RouteAttribute $attribute, array $globals, \ReflectionClass $class, \ReflectionMethod $method): void {
    if ($attribute->name === NULL) {
      throw new UnsupportedRouteAttributePropertyException(sprintf('The Route attribute on "%s::%s()" is missing a required "name" property.', $class->getName(), $method->getName()));
    }
    $name = $globals['name'] . $attribute->name;

    if (is_array($attribute->path)) {
      throw new UnsupportedRouteAttributePropertyException(sprintf('The "%s" route attribute does not support arrays on route "%s" in "%s::%s()"', "path", $name, $class->getName(), $method->getName()));
    }
    if (!empty($attribute->defaults['_locale'])) {
      throw new UnsupportedRouteAttributePropertyException(sprintf('The "%s" route attribute is not supported on route "%s" in "%s::%s()"', "locale", $name, $class->getName(), $method->getName()));
    }
    if ($attribute->condition !== NULL) {
      throw new UnsupportedRouteAttributePropertyException(sprintf('The "%s" route attribute is not supported on route "%s" in "%s::%s()"', "condition", $name, $class->getName(), $method->getName()));
    }

    $requirements = $attribute->requirements;

    foreach ($requirements as $placeholder => $requirement) {
      if (\is_int($placeholder)) {
        throw new \InvalidArgumentException(sprintf('A placeholder name must be a string (%d given). Did you forget to specify the placeholder key for the requirement "%s" of route "%s" in "%s::%s()"?', $placeholder, $requirement, $name, $class->getName(), $method->getName()));
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
    $this->configureRoute($route, $class, $method);
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
  private function getAttributes(\ReflectionClass|\ReflectionMethod $reflection): iterable {
    foreach ($reflection->getAttributes(RouteAttribute::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
      yield $attribute->newInstance();
    }
  }

  /**
   * Configures the _controller default parameter of a given Route instance.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to configure.
   * @param \ReflectionClass $class
   *   The class.
   * @param \ReflectionMethod $method
   *   The method.
   */
  private function configureRoute(Route $route, \ReflectionClass $class, \ReflectionMethod $method): void {
    if ($method->getName() === '__invoke') {
      $route->setDefault('_controller', $class->getName());
    }
    else {
      $route->setDefault('_controller', $class->getName() . '::' . $method->getName());
    }
  }

}
