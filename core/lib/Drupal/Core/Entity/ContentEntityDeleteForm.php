<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a generic base class for a content entity deletion form.
 *
 * @todo Re-evaluate and streamline the entity deletion form class hierarchy in
 *   https://www.drupal.org/node/2491057.
 */
class ContentEntityDeleteForm extends ContentEntityConfirmFormBase {

  use EntityDeleteFormTrait {
    getQuestion as traitGetQuestion;
    logDeletionMessage as traitLogDeletionMessage;
    getDeletionMessage as traitGetDeletionMessage;
    getCancelUrl as traitGetCancelUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    if ($entity->isDefaultTranslation()) {
      if (count($entity->getTranslationLanguages()) > 1) {
        $languages = [];
        foreach ($entity->getTranslationLanguages() as $language) {
          $languages[] = $language->getName();
        }

        $form['deleted_translations'] = [
          '#theme' => 'item_list',
          '#title' => $this->t('The following @entity-type translations will be deleted:', [
            '@entity-type' => $entity->getEntityType()->getSingularLabel(),
          ]),
          '#items' => $languages,
        ];

        $form['actions']['submit']['#value'] = $this->t('Delete all translations');
      }
    }
    else {
      $form['actions']['submit']['#value'] = $this->t('Delete @language translation', ['@language' => $entity->language()->getName()]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    $message = $this->getDeletionMessage();

    // Make sure that deleting a translation does not delete the whole entity.
    if (!$entity->isDefaultTranslation()) {
      $untranslated_entity = $entity->getUntranslated();
      $untranslated_entity->removeTranslation($entity->language()->getId());
      $untranslated_entity->save();
      $form_state->setRedirectUrl($untranslated_entity->toUrl('canonical'));
    }
    else {
      $entity->delete();
      $form_state->setRedirectUrl($this->getRedirectUrl());
    }

    $this->messenger()->addStatus($message);
    $this->logDeletionMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    return $entity->isDefaultTranslation() ? $this->traitGetCancelUrl() : $entity->toUrl('canonical');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();

    if (!$entity->isDefaultTranslation()) {
      return $this->t('The @entity-type %label @language translation has been deleted.', [
        '@entity-type' => $entity->getEntityType()->getSingularLabel(),
        '%label'       => $entity->label(),
        '@language'    => $entity->language()->getName(),
      ]);
    }

    return $this->traitGetDeletionMessage();
  }

  /**
   * {@inheritdoc}
   */
  protected function logDeletionMessage() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();

    if (!$entity->isDefaultTranslation()) {
      $this->logger($entity->getEntityType()->getProvider())->info('The @entity-type %label @language translation has been deleted.', [
        '@entity-type' => $entity->getEntityType()->getSingularLabel(),
        '%label'       => $entity->label(),
        '@language'    => $entity->language()->getName(),
      ]);
    }
    else {
      $this->traitLogDeletionMessage();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();

    if (!$entity->isDefaultTranslation()) {
      return $this->t('Are you sure you want to delete the @language translation of the @entity-type %label?', [
        '@language' => $entity->language()->getName(),
        '@entity-type' => $this->getEntity()->getEntityType()->getSingularLabel(),
        '%label' => $this->getEntity()->label(),
      ]);
    }

    return $this->traitGetQuestion();
  }

}
