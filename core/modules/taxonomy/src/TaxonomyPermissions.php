<?php

namespace Drupal\taxonomy;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the taxonomy module.
 *
 * @see taxonomy.permissions.yml
 */
class TaxonomyPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a TaxonomyPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * Get taxonomy permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];
    foreach (Vocabulary::loadMultiple() as $vocabulary) {
      $permissions += $this->buildPermissions($vocabulary);
    }
    return $permissions;
  }

  /**
   * Builds a standard list of taxonomy term permissions for a given vocabulary.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   The vocabulary.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(VocabularyInterface $vocabulary) {
    $id = $vocabulary->id();
    $args = ['%vocabulary' => $vocabulary->label()];

    return [
      "create terms in $id" => ['title' => $this->t('%vocabulary: Create terms', $args)],
      "delete terms in $id" => ['title' => $this->t('%vocabulary: Delete terms', $args)],
      "edit terms in $id" => ['title' => $this->t('%vocabulary: Edit terms', $args)],
    ];
  }

}
