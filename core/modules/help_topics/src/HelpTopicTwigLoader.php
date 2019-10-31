<?php

namespace Drupal\help_topics;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Serialization\Yaml;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\Source;

/**
 * Loads help topic Twig files from the filesystem.
 *
 * This loader adds module and theme help topic paths to a help_topics namespace
 * to the Twig filesystem loader so that help_topics can be referenced, using
 * '@help-topic/pluginId.html.twig'.
 *
 * @see \Drupal\help_topics\HelpTopicDiscovery
 * @see \Drupal\help_topics\HelpTopicTwig
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class HelpTopicTwigLoader extends FilesystemLoader {

  /**
   * {@inheritdoc}
   */
  const MAIN_NAMESPACE = 'help_topics';

  /**
   * Constructs a new HelpTopicTwigLoader object.
   *
   * @param string $root_path
   *   The root path.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler service.
   */
  public function __construct($root_path, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    parent::__construct([], $root_path);
    // Add help_topics directories for modules and themes in the 'help_topic'
    // namespace, plus core.
    $this->addExtension($root_path . '/core');
    array_map([$this, 'addExtension'], $module_handler->getModuleDirectories());
    array_map([$this, 'addExtension'], $theme_handler->getThemeDirectories());
  }

  /**
   * Adds an extensions help_topics directory to the Twig loader.
   *
   * @param $path
   *   The path to the extension.
   */
  protected function addExtension($path) {
    $path .= DIRECTORY_SEPARATOR . 'help_topics';
    if (is_dir($path)) {
      $this->cache = $this->errorCache = [];
      $this->paths[self::MAIN_NAMESPACE][] = rtrim($path, '/\\');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceContext($name) {
    $path = $this->findTemplate($name);

    $contents = file_get_contents($path);
    try {
      // Note: always use \Drupal\Core\Serialization\Yaml here instead of the
      // "serializer.yaml" service. This allows the core serializer to utilize
      // core related functionality which isn't available as the standalone
      // component based serializer.
      $front_matter = FrontMatter::load($contents, Yaml::class);

      // Reconstruct the content if there is front matter data detected. Prepend
      // the source with {% line \d+ %} to inform Twig that the source code
      // actually starts on a different line past the front matter data. This is
      // particularly useful when used in error reporting.
      if ($front_matter->getData() && ($line = $front_matter->getLine())) {
        $contents = "{% line $line %}" . $front_matter->getCode();
      }
    }
    catch (InvalidDataTypeException $e) {
      throw new LoaderError(sprintf('Malformed YAML in help topic "%s": %s.', $path, $e->getMessage()));
    }

    return new Source($contents, $name, $path);
  }

}
