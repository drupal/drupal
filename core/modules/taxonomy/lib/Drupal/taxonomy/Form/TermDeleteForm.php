<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Form\TermDeleteForm.
 */

namespace Drupal\taxonomy\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\VocabularyStorageControllerInterface;
use Drupal\Core\Entity\EntityNGConfirmFormBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a deletion confirmation form for taxonomy term.
 */
class TermDeleteForm extends EntityNGConfirmFormBase {

  /**
   * The taxonomy vocabulary storage controller.
   *
   * @var \Drupal\taxonomy\VocabularyStorageControllerInterface
   */
  protected $vocabularyStorageController;

  /**
   * Constructs a new TermDelete object.
   *
   * @param \Drupal\taxonomy\VocabularyStorageControllerInterface $storage_controller
   *   The Entity manager.
   */
  public function __construct(VocabularyStorageControllerInterface $storage_controller) {
    $this->vocabularyStorageController = $storage_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorageController('taxonomy_vocabulary')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'taxonomy_term_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the term %title?', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/structure/taxonomy';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a term will delete all its children if there are any. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    $vocabulary = $this->vocabularyStorageController->load($this->entity->bundle());

    // @todo Move to storage controller http://drupal.org/node/1988712
    taxonomy_check_vocabulary_hierarchy($vocabulary, array('tid' => $this->entity->id()));

    drupal_set_message($this->t('Deleted term %name.', array('%name' => $this->entity->label())));
    watchdog('taxonomy', 'Deleted term %name.', array('%name' => $this->entity->label()), WATCHDOG_NOTICE);
    $form_state['redirect'] = 'admin/structure/taxonomy';
    Cache::invalidateTags(array('content' => TRUE));
  }

}
