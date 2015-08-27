<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\migrate\builder\d7\User.
 */

namespace Drupal\user\Plugin\migrate\builder\d7;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\migrate\builder\BuilderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @PluginID("d7_user")
 */
class User extends BuilderBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a d7_user builder plugin instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildMigrations(array $template) {
    $migration = Migration::create($template);

    if ($this->moduleHandler->moduleExists('field')) {
      $template['source']['entity_type'] = 'user';

      $source_plugin = $this->getSourcePlugin('d7_field_instance', $template['source']);
      foreach ($source_plugin as $field) {
        $field_name = $field->getSourceProperty('field_name');
        $migration->setProcessOfProperty($field_name, $field_name);
      }
    }

    try {
      $profile_fields = $this->getSourcePlugin('profile_field', $template['source']);
      // Ensure that Profile is enabled in the source DB.
      $profile_fields->checkRequirements();
      foreach ($profile_fields as $field) {
        $field_name = $field->getSourceProperty('name');
        $migration->setProcessOfProperty($field_name, $field_name);
      }
    }
    catch (RequirementsException $e) {
      // Profile is not enabled in the source DB, so don't do anything.
    }

    return [$migration];
  }

}
