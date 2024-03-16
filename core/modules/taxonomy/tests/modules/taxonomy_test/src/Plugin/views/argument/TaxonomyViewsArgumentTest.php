<?php

declare(strict_types=1);

namespace Drupal\taxonomy_test\Plugin\views\argument;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\taxonomy\Plugin\views\argument\Taxonomy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test argument handler for testing deprecation in IndexTidDepth plugin.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("taxonomy_views_argument_test")
 */
class TaxonomyViewsArgumentTest extends Taxonomy {

  /**
   * Constructs new IndexTidDepthTestPlugin object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $term_storage, protected EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $term_storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
      $container->get('entity_type.bundle.info'),
    );
  }

}
