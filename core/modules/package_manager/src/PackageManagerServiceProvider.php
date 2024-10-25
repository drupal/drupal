<?php

declare(strict_types=1);

namespace Drupal\package_manager;

use Composer\InstalledVersions;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Defines dynamic container services for Package Manager.
 *
 * Scans the Composer Stager library and registers its classes in the Drupal
 * service container.
 *
 * @todo Refactor this if/when https://www.drupal.org/i/3111008 is fixed.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class PackageManagerServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $path = InstalledVersions::getInstallPath('php-tuf/composer-stager') . '/src';

    // Certain subdirectories of Composer Stager shouldn't be scanned for
    // services.
    $ignore_directories = [
      $path . '/API/Exception',
      $path . '/Internal/Helper',
      $path . '/Internal/Path/Value',
      $path . '/Internal/Translation/Value',
    ];
    // As we scan for services, compile a list of which classes implement which
    // interfaces so that we can set up aliases for interfaces that are only
    // implemented by one class (to facilitate autowiring).
    $interfaces = [];

    // Find all `.php` files in Composer Stager which aren't in the ignored
    // directories.
    $iterator = new \RecursiveDirectoryIterator($path, \FilesystemIterator::CURRENT_AS_SELF | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS);
    $iterator = new \RecursiveCallbackFilterIterator($iterator, static function (\SplFileInfo $current) use ($ignore_directories): bool {
      if ($current->isDir()) {
        return !in_array($current->getPathname(), $ignore_directories, TRUE);
      }
      return $current->getExtension() === 'php';
    });
    $iterator = new \RecursiveIteratorIterator($iterator);

    /** @var \SplFileInfo $file */
    foreach ($iterator as $file) {
      // Convert the file name to a class name.
      $class_name = substr($file->getPathname(), strlen($path) + 1, -4);
      $class_name = 'PhpTuf\\ComposerStager\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $class_name);

      // Don't register interfaces and abstract classes as services.
      $reflector = new \ReflectionClass($class_name);
      if ($reflector->isInterface() || $reflector->isAbstract()) {
        continue;
      }
      foreach ($reflector->getInterfaceNames() as $interface) {
        $interfaces[$interface][] = $class_name;
      }
      // Register the class as an autowired, private service.
      $container->register($class_name)
        ->setClass($class_name)
        ->setAutowired(TRUE)
        ->setPublic(FALSE);
    }

    // Create aliases for interfaces that are only implemented by one class.
    // Ignore interfaces that already have a service alias.
    foreach ($interfaces as $interface_name => $implementations) {
      if (count($implementations) === 1 && !$container->hasAlias($interface_name)) {
        $container->setAlias($interface_name, $implementations[0]);
      }
    }

  }

}
