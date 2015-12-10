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
class MigrateTemplateStorage implements MigrateTemplateStorageInterface {
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getTemplateByName($name) {
    $templates = $this->getAllTemplates();
    return isset($templates[$name]) ? $templates[$name] : NULL;
  }

  /**
   * {@inheritdoc}
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
