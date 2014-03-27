<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Controller\TaxonomyController.
 */

namespace Drupal\taxonomy\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for taxonomy.module.
 */
class TaxonomyController extends ControllerBase {

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
    $term = $this->entityManager()->getStorage('taxonomy_term')->create(array('vid' => $taxonomy_vocabulary->id()));
    if ($this->moduleHandler()->moduleExists('language')) {
      $term->langcode = language_get_default_langcode('taxonomy_term', $taxonomy_vocabulary->id());
    }
    return $this->entityFormBuilder()->getForm($term);
  }

  /**
   * @todo Remove taxonomy_term_page().
   */
  public function termPage(TermInterface $taxonomy_term) {
    module_load_include('pages.inc', 'taxonomy');
    return taxonomy_term_page($taxonomy_term);

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
    return Xss::filter($taxonomy_vocabulary->label());
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
    return Xss::filter($taxonomy_term->getName());
  }

  /**
   * @todo Remove taxonomy_term_feed().
   */
  public function termFeed(TermInterface $taxonomy_term) {
    module_load_include('pages.inc', 'taxonomy');
    return taxonomy_term_feed($taxonomy_term);
  }

}
