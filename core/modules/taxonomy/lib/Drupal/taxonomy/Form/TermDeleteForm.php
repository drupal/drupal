<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Form\TermDeleteForm.
 */

namespace Drupal\taxonomy\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a deletion confirmation form for taxonomy term.
 */
class TermDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_term_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the term %title?', array('%title' => $this->entity->getName()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'taxonomy.vocabulary_list',
    );
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
    $storage_controller = $this->entityManager->getStorageController('taxonomy_vocabulary');
    $vocabulary = $storage_controller->load($this->entity->bundle());

    // @todo Move to storage controller http://drupal.org/node/1988712
    taxonomy_check_vocabulary_hierarchy($vocabulary, array('tid' => $this->entity->id()));

    drupal_set_message($this->t('Deleted term %name.', array('%name' => $this->entity->getName())));
    watchdog('taxonomy', 'Deleted term %name.', array('%name' => $this->entity->getName()), WATCHDOG_NOTICE);
    $form_state['redirect_route']['route_name'] = 'taxonomy.vocabulary_list';
    Cache::invalidateTags(array('content' => TRUE));
  }

}
