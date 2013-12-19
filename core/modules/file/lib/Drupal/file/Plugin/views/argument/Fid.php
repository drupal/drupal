<?php

/**
 * @file
 * Definition of Drupal\file\Plugin\views\argument\Fid.
 */

namespace Drupal\file\Plugin\views\argument;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Component\Utility\String;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept multiple file ids.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("file_fid")
 */
class Fid extends Numeric implements ContainerFactoryPluginInterface {

  /**
   * The entity manager service
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructs a Drupal\file\Plugin\views\argument\Fid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory
   *   The entity query factory.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityManagerInterface $entity_manager, QueryFactory $entity_query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('entity.query')
    );
  }

  /**
   * Override the behavior of titleQuery(). Get the filenames.
   */
  public function titleQuery() {
    $fids = $this->entityQuery->get('file')
      ->condition('fid', $this->value)
      ->execute();
    $controller = $this->entityManager->getStorageController('file');
    $files = $controller->loadMultiple($fids);
    $titles = array();
    foreach ($files as $file) {
      $titles[] = String::checkPlain($file->getFilename());
    }
    return $titles;
  }

}
