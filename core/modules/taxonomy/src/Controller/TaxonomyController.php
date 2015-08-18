<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Controller\TaxonomyController.
 */

namespace Drupal\taxonomy\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Provides route responses for taxonomy.module.
 */
class TaxonomyController extends ControllerBase {

  /**
   * Title callback for term pages.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A taxonomy term entity.
   *
   * @return
   *   The term name to be used as the page title.
   */
  public function getTitle(TermInterface $term) {
    return $term->label();
  }

  /**
   * Returns a form to add a new term to a vocabulary.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $taxonomy_vocabulary
   *   The vocabulary this term will be added to.
   *
   * @return array
   *   The taxonomy term add form.
   */
  public function addForm(VocabularyInterface $taxonomy_vocabulary) {
    $term = $this->entityManager()->getStorage('taxonomy_term')->create(array('vid' => $taxonomy_vocabulary->id()));
    return $this->entityFormBuilder()->getForm($term);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $taxonomy_vocabulary
   *   The taxonomy term.
   *
   * @return string
   *   The term label.
   */
  public function vocabularyTitle(VocabularyInterface $taxonomy_vocabulary) {
    return SafeMarkup::xssFilter($taxonomy_vocabulary->label());
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   The taxonomy term.
   *
   * @return string
   *   The term label.
   */
  public function termTitle(TermInterface $taxonomy_term) {
    return SafeMarkup::xssFilter($taxonomy_term->getName());
  }

}
