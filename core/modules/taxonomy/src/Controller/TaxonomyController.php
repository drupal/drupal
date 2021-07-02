<?php

namespace Drupal\taxonomy\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Provides route responses for taxonomy.module.
 */
class TaxonomyController extends ControllerBase {

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
    $term = $this->entityTypeManager()->getStorage('taxonomy_term')->create(['vid' => $taxonomy_vocabulary->id()]);
    return $this->entityFormBuilder()->getForm($term);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $taxonomy_vocabulary
   *   The vocabulary.
   *
   * @return string
   *   The vocabulary label as a render array.
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no
   *   replacement; route title callbacks are internal.
   *
   * @see https://www.drupal.org/project/drupal/issues/3037157
   */
  public function vocabularyTitle(VocabularyInterface $taxonomy_vocabulary) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement; route title callbacks are internal. See https://www.drupal.org/project/drupal/issues/3037157.', E_USER_DEPRECATED);
    return ['#markup' => $taxonomy_vocabulary->label(), '#allowed_tags' => Xss::getHtmlTagList()];
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   The taxonomy term.
   *
   * @return array
   *   The term label as a render array.
   */
  public function termTitle(TermInterface $taxonomy_term) {
    return ['#markup' => $taxonomy_term->getName(), '#allowed_tags' => Xss::getHtmlTagList()];
  }

}
