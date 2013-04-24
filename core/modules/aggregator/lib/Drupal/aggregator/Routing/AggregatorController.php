<?php

/**
 * @file
 * Contains \Drupal\aggregator\Routing\AggregatorController.
 */

namespace Drupal\aggregator\Routing;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\ControllerInterface;
use Drupal\Core\Entity\EntityManager;

/**
 * Returns responses for aggregator module routes.
 */
class AggregatorController implements ControllerInterface {

  /**
   * Stores the Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a \Drupal\aggregator\Routing\AggregatorController object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The Entity manager.
   */
  public function __construct(EntityManager $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.entity'));
  }

  /**
   * Presents the aggregator feed creation form.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function feedAdd() {
    $feed = $this->entityManager
      ->getStorageController('aggregator_feed')
      ->create(array(
        'refresh' => 3600,
        'block' => 5,
      ));
    return entity_get_form($feed);
  }

}
