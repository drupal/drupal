<?php

declare(strict_types=1);

namespace Drupal\taxonomy_overview_override_test\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Form\OverviewTerms;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Provides an overview form to test overriding it via hook_entity_type_alter.
 */
class OverviewTermsOverride extends OverviewTerms {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?VocabularyInterface $taxonomy_vocabulary = NULL) {
    $form = parent::buildForm($form, $form_state, $taxonomy_vocabulary);
    $form['terms']['#empty'] = $this->t('No unicorns here, only llamas.');
    return $form;
  }

}
