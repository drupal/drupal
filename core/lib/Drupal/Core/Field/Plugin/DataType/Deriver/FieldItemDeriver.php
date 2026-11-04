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
   * Constructs a FieldItemDeriver object.
   *
   * @param string $basePluginId
   *   The base plugin ID.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $fieldTypePluginManager
   *   The field type plugin manager.
   */
  public function __construct(
    protected $basePluginId,
    protected FieldTypePluginManagerInterface $fieldTypePluginManager,
  ) {
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
