<?php

/**
 * @file
 * Definition of Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery.
 */

namespace Drupal\Core\Plugin\Discovery;

use DirectoryIterator;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Reflection\MockFileFinder;
use Drupal\Core\Annotation\Plugin;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Reflection\StaticReflectionParser;

/**
 * Defines a discovery mechanism to find annotated plugins in PSR-0 namespaces.
 */
class AnnotatedClassDiscovery implements DiscoveryInterface {

  /**
   * Constructs an AnnotatedClassDiscovery object.
   */
  function __construct($owner, $type) {
    $this->owner = $owner;
    $this->type = $type;
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinition().
   */
  public function getDefinition($plugin_id) {
    $plugins = $this->getDefinitions();
    return isset($plugins[$plugin_id]) ? $plugins[$plugin_id] : NULL;
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinitions().
   */
  public function getDefinitions() {
    $definitions = array();
    $reader = new AnnotationReader();

    // Register the namespace of classes that can be used for annotations.
    AnnotationRegistry::registerAutoloadNamespace('Drupal\Core\Annotation', array(DRUPAL_ROOT . '/core/lib'));
    // Get all PSR-0 namespaces.
    $namespaces = drupal_classloader()->getNamespaces();
    foreach ($namespaces as $ns => $namespace_dirs) {

      // OS-Safe directory separators.
      $ns = str_replace('\\', DIRECTORY_SEPARATOR, $ns);

      foreach ($namespace_dirs as $dir) {
        // Check for the pre-determined directory structure to find plugins.
        $prefix = implode(DIRECTORY_SEPARATOR, array(
          $ns,
          'Plugin',
          $this->owner,
          $this->type
        ));
        $dir .= DIRECTORY_SEPARATOR . $prefix;

        // If the directory structure exists, look for classes.
        if (file_exists($dir)) {
          $directories = new DirectoryIterator($dir);
          foreach ($directories as $fileinfo) {
            // @todo Once core requires 5.3.6, use $fileinfo->getExtension().
            if (pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION) == 'php') {
              $class = str_replace(
                DIRECTORY_SEPARATOR,
                '\\',
                $prefix . DIRECTORY_SEPARATOR . $fileinfo->getBasename('.php')
              );

              // The filename is already known, so there is no need to find the
              // file. However, StaticReflectionParser needs a finder, so use a
              // mock version.
              $finder = MockFileFinder::create($fileinfo->getPathName());
              $parser = new StaticReflectionParser($class, $finder);

              if ($annotation = $reader->getClassAnnotation($parser->getReflectionClass(), 'Drupal\Core\Annotation\Plugin')) {
                // AnnotationInterface::get() returns the array definition
                // instead of requiring us to work with the annotation object.
                $definition = $annotation->get();
                $definition['class'] = $class;
                $definitions[$definition['id']] = $definition;
              }
            }
          }
        }
      }
    }
    return $definitions;
  }

}
