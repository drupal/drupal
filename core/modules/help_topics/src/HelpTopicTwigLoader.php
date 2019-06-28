<?php

namespace Drupal\help_topics;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

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
 *   Help Topic is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class HelpTopicTwigLoader extends \Twig_Loader_Filesystem {

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
    // namespace.
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

}
