<?php

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Attribute\AttributeInterface;
use Drupal\Component\Plugin\Discovery\AttributeClassDiscovery as ComponentAttributeClassDiscovery;

/**
 * Defines a discovery mechanism to find plugins using attributes.
 */
class AttributeClassDiscovery extends ComponentAttributeClassDiscovery {

  /**
   * Suffix to append to each PSR-4 directory associated with a base namespace.
   *
   * This suffix is used to form the directories where plugins are found.
   *
   * @var string
   */
  protected $directorySuffix = '';

  /**
   * A suffix to append to each base namespace.
   *
   * This suffix is used to obtain the namespaces where plugins are found.
   *
   * @var string
   */
  protected $namespaceSuffix = '';

  /**
   * Constructs an AttributeClassDiscovery object.
   *
   * @param string $subdir
   *   Either the plugin's subdirectory, for example 'Plugin/views/filter', or
   *   empty string if plugins are located at the top level of the namespace.
   * @param \Traversable $rootNamespacesIterator
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   *   If $subdir is not an empty string, it will be appended to each namespace.
   * @param string $pluginDefinitionAttributeName
   *   (optional) The name of the attribute that contains the plugin definition.
   *   Defaults to 'Drupal\Component\Plugin\Attribute\Plugin'.
   */
  public function __construct(
    string $subdir,
    protected \Traversable $rootNamespacesIterator,
    string $pluginDefinitionAttributeName = 'Drupal\Component\Plugin\Attribute\Plugin',
  ) {
    if ($subdir) {
      // Prepend a directory separator to $subdir,
      // if it does not already have one.
      if ('/' !== $subdir[0]) {
        $subdir = '/' . $subdir;
      }
      $this->directorySuffix = $subdir;
      $this->namespaceSuffix = str_replace('/', '\\', $subdir);
    }
    parent::__construct([], $pluginDefinitionAttributeName);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareAttributeDefinition(AttributeInterface $attribute, string $class): void {
    parent::prepareAttributeDefinition($attribute, $class);

    if (!$attribute->getProvider()) {
      $attribute->setProvider($this->getProviderFromNamespace($class));
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
  protected function getProviderFromNamespace(string $namespace): ?string {
    preg_match('|^Drupal\\\\(?<provider>[\w]+)\\\\|', $namespace, $matches);

    if (isset($matches['provider'])) {
      return mb_strtolower($matches['provider']);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginNamespaces(): array {
    $plugin_namespaces = [];
    if ($this->namespaceSuffix) {
      foreach ($this->rootNamespacesIterator as $namespace => $dirs) {
        // Append the namespace suffix to the base namespace, to obtain the
        // plugin namespace; for example, 'Drupal\views' may become
        // 'Drupal\views\Plugin\Block'.
        $namespace .= $this->namespaceSuffix;
        foreach ((array) $dirs as $dir) {
          // Append the directory suffix to the PSR-4 base directory, to obtain
          // the directory where plugins are found. For example,
          // DRUPAL_ROOT . '/core/modules/views/src' may become
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

  /**
   * {@inheritdoc}
   */
  protected function getClassDependencies(\ReflectionClass $reflection_class): ?array {
    if (!($dependencies = parent::getClassDependencies($reflection_class))) {
      return NULL;
    }

    // Get the providers from all the class namespaces. Exclude 'component',
    // 'core', and the provider for the plugin class itself, since none of those
    // providers will ever be missing.
    $class_provider = $this->getProviderFromNamespace($reflection_class->getName());
    $providers = [];
    foreach ($dependencies as $type_dependencies) {
      foreach ($type_dependencies as $dependency) {
        if (($provider = $this->getProviderFromNamespace($dependency)) &&
            ($provider !== $class_provider) &&
            !in_array($provider, ['component', 'core']) &&
            !in_array($provider, $providers)) {
          $providers[] = $provider;
        }
      }
    }

    if ($providers) {
      // Only need to return providers here.
      return ['provider' => $providers];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function hasMissingDependencies(array $dependencies): bool {
    if (empty($dependencies['provider'])) {
      return FALSE;
    }

    // Convert providers to two-level namespaces to check for availability.
    $dependencies['provider'] = array_map(static fn ($provider) => "Drupal\\$provider", $dependencies['provider']);
    return parent::hasMissingDependencies($dependencies);
  }

}
