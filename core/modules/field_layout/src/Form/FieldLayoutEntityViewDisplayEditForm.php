<?php

namespace Drupal\field_layout\Form;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\field_ui\Form\EntityViewDisplayEditForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Edit form for the EntityViewDisplay entity type.
 */
class FieldLayoutEntityViewDisplayEditForm extends EntityViewDisplayEditForm {

  use FieldLayoutEntityDisplayFormTrait;

  /**
   * FieldLayoutEntityViewDisplayEditForm constructor.
   *
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager.
   * @param \Drupal\Component\Plugin\PluginManagerBase $plugin_manager
   *   The formatter plugin manager.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   The field layout plugin manager.
   */
  public function __construct(FieldTypePluginManagerInterface $field_type_manager, PluginManagerBase $plugin_manager, LayoutPluginManagerInterface $layout_plugin_manager) {
    parent::__construct($field_type_manager, $plugin_manager);
    $this->layoutPluginManager = $layout_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('plugin.manager.core.layout')
    );
  }

}
