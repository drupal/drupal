<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Discards any pending changes to the layout.
 *
 * @internal
 *   Form classes are internal.
 */
class DiscardLayoutChangesForm extends ConfirmFormBase {

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * Indicates if the owner of the lock is the current user or not.
   *
   * @var bool
   */
  protected $isOwnerCurrentUser;

  /**
   * Constructs a new DiscardLayoutChangesForm.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, MessengerInterface $messenger, AccountInterface $current_user) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('messenger'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_discard_changes';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->isOwnerCurrentUser ? $this->t('Are you sure you want to discard your layout changes?') : $this->t('Are you sure you want to break the lock on the layout changes?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->sectionStorage->getLayoutBuilderUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL) {
    $this->sectionStorage = $section_storage;
    $lock = $this->layoutTempstoreRepository->getLock($section_storage);

    // The current user is considered the owner if they are the ones who made
    // the changes or if there are no changes made.
    $this->isOwnerCurrentUser = $lock ? $lock->getOwnerId() === $this->currentUser()->id() : TRUE;

    // Mark this as an administrative page for JavaScript ("Back to site" link).
    $form['#attached']['drupalSettings']['path']['currentPathIsAdmin'] = TRUE;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->layoutTempstoreRepository->delete($this->sectionStorage);

    $this->messenger->addMessage($this->t('The changes to the layout have been discarded.'));

    // If the user is discarding their own changes, redirect as usual. If they
    // are breaking the lock of another user's changes, redirect them back to
    // the Layout Builder UI to make their own changes.
    $form_state->setRedirectUrl($this->isOwnerCurrentUser ? $this->sectionStorage->getRedirectUrl() : $this->sectionStorage->getLayoutBuilderUrl());
  }

}
