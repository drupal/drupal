<?php

namespace Drupal\search_node\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;

/**
 * Hooks for the Search Node module.
 */
class SearchNodeHooks {

  /**
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public static function preDelete(EntityInterface $entity): void {
    // Ensure that all nodes deleted are removed from the search index.
    if ($entity->getEntityTypeId() === 'node') {
      if (\Drupal::hasService('search.index')) {
        /** @var \Drupal\search\SearchIndexInterface $search_index */
        $search_index = \Drupal::service('search.index');
        $search_index->clear('node_search', $entity->id());
      }
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_node_preview_form_select_alter')]
  public function formNodePreviewFormSelectAlter(&$form, FormStateInterface $form_state, $form_id): void {
    unset($form['view_mode']['#options']['search_index']);
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('node_update')]
  public function nodeUpdate(NodeInterface $node): void {
    // Remove deleted translations from the search index.
    $node_original = $node->getOriginal();
    $removed_translations = array_diff_key($node_original->getTranslationLanguages(), $node->getTranslationLanguages());
    foreach (array_keys($removed_translations) as $langcode) {
      \Drupal::service('search.index')->clear('node_search', $node_original->id(), $langcode);
    }
  }

  /**
   * Implements hook_entity_view_display_alter().
   */
  #[Hook('entity_view_display_alter')]
  public function entityViewDisplayAlter(EntityViewDisplayInterface $display, $context): void {
    if ($context['entity_type'] == 'node') {
      // Hide field labels in search index.
      if ($context['view_mode'] == 'search_index') {
        foreach ($display->getComponents() as $name => $options) {
          if (isset($options['label'])) {
            $options['label'] = 'hidden';
            $display->setComponent($name, $options);
          }
        }
      }
    }
  }

}
