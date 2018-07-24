<?php

namespace Drupal\aggregator\Plugin\views\argument;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept an aggregator feed id.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("aggregator_fid")
 */
class Fid extends NumericArgument {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a \Drupal\aggregator\Plugin\views\argument\Fid object.
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
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function titleQuery() {
    $titles = [];

    $feeds = $this->entityManager->getStorage('aggregator_feed')->loadMultiple($this->value);
    foreach ($feeds as $feed) {
      $titles[] = $feed->label();
    }
    return $titles;
  }

}
