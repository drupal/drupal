<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Common functionality for many CKEditor 5 validation constraints.
 *
 * @internal
 */
trait PluginManagerDependentValidatorTrait {

  /**
   * The CKEditor 5 plugin manager.
   *
   * @var \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a CKEditor5ConstraintValidatorTrait object.
   *
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface $plugin_manager
   *   The CKEditor 5 plugin manager.
   */
  public function __construct(CKEditor5PluginManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.ckeditor5.plugin')
    );
  }

}
