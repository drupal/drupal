<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Section;

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
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being configured.
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
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = NULL, $delta = NULL, $region = NULL, $uuid = NULL, array $configuration = []) {
    /** @var \Drupal\layout_builder\SectionStorageInterface $field_list */
    $field_list = $entity->layout_builder__layout;
    $plugin = $field_list->getSection($delta)->getComponent($uuid)->getPlugin();

    return parent::buildForm($form, $form_state, $entity, $delta, $region, $plugin->getPluginId(), $plugin->getConfiguration());
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
