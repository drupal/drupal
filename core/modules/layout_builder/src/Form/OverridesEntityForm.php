<?php

namespace Drupal\layout_builder\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceDynamicSafeFormInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\OverridesSectionStorageInterface;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form containing the Layout Builder UI for overrides.
 *
 * @internal
 *   Form classes are internal.
 */
class OverridesEntityForm extends ContentEntityForm implements WorkspaceDynamicSafeFormInterface {

  use PreviewToggleTrait;
  use LayoutBuilderEntityFormTrait;
  use WorkspaceSafeFormTrait;

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
  protected function init(FormStateInterface $form_state) {
    parent::init($form_state);

    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, $this->getOperation(), FALSE);
    $form_display->setComponent(OverridesSectionStorage::FIELD_NAME, [
      'type' => 'layout_builder_widget',
      'weight' => -10,
      'settings' => [],
    ]);

    $this->setFormDisplay($form_display, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?SectionStorageInterface $section_storage = NULL) {
    $this->sectionStorage = $section_storage;
    $form = parent::buildForm($form, $form_state);
    $form['#attributes']['class'][] = 'layout-builder-form';

    // @todo \Drupal\layout_builder\Field\LayoutSectionItemList::defaultAccess()
    //   restricts all access to the field, explicitly allow access here until
    //   https://www.drupal.org/node/2942975 is resolved.
    $form[OverridesSectionStorage::FIELD_NAME]['#access'] = TRUE;

    $form['layout_builder_message'] = $this->buildMessage($section_storage->getContextValue('entity'), $section_storage);
    return $form;
  }

  /**
   * Renders a message to display at the top of the layout builder.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose layout is being edited.
   * @param \Drupal\layout_builder\OverridesSectionStorageInterface $section_storage
   *   The current section storage.
   *
   * @return array
   *   A renderable array containing the message.
   */
  protected function buildMessage(EntityInterface $entity, OverridesSectionStorageInterface $section_storage) {
    $entity_type = $entity->getEntityType();
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity->getEntityTypeId());

    $variables = [
      '@bundle' => $bundle_info[$entity->bundle()]['label'],
      '@singular_label' => $entity_type->getSingularLabel(),
      '@plural_label' => $entity_type->getPluralLabel(),
    ];

    $defaults_link = $section_storage
      ->getDefaultSectionStorage()
      ->getLayoutBuilderUrl();

    if ($defaults_link->access($this->currentUser())) {
      $variables[':link'] = $defaults_link->toString();
      if ($entity_type->hasKey('bundle')) {
        $message = $this->t('You are editing the layout for this @bundle @singular_label. <a href=":link">Edit the template for all @bundle @plural_label instead.</a>', $variables);
      }
      else {
        $message = $this->t('You are editing the layout for this @singular_label. <a href=":link">Edit the template for all @plural_label instead.</a>', $variables);
      }
    }
    else {
      if ($entity_type->hasKey('bundle')) {
        $message = $this->t('You are editing the layout for this @bundle @singular_label.', $variables);
      }
      else {
        $message = $this->t('You are editing the layout for this @singular_label.', $variables);
      }
    }
    return $this->buildMessageContainer($message, 'overrides');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);
    $this->saveTasks($form_state, $this->t('The layout override has been saved.'));
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions = $this->buildActions($actions);
    $actions['delete']['#access'] = FALSE;

    $actions['discard_changes']['#limit_validation_errors'] = [];
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

}
