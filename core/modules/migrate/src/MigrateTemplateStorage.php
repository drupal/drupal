<?php

/**
 * @file
 * Contains \Drupal\migrate\MigrateTemplateStorage.
 */

namespace Drupal\migrate;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Storage to access migration template configuration in enabled extensions.
 */
class MigrateTemplateStorage {

  /**
   * Extension sub-directory containing default configuration for migrations.
   */
  const MIGRATION_TEMPLATE_DIRECTORY = 'migration_templates';

  /**
   * Template subdirectory.
   *
   * @var string
   */
  protected $directory;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleHandlerInterface $module_handler, $directory = self::MIGRATION_TEMPLATE_DIRECTORY) {
    $this->moduleHandler = $module_handler;
    $this->directory = $directory;
  }

  /**
   * Find all migration templates with the specified tag.
   *
   * @param $tag
   *   The tag to match.
   *
   * @return array
   *   Any templates (parsed YAML config) that matched, keyed by the ID.
   */
  public function findTemplatesByTag($tag) {
    $templates = $this->getAllTemplates();
    $matched_templates = [];
    foreach ($templates as $template_name => $template) {
      if (!empty($template['migration_tags'])) {
        if (in_array($tag, $template['migration_tags'])) {
          $matched_templates[$template_name] = $template;
        }
      }
    }
    return $matched_templates;
  }

  /**
   * Retrieves all migration templates belonging to enabled extensions.
   *
   * @return array
   *   Array of parsed templates, keyed by the fully-qualified id.
   */
  public function getAllTemplates() {

    $templates = [];
    foreach ($this->moduleHandler->getModuleDirectories() as $directory) {
      $full_directory = $directory . '/' . $this->directory;
      if (file_exists($full_directory)) {
        $files = scandir($full_directory);
        foreach ($files as $file) {
          if ($file[0] !== '.' && fnmatch('*.yml', $file)) {
            $templates[basename($file, '.yml')] = Yaml::decode(file_get_contents("$full_directory/$file"));
          }
        }
      }
    }

    return $templates;
  }

}
