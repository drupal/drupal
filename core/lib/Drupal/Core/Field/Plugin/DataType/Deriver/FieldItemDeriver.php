<?php

namespace Drupal\Core\Field\Plugin\DataType\Deriver;

use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides data type plugins for each existing field type plugin.
 */
class FieldItemDeriver implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * Constructs a FieldItemDeriver object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   */
  public function __construct($base_plugin_id, FieldTypePluginManagerInterface $field_type_plugin_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $this->getDerivativeDefinitions($base_plugin_definition);
    }
    if (isset($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->fieldTypePluginManager->getDefinitions() as $plugin_id => $definition) {
      $definition['definition_class'] = '\Drupal\Core\Field\TypedData\FieldItemDataDefinition';
      $definition['list_definition_class'] = '\Drupal\Core\Field\BaseFieldDefinition';
      $definition['unwrap_for_canonical_representation'] = FALSE;
      $this->derivatives[$plugin_id] = $definition;
    }
    return $this->derivatives;
  }

}
