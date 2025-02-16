<?php

namespace Drupal\node;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the translation handler for nodes.
 */
class NodeTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity) {
    parent::entityFormAlter($form, $form_state, $entity);

    if (isset($form['content_translation'])) {
      // We do not need to show these values on node forms: they inherit the
      // basic node property values.
      $form['content_translation']['status']['#access'] = FALSE;
      $form['content_translation']['name']['#access'] = FALSE;
      $form['content_translation']['created']['#access'] = FALSE;
    }

    $form_object = $form_state->getFormObject();
    $form_langcode = $form_object->getFormLangcode($form_state);
    $translations = $entity->getTranslationLanguages();
    $status_translatable = NULL;
    // Change the submit button labels if there was a status field they affect
    // in which case their publishing / unpublishing may or may not apply
    // to all translations.
    if (!$entity->isNew() && (!isset($translations[$form_langcode]) || count($translations) > 1)) {
      foreach ($entity->getFieldDefinitions() as $property_name => $definition) {
        if ($property_name == 'status') {
          $status_translatable = $definition->isTranslatable();
        }
      }
      if (isset($status_translatable)) {
        if (isset($form['actions']['submit'])) {
          $form['actions']['submit']['#value'] .= ' ' . ($status_translatable ? $this->t('(this translation)') : $this->t('(all translations)'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function entityFormTitle(EntityInterface $entity) {
    $type_name = node_get_type_label($entity);
    return $this->t('<em>Edit @type</em> @title', ['@type' => $type_name, '@title' => $entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormEntityBuild($entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    if ($form_state->hasValue('content_translation')) {
      $translation = &$form_state->getValue('content_translation');
      $translation['status'] = $entity->isPublished();
      $account = $entity->uid->entity;
      $translation['uid'] = $account ? $account->id() : 0;
      $translation['created'] = $this->dateFormatter->format($entity->created->value, 'custom', 'Y-m-d H:i:s O');
    }
    parent::entityFormEntityBuild($entity_type, $entity, $form, $form_state);
  }

}
