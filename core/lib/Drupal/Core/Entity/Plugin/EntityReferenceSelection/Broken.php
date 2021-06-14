<?php

namespace Drupal\Core\Entity\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a fallback plugin for missing entity_reference selection plugins.
 *
 * @EntityReferenceSelection(
 *   id = "broken",
 *   label = @Translation("Broken/Missing")
 * )
 */
class Broken extends SelectionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['selection_handler'] = [
      '#markup' => t('The selected selection handler is broken.'),
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
