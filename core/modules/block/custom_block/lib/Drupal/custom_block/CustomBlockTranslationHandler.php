<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockTranslationHandler.
 */

namespace Drupal\custom_block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\ContentTranslationHandler;

/**
 * Defines the translation handler for custom blocks.
 */
class CustomBlockTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(array &$form, array &$form_state, EntityInterface $entity) {
    parent::entityFormAlter($form, $form_state, $entity);
    // Move the translation fieldset to a vertical tab.
    if (isset($form['translation'])) {
      $form['translation'] += array(
        '#group' => 'additional_settings',
        '#weight' => 100,
        '#attributes' => array(
          'class' => array('custom-block-translation-options'),
        ),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function entityFormTitle(EntityInterface $entity) {
    $block_type = entity_load('custom_block_type', $entity->bundle());
    return t('<em>Edit @type</em> @title', array('@type' => $block_type->label(), '@title' => $entity->label()));
  }

}
