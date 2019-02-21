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
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $section_storage_entity */
    $section_storage_entity = $this->sectionStorage->getContextValue('entity');

    // @todo Replace with new API in
    //   https://www.drupal.org/project/drupal/issues/2942907.
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $active_entity */
    $active_entity = $this->entityTypeManager->getStorage($section_storage_entity->getEntityTypeId())->load($section_storage_entity->id());

    // Any fields that are not editable on this form should be updated with the
    // value from the active entity for editing. This avoids overwriting fields
    // that have been updated since the entity was stored in the section
    // storage.
    $edited_field_names = $this->getEditedFieldNames($form_state);
    foreach ($section_storage_entity->getFieldDefinitions() as $field_name => $field_definition) {
      if (!in_array($field_name, $edited_field_names) && !$field_definition->isReadOnly() && !$field_definition->isComputed()) {
        $section_storage_entity->{$field_name} = $active_entity->{$field_name};
      }
    }

    // \Drupal\Core\Entity\EntityForm::buildEntity() clones the entity object.
    // Keep it in sync with the one used by the section storage.
    $this->setEntity($section_storage_entity);
    $entity = parent::buildEntity($form, $form_state);
    $this->sectionStorage->setContextValue('entity', $entity);
    return $entity;
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
      '#type' => 'link',
      '#title' => $this->t('Discard changes'),
      '#attributes' => ['class' => ['button']],
      '#url' => $this->sectionStorage->getLayoutBuilderUrl('discard_changes'),
    ];
    // @todo This link should be conditionally displayed, see
    //   https://www.drupal.org/node/2917777.
    $actions['revert'] = [
      '#type' => 'link',
      '#title' => $this->t('Revert to defaults'),
      '#attributes' => ['class' => ['button']],
      '#url' => $this->sectionStorage->getLayoutBuilderUrl('revert'),
    ];
    return $actions;
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
