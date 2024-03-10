<?php

namespace Drupal\Core\Entity\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Attribute\EntityReferenceSelection;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a fallback plugin for missing entity_reference selection plugins.
 *
 * Note this plugin does not appear in the UI and is only used when a plugin can
 * not found.
 */
#[EntityReferenceSelection(
  id: "broken",
  label: new TranslatableMarkup("Broken/Missing"),
  group: '',
  weight: -100,
)]
class Broken extends SelectionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['selection_handler'] = [
      '#markup' => $this->t('The selected selection handler is broken.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    return [];
  }

}
