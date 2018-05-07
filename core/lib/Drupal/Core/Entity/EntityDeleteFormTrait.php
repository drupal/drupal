<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Config\Entity\ConfigDependencyDeleteFormTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a trait for an entity deletion form.
 *
 * This trait relies on the StringTranslationTrait and the logger method added
 * by FormBase.
 *
 * @ingroup entity_api
 */
trait EntityDeleteFormTrait {
  use ConfigDependencyDeleteFormTrait;

  /**
   * Gets the entity of this form.
   *
   * Provided by \Drupal\Core\Entity\EntityForm.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  abstract public function getEntity();

  /**
   * Gets the logger for a specific channel.
   *
   * Provided by \Drupal\Core\Form\FormBase.
   *
   * @param string $channel
   *   The name of the channel.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger for this channel.
   */
  abstract protected function logger($channel);

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @entity-type %label?', [
      '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
      '%label' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * Gets the message to display to the user after deleting the entity.
   *
   * @return string
   *   The translated string of the deletion message.
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();
    return $this->t('The @entity-type %label has been deleted.', [
      '@entity-type' => $entity->getEntityType()->getLowercaseLabel(),
      '%label' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity = $this->getEntity();
    if ($entity->hasLinkTemplate('collection')) {
      // If available, return the collection URL.
      return $entity->urlInfo('collection');
    }
    else {
      // Otherwise fall back to the default link template.
      return $entity->urlInfo();
    }
  }

  /**
   * Returns the URL where the user should be redirected after deletion.
   *
   * @return \Drupal\Core\Url
   *   The redirect URL.
   */
  protected function getRedirectUrl() {
    $entity = $this->getEntity();
    if ($entity->hasLinkTemplate('collection')) {
      // If available, return the collection URL.
      return $entity->urlInfo('collection');
    }
    else {
      // Otherwise fall back to the front page.
      return Url::fromRoute('<front>');
    }
  }

  /**
   * Logs a message about the deleted entity.
   */
  protected function logDeletionMessage() {
    $entity = $this->getEntity();
    $this->logger($entity->getEntityType()->getProvider())->notice('The @entity-type %label has been deleted.', [
      '@entity-type' => $entity->getEntityType()->getLowercaseLabel(),
      '%label' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getEntity()->delete();
    $this->messenger()->addStatus($this->getDeletionMessage());
    $form_state->setRedirectUrl($this->getCancelUrl());
    $this->logDeletionMessage();
  }

}
