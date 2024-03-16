<?php

namespace Drupal\taxonomy\Plugin\views\argument;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
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
   * @var \Drupal\Core\Entity\EntityStorageInterface
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3427843
   */
  protected $termStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected EntityStorageInterface|EntityRepositoryInterface $entityRepository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if ($entityRepository instanceof EntityStorageInterface) {
      // @phpstan-ignore-next-line
      $this->termStorage = $this->entityRepository;
      @trigger_error('Calling ' . __CLASS__ . '::__construct() with the $termStorage argument as \Drupal\Core\Entity\EntityStorageInterface is deprecated in drupal:10.3.0 and it will require Drupal\Core\Entity\EntityRepositoryInterface in drupal:11.0.0. See https://www.drupal.org/node/3427843', E_USER_DEPRECATED);
      $this->entityRepository = \Drupal::service('entity.repository');
    }
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
