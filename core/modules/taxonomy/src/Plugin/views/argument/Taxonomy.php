<?php

namespace Drupal\taxonomy\Plugin\views\argument;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Attribute\ViewsArgument;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler for basic taxonomy tid.
 *
 * @ingroup views_argument_handlers
 */
#[ViewsArgument(
  id: 'taxonomy',
)]
class Taxonomy extends NumericArgument implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected EntityRepositoryInterface $entityRepository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  public function title() {
    // There might be no valid argument.
    if ($this->argument) {
      $term = $this->entityRepository->getCanonical('taxonomy_term', $this->argument);
      if (!empty($term)) {
        return $term->label();
      }
    }
    // TODO review text
    return $this->t('No name');
  }

}
