<?php

namespace Drupal\content_moderation\Entity\Handler;

use Drupal\Core\Form\FormStateInterface;

/**
 * Customizations for taxonomy term entities.
 *
 * @internal
 */
class TaxonomyTermModerationHandler extends ModerationHandler {

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsEntityFormAlter(array &$form, FormStateInterface $form_state, $form_id): void {
    $form['revision']['#default_value'] = TRUE;
    $form['revision']['#disabled'] = TRUE;
    $form['revision']['#description'] = $this->t('Revisions must be required when moderation is enabled.');
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsBundleFormAlter(array &$form, FormStateInterface $form_state, $form_id): void {
    $form['revision']['#default_value'] = TRUE;
    $form['revision']['#disabled'] = TRUE;
    $form['revision']['#description'] = $this->t('Revisions must be required when moderation is enabled.');
  }

}
