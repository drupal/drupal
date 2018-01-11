<?php

namespace Drupal\layout_builder\Form;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a form to update a block.
 *
 * @internal
 */
class UpdateBlockForm extends ConfigureBlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_update_block';
  }

  /**
   * Builds the block form.
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
   * @param string $uuid
   *   The UUID of the block being updated.
   * @param array $configuration
   *   (optional) The array of configuration for the block.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $region = NULL, $uuid = NULL, array $configuration = []) {
    $plugin = $section_storage->getSection($delta)->getComponent($uuid)->getPlugin();
    if ($plugin instanceof ConfigurablePluginInterface) {
      $configuration = $plugin->getConfiguration();
    }

    return parent::buildForm($form, $form_state, $section_storage, $delta, $region, $plugin->getPluginId(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  protected function submitLabel() {
    return $this->t('Update');
  }

  /**
   * {@inheritdoc}
   */
  protected function submitBlock(Section $section, $region, $uuid, array $configuration) {
    $section->getComponent($uuid)->setConfiguration($configuration);
  }

}
