<?php

namespace Drupal\node\Plugin\views\argument;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\views\Attribute\ViewsArgument;
use Drupal\views\Plugin\views\argument\StringArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a node type.
 */
#[ViewsArgument(
  id: 'node_type',
)]
class Type extends StringArgument {

  /**
   * NodeType storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeTypeStorage;

  /**
   * Constructs a new Node Type object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_type_storage
   *   The entity storage class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $node_type_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->nodeTypeStorage = $node_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager->getStorage('node_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summaryName($data) {
    return $this->node_type($data->{$this->name_alias});
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    return $this->node_type($this->argument);
  }

  /**
   * Returns the label for the given node type.
   */
  public function node_type($type_name) {
    $type = $this->nodeTypeStorage->load($type_name);
    $output = $type ? $type->label() : $this->t('Unknown content type');
    return $output;
  }

}
