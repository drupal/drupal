<?php

namespace Drupal\layout_builder\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form containing the Layout Builder UI for overrides.
 *
 * @internal
 */
class OverridesEntityForm extends ContentEntityForm {

  /**
   * Layout tempstore repository.
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
   * Constructs a new OverridesEntityForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, LayoutTempstoreRepositoryInterface $layout_tempstore_repository) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('layout_builder.tempstore_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return $this->getEntity()->getEntityTypeId() . '_layout_builder_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    parent::init($form_state);

    // Create a transient display that is not persisted, but used only for
    // building the components required for the layout form.
    $display = EntityFormDisplay::create([
      'targetEntityType' => $this->getEntity()->getEntityTypeId(),
      'bundle' => $this->getEntity()->bundle(),
    ]);

    // Allow modules to choose if they are relevant to the layout form.
    $this->moduleHandler->alter('layout_builder_overrides_entity_form_display', $display);

    // Add the widget for Layout Builder after the alter.
    $display->setComponent(OverridesSectionStorage::FIELD_NAME, [
      'type' => 'layout_builder_widget',
      'weight' => -10,
      'settings' => [],
    ]);

    $this->setFormDisplay($display, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL) {
    $this->sectionStorage = $section_storage;
    $form = parent::buildForm($form, $form_state);

    // @todo \Drupal\layout_builder\Field\LayoutSectionItemList::defaultAccess()
    //   restricts all access to the field, explicitly allow access here until
    //   https://www.drupal.org/node/2942975 is resolved.
    $form[OverridesSectionStorage::FIELD_NAME]['#access'] = TRUE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);

    $this->layoutTempstoreRepository->delete($this->sectionStorage);
    $this->messenger()->addStatus($this->t('The layout override has been saved.'));
    $form_state->setRedirectUrl($this->sectionStorage->getRedirectUrl());
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save layout');
    $actions['delete']['#access'] = FALSE;
    $actions['#weight'] = -1000;

    $actions['discard_changes'] = [
      '#type' => 'submit',
      '#value' => $this->t('Discard changes'),
      '#submit' => ['::redirectOnSubmit'],
      '#redirect' => 'discard_changes',
    ];
    // @todo This button should be conditionally displayed, see
    //   https://www.drupal.org/node/2917777.
    $actions['revert'] = [
      '#type' => 'submit',
      '#value' => $this->t('Revert to defaults'),
      '#submit' => ['::redirectOnSubmit'],
      '#redirect' => 'revert',
    ];
    return $actions;
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
  public function getSectionStorage() {
    return $this->sectionStorage;
  }

}
