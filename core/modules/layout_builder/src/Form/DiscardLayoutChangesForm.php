<?php

namespace Drupal\layout_builder\Form;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceDynamicSafeFormInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Discards any pending changes to the layout.
 *
 * @internal
 *   Form classes are internal.
 */
class DiscardLayoutChangesForm extends ConfirmFormBase implements WorkspaceDynamicSafeFormInterface {

  use WorkspaceSafeFormTrait;

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
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * Constructs a new DiscardLayoutChangesForm.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, MessengerInterface $messenger) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('messenger')
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
    return $this->t('Discard unsaved changes');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    try {
      $entity = $this->sectionStorage->getContextValue('entity');
      return $this->t('Any unsaved changes to the layout for %label will be discarded. This action cannot be undone.', [
        '%label' => $entity->label(),
      ]);
    }
    catch (ContextException) {
      // If the entity is not available, just return a generic message.
      return $this->t('Any unsaved changes to the layout will be discarded. This action cannot be undone.');
    }
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
  public function buildForm(array $form, FormStateInterface $form_state, ?SectionStorageInterface $section_storage = NULL) {
    $this->sectionStorage = $section_storage;
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

    $form_state->setRedirectUrl($this->sectionStorage->getRedirectUrl());
  }

}
