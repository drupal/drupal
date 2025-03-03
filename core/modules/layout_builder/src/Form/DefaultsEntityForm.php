<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form containing the Layout Builder UI for defaults.
 *
 * @internal
 *   Form classes are internal.
 */
class DefaultsEntityForm extends EntityForm {

  use PreviewToggleTrait;
  use LayoutBuilderEntityFormTrait;

  /**
   * Layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * Constructs a new DefaultsEntityForm.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?SectionStorageInterface $section_storage = NULL) {
    $form['#attributes']['class'][] = 'layout-builder-form';
    $form['layout_builder'] = [
      '#type' => 'layout_builder',
      '#section_storage' => $section_storage,
    ];
    $form['layout_builder_message'] = $this->buildMessage($section_storage->getContextValue('display'));

    $this->sectionStorage = $section_storage;
    return parent::buildForm($form, $form_state);
  }

  /**
   * Renders a message to display at the top of the layout builder.
   *
   * @param \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface $entity
   *   The entity view display being edited.
   *
   * @return array
   *   A renderable array containing the message.
   */
  protected function buildMessage(LayoutEntityDisplayInterface $entity) {
    $entity_type_id = $entity->getTargetEntityTypeId();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);

    $args = [
      '@bundle' => $bundle_info[$entity->getTargetBundle()]['label'],
      '@plural_label' => $entity_type->getPluralLabel(),
    ];
    if ($entity_type->hasKey('bundle')) {
      $message = $this->t('You are editing the layout template for all @bundle @plural_label.', $args);
    }
    else {
      $message = $this->t('You are editing the layout template for all @plural_label.', $args);
    }
    return $this->buildMessageContainer($message, 'defaults');
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    // \Drupal\Core\Entity\EntityForm::buildEntity() clones the entity object.
    // Keep it in sync with the one used by the section storage.
    $this->setEntity($this->sectionStorage->getContextValue('display'));
    $entity = parent::buildEntity($form, $form_state);
    $this->sectionStorage->setContextValue('display', $entity);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $route_parameters = $route_match->getParameters()->all();

    return $this->entityTypeManager->getStorage('entity_view_display')->load($route_parameters['entity_type_id'] . '.' . $route_parameters['bundle'] . '.' . $route_parameters['view_mode_name']);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    return $this->buildActions($actions);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = $this->sectionStorage->save();
    $this->saveTasks($form_state, $this->t('The layout has been saved.'));
    return $return;
  }

}
