<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery.
 */

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Annotation\AnnotationInterface;
use Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery as ComponentAnnotatedClassDiscovery;

/**
 * Defines a discovery mechanism to find annotated plugins in PSR-0 namespaces.
 */
class AnnotatedClassDiscovery extends ComponentAnnotatedClassDiscovery {

  /**
   * The subdirectory within a namespace to look for plugins.
   *
   * If the plugins are in the top level of the namespace and not within a
   * subdirectory, set this to an empty string.
   *
   * @var string
   */
  protected $subdir = '';

  /**
   * An object containing the namespaces to look for plugin implementations.
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
      $this->subdir = str_replace('/', '\\', $subdir);
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
      $reader->addNamespace('Drupal\Core\Annotation', array(DRUPAL_ROOT . '/core/lib/Drupal/Core/Annotation'));
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
      return $matches['provider'];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginNamespaces() {
    $plugin_namespaces = array();
    foreach ($this->rootNamespacesIterator as $namespace => $dir) {
      if ($this->subdir) {
        $namespace .= "\\{$this->subdir}";
      }
      $plugin_namespaces[$namespace] = array($dir);
    }

    return $plugin_namespaces;
  }

}
