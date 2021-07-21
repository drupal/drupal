<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\Lock;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * A trait for functionality used by both types of entity forms.
 */
trait LayoutBuilderEntityFormTrait {

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;


  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * Gets the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  abstract protected function currentUser();

  /**
   * Gets the messenger service.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface
   *   The messenger service.
   */
  abstract protected function messenger();

  /**
   * Checks if a lock is present and provides a situation specific message.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage to get the lock from.
   *
   * @return array|null
   *   If the section storage is locked and does not belong to current user,
   *   return a render array that will replace the Layout Builder UI form.
   *   Otherwise, return null.
   */
  protected function getLockMessage(SectionStorageInterface $section_storage) {
    if ($lock = $this->layoutTempstoreRepository->getLock($section_storage)) {
      if ($this->currentUser()->id() === $lock->getOwnerId()) {
        $this->messenger()->addWarning($this->t('You have unsaved changes.'));
      }
      else {
        return $this->lockMessage($section_storage, $lock);
      }
    }
    return NULL;
  }

  /**
   * Builds the lock message.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param \Drupal\Core\TempStore\Lock $lock
   *   The lock object.
   *
   * @return array
   *   A render array.
   */
  protected function lockMessage(SectionStorageInterface $section_storage, Lock $lock) {
    return [
      '#theme' => 'status_messages',
      '#message_list' => [
        'warning' => [
          [
            '#type' => 'break_lock_link',
            '#label' => $this->t('layout'),
            '#lock' => $lock,
            '#url' => $section_storage->getLayoutBuilderUrl('discard_changes'),
          ],
        ],
      ],
      '#status_headings' => [
        'warning' => $this->t('Warning message'),
      ],
    ];
  }

  /**
   * Steps shared when saving override and default section storage.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the Layout Builder UI form.
   */
  protected function layoutEntitySaveTasks(FormStateInterface $form_state) {
    $this->layoutTempstoreRepository->delete($this->sectionStorage);
    $this->messenger()->deleteByType('warning');
    $this->messenger()->addMessage($this->t('The layout has been saved.'));
    $form_state->setRedirectUrl($this->sectionStorage->getRedirectUrl());
  }

}
