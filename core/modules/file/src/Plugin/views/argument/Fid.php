<?php

namespace Drupal\file\Plugin\views\argument;

use Drupal\Core\Entity\EntityManagerInterface;
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
   * The entity manager service
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a Drupal\file\Plugin\views\argument\Fid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * Override the behavior of titleQuery(). Get the filenames.
   */
  public function titleQuery() {
    $storage = $this->entityManager->getStorage('file');
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
