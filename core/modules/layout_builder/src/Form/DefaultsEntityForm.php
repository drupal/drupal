<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form containing the Layout Builder UI for defaults.
 *
 * @internal
 */
class DefaultsEntityForm extends EntityForm {

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
   * Constructs a new DefaultsEntityForm.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
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
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL) {
    $form['layout_builder'] = [
      '#type' => 'layout_builder',
      '#section_storage' => $section_storage,
    ];
    $this->sectionStorage = $section_storage;
    return parent::buildForm($form, $form_state);
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
    $actions['submit']['#value'] = $this->t('Save layout');
    $actions['#weight'] = -1000;

    $actions['discard_changes'] = [
      '#type' => 'link',
      '#title' => $this->t('Discard changes'),
      '#attributes' => ['class' => ['button']],
      '#url' => $this->sectionStorage->getLayoutBuilderUrl('discard_changes'),
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = $this->sectionStorage->save();
    $this->layoutTempstoreRepository->delete($this->sectionStorage);
    $this->messenger()->addMessage($this->t('The layout has been saved.'));
    $form_state->setRedirectUrl($this->sectionStorage->getRedirectUrl());
    return $return;
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
