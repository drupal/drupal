<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Form\VocabularyReset.
 */

namespace Drupal\taxonomy\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\taxonomy\TermStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides confirmation form for resetting a vocabulary to alphabetical order.
 */
class VocabularyResetForm extends EntityConfirmFormBase {

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Constructs a new VocabularyResetForm object.
   *
   * @param \Drupal\taxonomy\TermStorageInterface $term_storage
   *   The term storage.
   */
  public function __construct(TermStorageInterface $term_storage) {
    $this->termStorage = $term_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('taxonomy_term')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_vocabulary_confirm_reset_alphabetical';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reset the vocabulary %title to alphabetical order?', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return $this->entity->urlInfo('overview-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Resetting a vocabulary will discard all custom ordering and sort items alphabetically.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reset to alphabetical');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $this->termStorage->resetWeights($this->entity->id());
    drupal_set_message($this->t('Reset vocabulary %name to alphabetical order.', array('%name' => $this->entity->label())));
    watchdog('taxonomy', 'Reset vocabulary %name to alphabetical order.', array('%name' => $this->entity->label()), WATCHDOG_NOTICE);
    $form_state['redirect_route'] = $this->entity->urlInfo('edit-form');
  }

}
