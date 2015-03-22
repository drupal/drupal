<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\views\argument\Iid.
 */

namespace Drupal\aggregator\Plugin\views\argument;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Drupal\Component\Utility\String;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept an aggregator item id.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("aggregator_iid")
 */
class Iid extends NumericArgument {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
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
    $titles = array();

    $items = $this->entityManager->getStorage('aggregator_item')->loadMultiple($this->value);
    foreach ($items as $feed) {
      $titles[] = String::checkPlain($feed->label());
    }
    return $titles;
  }

}
