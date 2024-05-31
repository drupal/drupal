<?php

declare(strict_types=1);

namespace Drupal\taxonomy_test\Plugin\views\argument;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\taxonomy\Plugin\views\argument\Taxonomy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test argument handler for testing deprecation in Taxonomy argument plugin.
 *
 * Intentionally setup our properties and constructor as Drupal 10.2.x and
 * earlier used in the Taxonomy argument handler.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("taxonomy_views_argument_test")
 */
class TaxonomyViewsArgumentTest extends Taxonomy {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityStorageInterface $termStorage,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $termStorage);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('taxonomy_term')
    );
  }

}
