<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a form to confirm the removal of a section.
 *
 * @internal
 *   Form classes are internal.
 */
class RemoveSectionForm extends LayoutRebuildConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_remove_section';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $configuration = $this->sectionStorage->getSection($this->delta)->getLayoutSettings();
    // Layouts may choose to use a class that might not have a label
    // configuration.
    if (!empty($configuration['label'])) {
      return $this->t('Are you sure you want to remove @section?', ['@section' => $configuration['label']]);
    }
    return $this->t('Are you sure you want to remove section @section?', ['@section' => $this->delta + 1]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  protected function handleSectionStorage(SectionStorageInterface $section_storage, FormStateInterface $form_state) {
    $section_storage->removeSection($this->delta);
  }

}
