<?php

/**
 * @file
 * Contains \Drupal\block_content\BlockContentTranslationHandler.
 */

namespace Drupal\block_content;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the translation handler for custom blocks.
 */
class BlockContentTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity) {
    parent::entityFormAlter($form, $form_state, $entity);
    // Move the translation fieldset to a vertical tab.
    if (isset($form['translation'])) {
      $form['translation'] += array(
        '#group' => 'additional_settings',
        '#weight' => 100,
        '#attributes' => array(
          'class' => array('block-content-translation-options'),
        ),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function entityFormTitle(EntityInterface $entity) {
    $block_type = BlockContentType::load($entity->bundle());
    return t('<em>Edit @type</em> @title', array('@type' => $block_type->label(), '@title' => $entity->label()));
  }

}
