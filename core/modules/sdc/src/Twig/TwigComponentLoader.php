<?php

namespace Drupal\sdc\Twig;

use Drupal\Core\Utility\Error;
use Drupal\sdc\ComponentPluginManager;
use Drupal\sdc\Exception\ComponentNotFoundException;
use Drupal\Component\Discovery\YamlDirectoryDiscovery;
use Psr\Log\LoggerInterface;
use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * Lets you load templates using the component ID.
 *
 * @internal
 */
final class TwigComponentLoader implements LoaderInterface {

  /**
   * Constructs a new ComponentLoader object.
   *
   * @param \Drupal\sdc\ComponentPluginManager $pluginManager
   *   The plugin manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    protected ComponentPluginManager $pluginManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Finds a template in the file system based on the template name.
   *
   * @param string $name
   *   The template name.
   * @param bool $throw
   *   TRUE to throw an exception when the component is not found. FALSE to
   *   return NULL if the component cannot be found.
   *
   * @return string|null
   *   The path to the component.
   *
   * @throws \Twig\Error\LoaderError
   *   Thrown if a template matching $name cannot be found and $throw is TRUE.
   */
  protected function findTemplate(string $name, bool $throw = TRUE): ?string {
    $path = $name;
    try {
      $component = $this->pluginManager->find($name);
      $path = $component->getTemplatePath();
    }
    catch (ComponentNotFoundException $e) {
      if ($throw) {
        throw new LoaderError($e->getMessage(), $e->getCode(), $e);
      }
    }
    if ($path || !$throw) {
      return $path;
    }

    throw new LoaderError(sprintf('Unable to find template "%s" in the components registry.', $name));
  }

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
    catch (ComponentNotFoundException $e) {
      Error::logException($this->logger, $e);
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
    catch (ComponentNotFoundException $e) {
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
    catch (ComponentNotFoundException $e) {
      throw new LoaderError('Unable to find component');
    }
    return implode('--', array_filter([
      'sdc',
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
    catch (ComponentNotFoundException $e) {
      throw new LoaderError('Unable to find component');
    }
    // If any of the templates, or the component definition, are fresh. Then the
    // component is fresh.
    $metadata_path = $component->getPluginDefinition()[YamlDirectoryDiscovery::FILE_KEY];
    if ($file_is_fresh($metadata_path)) {
      return TRUE;
    }
    return $file_is_fresh($component->getTemplatePath());
  }

}
