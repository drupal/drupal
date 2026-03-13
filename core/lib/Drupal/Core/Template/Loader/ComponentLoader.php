<?php

namespace Drupal\Core\Template\Loader;

use Drupal\Component\Discovery\YamlDirectoryDiscovery;
use Drupal\Core\Render\Component\Exception\ComponentNotFoundException;
use Drupal\Core\Theme\ComponentPluginManager;
use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * Lets you load templates using the component ID.
 */
class ComponentLoader implements LoaderInterface {

  /**
   * Constructs a new ComponentLoader object.
   *
   * @param \Drupal\Core\Theme\ComponentPluginManager $pluginManager
   *   The plugin manager.
   */
  public function __construct(
    protected ComponentPluginManager $pluginManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function exists($name): bool {
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9:_-]*[a-zA-Z0-9]?$/', $name)) {
      return FALSE;
    }
    try {
      $this->pluginManager->find($name);
      return TRUE;
    }
    catch (ComponentNotFoundException) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceContext($name): Source {
    try {
      $component = $this->pluginManager->find($name);
      $path = $component->getTemplatePath();
    }
    catch (ComponentNotFoundException) {
      return new Source('', $name, '');
    }
    $original_code = file_get_contents($path);
    return new Source($original_code, $name, $path);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKey($name): string {
    try {
      $component = $this->pluginManager->find($name);
    }
    catch (ComponentNotFoundException) {
      throw new LoaderError('Unable to find component');
    }
    return implode('--', array_filter([
      'components',
      $name,
      $component->getPluginDefinition()['provider'] ?? '',
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function isFresh(string $name, int $time): bool {
    $file_is_fresh = static fn(string $path) => filemtime($path) < $time;
    try {
      $component = $this->pluginManager->find($name);
    }
    catch (ComponentNotFoundException) {
      throw new LoaderError('Unable to find component');
    }
    $metadata_path = $component->getPluginDefinition()[YamlDirectoryDiscovery::FILE_KEY];
    return $file_is_fresh($component->getTemplatePath()) && $file_is_fresh($metadata_path);
  }

}
