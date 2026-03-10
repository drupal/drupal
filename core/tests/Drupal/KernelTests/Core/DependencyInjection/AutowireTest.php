<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\DependencyInjection;

use Drupal\autowire_test\TestInjection;
use Drupal\autowire_test\TestInjection2;
use Drupal\autowire_test\TestInjection3;
use Drupal\autowire_test\TestService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests auto-wiring services.
 */
#[Group('DependencyInjection')]
#[RunTestsInSeparateProcesses]
class AutowireTest extends KernelTestBase {

  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['autowire_test'];

  /**
   * Tests that 'autowire_test.service' has its dependencies injected.
   */
  public function testAutowire(): void {
    $service = $this->container->get(TestService::class);

    // Ensure an autowired interface works.
    $this->assertInstanceOf(TestInjection::class, $service->getTestInjection());
    $this->assertInstanceOf(TestInjection3::class, $service->getTestInjection3());
    // Ensure an autowired class works.
    $this->assertInstanceOf(TestInjection2::class, $service->getTestInjection2());
    // Ensure an autowired core class works.
    $this->assertInstanceOf(Connection::class, $service->getDatabase());
    // Ensure an autowired core synthetic class works.
    $this->assertInstanceOf(DrupalKernelInterface::class, $service->getKernel());
  }

  /**
   * Tests that core services have aliases correctly defined where possible.
   */
  public function testCoreServiceAliases(): void {
    $services = [];
    $aliases = [];

    foreach ($this->getCoreServiceFiles() as $filename) {
      foreach ((Yaml::decode(file_get_contents($filename))['services'] ?? []) as $id => $service) {
        if (is_string($service)) {
          $aliases[$id] = substr($service, 1);
        }
        elseif (isset($service['class']) && class_exists($service['class'])) {
          // Ignore services named by their own class.
          if ($id === $service['class']) {
            continue;
          }
          // Ignore certain tagged services.
          if (isset($service['tags'])) {
            foreach ($service['tags'] as $tag) {
              if (in_array($tag['name'], [
                'access_check',
                'cache.context',
                'context_provider',
                'event_subscriber',
              ])) {
                continue 2;
              }
            }
          }

          $services[$id] = $service['class'];
        }
      }
    }

    $interfaces = [];
    foreach (get_declared_classes() as $class) {
      // Ignore proxy classes for autowiring purposes.
      if (str_contains($class, '\\ProxyClass\\')) {
        continue;
      }

      foreach (class_implements($class) as $interface) {
        $interfaces[$interface][] = $class;
      }
    }

    $expected = [];
    foreach ($services as $id => $class) {
      // Skip services that share a class.
      if (count(array_keys($services, $class)) > 1) {
        continue;
      }

      // Skip IDs that are interfaces already.
      if (interface_exists($id)) {
        continue;
      }

      // Expect standalone classes to be aliased.
      $implements = class_implements($class);
      if (!$implements) {
        $expected[$class] = $id;
      }
      elseif (count($implements) === 1 && TrustedCallbackInterface::class === reset($implements)) {
        // Classes implementing only TrustedCallbackInterface should be aliased.
        $expected[$class] = $id;
      }

      // Expect classes that are the only implementation of their interface to
      // be aliased.
      foreach ($implements as $interface) {
        if (count($interfaces[$interface]) === 1) {
          $expected[$interface] = $id;
        }
      }
    }

    $missing = array_diff($expected, $aliases);
    $formatted = Yaml::encode(array_map(fn (int|string $alias): string => sprintf('@%s', $alias), $missing));
    $this->assertSame($expected, array_intersect($expected, $aliases), sprintf('The following core services do not have map the class name to an alias. Add the following to core.services.yml in the appropriate place: %s%s%s', \PHP_EOL, \PHP_EOL, $formatted));
  }

  /**
   * Tests that core controllers are autowired where possible.
   */
  public function testCoreControllerAutowiring(): void {
    $aliases = [];

    foreach ($this->getCoreServiceFiles() as $filename) {
      foreach ((Yaml::decode(file_get_contents($filename))['services'] ?? []) as $id => $service) {
        if (is_string($service)) {
          $aliases[$id] = substr($service, 1);
        }
      }
    }

    $controllers = [];
    foreach ($this->getCoreServiceFiles() as $filename) {
      foreach (Yaml::decode(file_get_contents($filename)) as $route) {
        if (isset($route['defaults']['_controller'])) {
          [$class] = explode('::', $route['defaults']['_controller'], 2);
          $controllers[$class] = $class;
        }
      }
    }

    $autowire = [];
    foreach ($controllers as $controller) {
      if (!is_subclass_of($controller, ControllerBase::class)) {
        continue;
      }
      if (!method_exists($controller, '__construct') || !method_exists($controller, 'create')) {
        continue;
      }
      if ((new \ReflectionClass($controller))->getMethod('create')->class !== ltrim($controller, '\\')) {
        continue;
      }
      $constructor = new \ReflectionMethod($controller, '__construct');
      foreach ($constructor->getParameters() as $parameter) {
        $interface = (string) $parameter->getType();
        if (!isset($aliases[$interface])) {
          continue 2;
        }
      }
      $autowire[] = $controller;
    }

    $this->assertEmpty($autowire, 'The following core controllers can be autowired. Remove the create() method:' . PHP_EOL . implode(PHP_EOL, $autowire));
  }

  /**
   * Tests that core services have aliases correctly defined where possible.
   */
  public function testCoreAutowiring(): void {
    $services = [];
    $aliases = [];
    foreach ($this->getCoreServiceFiles() as $filename) {
      foreach ((Yaml::decode(file_get_contents($filename))['services'] ?? []) as $id => $service) {
        if (is_string($service)) {
          $aliases[$id] = substr($service, 1);
        }
        elseif (isset($service['class']) && isset($service['arguments'])) {
          if ($filename === 'core/core.services.yml') {
            // @todo Remove this skip in https://www.drupal.org/i/3295751
            continue;
          }
          $services[$id] = $service;
        }
      }
    }

    $autowire = [];
    foreach ($services as $id => $service) {
      if (!method_exists($service['class'], '__construct')) {
        continue;
      }

      $constructor = new \ReflectionMethod($service['class'], '__construct');
      foreach ($constructor->getParameters() as $pos => $parameter) {
        $interface = (string) $parameter->getType();
        if (!isset($aliases[$interface])) {
          // There is no service to autowire.
          continue 2;
        }
        if ($aliases[$interface] !== substr($service['arguments'][$pos], 1)) {
          // The service is different.
          continue 2;
        }
      }
      $autowire[] = $id;
    }

    $this->assertEmpty($autowire, 'The following core services can be autowired. Remove their arguments from the services.yml file:' . PHP_EOL . implode(PHP_EOL, $autowire));
  }

  /**
   * Return a list of core service YAML files.
   *
   * @return string[]
   *   An array of filenames.
   */
  private function getCoreServiceFiles() {
    $filenames = array_map(fn($module) => "core/modules/{$module[0]}/{$module[0]}.services.yml", $this->coreModuleListDataProvider());
    $filenames[] = 'core/core.services.yml';
    return array_filter($filenames, 'file_exists');
  }

}
