<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Controller\TaxonomyController.
 */

namespace Drupal\taxonomy\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\taxonomy\TermStorageControllerInterface;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for taxonomy.module.
 */
class TaxonomyController implements ControllerInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\taxonomy\TermStorageControllerInterface
   */
  protected $termStorage;

  /**
   * Constructs a new TaxonomyController.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Drupal\taxonomy\TermStorageControllerInterface $term_storage
   *   The taxonomy term storage.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityManager $entity_manager, TermStorageControllerInterface $term_storage) {
    $this->moduleHandler = $module_handler;
    $this->entityManager = $entity_manager;
    $this->termStorage = $term_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('plugin.manager.entity');
    return new static(
      $container->get('module_handler'),
      $entity_manager,
      $entity_manager->getStorageController('taxonomy_term')
    );
  }

  /**
   * Returns a rendered edit form to create a new term associated to the given vocabulary.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $taxonomy_vocabulary
   *   The vocabulary this term will be added to.
   *
   * @return array
   *   The taxonomy term add form.
   */
  public function addForm(VocabularyInterface $taxonomy_vocabulary) {
    $term = $this->termStorage->create(array('vid' => $taxonomy_vocabulary->id()));
    if ($this->moduleHandler->moduleExists('language')) {
      $term->langcode = language_get_default_langcode('taxonomy_term', $taxonomy_vocabulary->id());
    }
    return $this->entityManager->getForm($term);
  }

}
