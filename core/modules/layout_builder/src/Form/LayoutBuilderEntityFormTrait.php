<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a trait for common methods used in Layout Builder entity forms.
 */
trait LayoutBuilderEntityFormTrait {

  use PreviewToggleTrait;

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId(): string {
    return $this->getEntity()->getEntityTypeId() . '_layout_builder_form';
  }

  /**
   * Build the message container.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The message to display.
   * @param string $type
   *   The form type this is being attached to.
   *
   * @return array
   *   The render array.
   */
  protected function buildMessageContainer(TranslatableMarkup $message, string $type): array {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'layout-builder__message',
          sprintf('layout-builder__message--%s', $type),
        ],
      ],
      'message' => [
        '#theme' => 'status_messages',
        '#message_list' => ['status' => [$message]],
        '#status_headings' => [
          'status' => $this->t('Status message'),
        ],
      ],
      '#weight' => -900,
    ];
  }

  /**
   * Form submission handler.
   */
  public function redirectOnSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl($form_state->getTriggeringElement()['#redirect']));
  }

  /**
   * Retrieves the section storage object.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface
   *   The section storage for the current form.
   */
  public function getSectionStorage(): SectionStorageInterface {
    return $this->sectionStorage;
  }

  /**
   * Builds the actions for the form.
   *
   * @param array $actions
   *   The actions array to modify.
   *
   * @return array
   *   The modified actions array.
   */
  protected function buildActions(array $actions): array {
    $actions['#attributes']['role'] = 'region';
    $actions['#attributes']['aria-label'] = $this->t('Layout Builder tools');
    $actions['submit']['#value'] = $this->t('Save layout');
    $actions['#weight'] = -1000;
    $actions['discard_changes'] = [
      '#type' => 'submit',
      '#value' => $this->t('Discard changes'),
      '#submit' => ['::redirectOnSubmit'],
      '#redirect' => 'discard_changes',
    ];
    $actions['preview_toggle'] = $this->buildContentPreviewToggle();
    return $actions;
  }

  /**
   * Performs tasks that are needed during the save process.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The message to display.
   */
  protected function saveTasks(FormStateInterface $formState, TranslatableMarkup $message): void {
    $this->layoutTempstoreRepository->delete($this->getSectionStorage());
    $this->messenger()->addStatus($message);
    $formState->setRedirectUrl($this->getSectionStorage()->getRedirectUrl());
  }

}
