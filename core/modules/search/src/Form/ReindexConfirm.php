<?php

/**
 * @file
 * Contains \Drupal\search\Form\ReindexConfirm.
 */

namespace Drupal\search\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the search reindex confirmation form.
 */
class ReindexConfirm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_reindex_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to re-index the site?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("This will re-index content in the search indexes of all active search pages. Searching will continue to work, but new content won't be indexed until all existing content has been re-indexed. This action cannot be undone.");
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Re-index site');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.search_page.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form['confirm']) {
      // Ask each active search page to mark itself for re-index.
      $search_page_repository = \Drupal::service('search.search_page_repository');
      foreach ($search_page_repository->getIndexableSearchPages() as $entity) {
        $entity->getPlugin()->markForReindex();
      }
      drupal_set_message($this->t('All search indexes will be rebuilt.'));
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
  }
}
