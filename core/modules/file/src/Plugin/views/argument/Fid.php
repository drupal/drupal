<?php

namespace Drupal\file\Plugin\views\argument;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept multiple file ids.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("file_fid")
 */
class Fid extends NumericArgument implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Drupal\file\Plugin\views\argument\Fid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Override the behavior of titleQuery(). Get the filenames.
   */
  public function titleQuery() {
    $storage = $this->entityTypeManager->getStorage('file');
    $fids = $storage->getQuery()
      ->condition('fid', $this->value, 'IN')
      ->execute();
    $files = $storage->loadMultiple($fids);
    $titles = [];
    foreach ($files as $file) {
      $titles[] = $file->getFilename();
    }
    return $titles;
  }

}
