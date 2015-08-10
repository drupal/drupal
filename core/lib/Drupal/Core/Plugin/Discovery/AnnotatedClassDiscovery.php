<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery.
 */

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Annotation\AnnotationInterface;
use Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery as ComponentAnnotatedClassDiscovery;
use Drupal\Component\Utility\Unicode;

/**
 * Defines a discovery mechanism to find annotated plugins in PSR-0 namespaces.
 */
class AnnotatedClassDiscovery extends ComponentAnnotatedClassDiscovery {

  /**
   * A suffix to append to each PSR-4 directory associated with a base
   * namespace, to form the directories where plugins are found.
   *
   * @var string
   */
  protected $directorySuffix = '';

  /**
   * A suffix to append to each base namespace, to obtain the namespaces where
   * plugins are found.
   *
   * @var string
   */
  protected $namespaceSuffix = '';

  /**
   * A list of base namespaces with their PSR-4 directories.
   *
   * @var \Traversable
   */
  protected $rootNamespacesIterator;

  /**
   * Constructs an AnnotatedClassDiscovery object.
   *
   * @param string $subdir
   *   Either the plugin's subdirectory, for example 'Plugin/views/filter', or
   *   empty string if plugins are located at the top level of the namespace.
   * @param \Traversable $root_namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   *   If $subdir is not an empty string, it will be appended to each namespace.
   * @param string $plugin_definition_annotation_name
   *   (optional) The name of the annotation that contains the plugin definition.
   *   Defaults to 'Drupal\Component\Annotation\Plugin'.
   */
  function __construct($subdir, \Traversable $root_namespaces, $plugin_definition_annotation_name = 'Drupal\Component\Annotation\Plugin') {
    if ($subdir) {
      // Prepend a directory separator to $subdir,
      // if it does not already have one.
      if ('/' !== $subdir[0]) {
        $subdir = '/' . $subdir;
      }
      $this->directorySuffix = $subdir;
      $this->namespaceSuffix = str_replace('/', '\\', $subdir);
    }
    $this->rootNamespacesIterator = $root_namespaces;
    $plugin_namespaces = array();
    parent::__construct($plugin_namespaces, $plugin_definition_annotation_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAnnotationReader() {
    if (!isset($this->annotationReader)) {
      $reader = parent::getAnnotationReader();

      // Add the Core annotation classes like @Translation.
      $reader->addNamespace('Drupal\Core\Annotation');
      $this->annotationReader = $reader;
    }
    return $this->annotationReader;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareAnnotationDefinition(AnnotationInterface $annotation, $class) {
    parent::prepareAnnotationDefinition($annotation, $class);

    if (!$annotation->getProvider()) {
      $annotation->setProvider($this->getProviderFromNamespace($class));
    }
  }

  /**
   * Extracts the provider name from a Drupal namespace.
   *
   * @param string $namespace
   *   The namespace to extract the provider from.
   *
   * @return string|null
   *   The matching provider name, or NULL otherwise.
   */
  protected function getProviderFromNamespace($namespace) {
    preg_match('|^Drupal\\\\(?<provider>[\w]+)\\\\|', $namespace, $matches);

    if (isset($matches['provider'])) {
      return Unicode::strtolower($matches['provider']);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginNamespaces() {
    $plugin_namespaces = array();
    if ($this->namespaceSuffix) {
      foreach ($this->rootNamespacesIterator as $namespace => $dirs) {
        // Append the namespace suffix to the base namespace, to obtain the
        // plugin namespace. E.g. 'Drupal\Views' may become
        // 'Drupal\Views\Plugin\Block'.
        $namespace .= $this->namespaceSuffix;
        foreach ((array) $dirs as $dir) {
          // Append the directory suffix to the PSR-4 base directory, to obtain
          // the directory where plugins are found.
          // E.g. DRUPAL_ROOT . '/core/modules/views/src' may become
          // DRUPAL_ROOT . '/core/modules/views/src/Plugin/Block'.
          $plugin_namespaces[$namespace][] = $dir . $this->directorySuffix;
        }
      }
    }
    else {
      // Both the namespace suffix and the directory suffix are empty,
      // so the plugin namespaces and directories are the same as the base
      // directories.
      foreach ($this->rootNamespacesIterator as $namespace => $dirs) {
        $plugin_namespaces[$namespace] = (array) $dirs;
      }
    }

    return $plugin_namespaces;
  }

}
