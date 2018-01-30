<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a form to add a block.
 *
 * @internal
 */
class AddBlockForm extends ConfigureBlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_add_block';
  }

  /**
   * {@inheritdoc}
   */
  protected function submitLabel() {
    return $this->t('Add Block');
  }

  /**
   * Builds the form for the block.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage being configured.
   * @param int $delta
   *   The delta of the section.
   * @param string $region
   *   The region of the block.
   * @param string|null $plugin_id
   *   The plugin ID of the block to add.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $region = NULL, $plugin_id = NULL) {
    // Only generate a new component once per form submission.
    if (!$component = $form_state->getTemporaryValue('layout_builder__component')) {
      $component = new SectionComponent($this->uuidGenerator->generate(), $region, ['id' => $plugin_id]);
      $section_storage->getSection($delta)->appendComponent($component);
      $form_state->setTemporaryValue('layout_builder__component', $component);
    }
    return $this->doBuildForm($form, $form_state, $section_storage, $delta, $component);
  }

}
