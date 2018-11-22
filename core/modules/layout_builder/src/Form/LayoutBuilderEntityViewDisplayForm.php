<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\Form\EntityViewDisplayEditForm;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Edit form for the LayoutBuilderEntityViewDisplay entity type.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class LayoutBuilderEntityViewDisplayForm extends EntityViewDisplayEditForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface
   */
  protected $entity;

  /**
   * The storage section.
   *
   * @var \Drupal\layout_builder\DefaultsSectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL) {
    $this->sectionStorage = $section_storage;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Remove the Layout Builder field from the list.
    $form['#fields'] = array_diff($form['#fields'], [OverridesSectionStorage::FIELD_NAME]);
    unset($form['fields'][OverridesSectionStorage::FIELD_NAME]);

    $is_enabled = $this->entity->isLayoutBuilderEnabled();
    if ($is_enabled) {
      // Hide the table of fields.
      $form['fields']['#access'] = FALSE;
      $form['#fields'] = [];
      $form['#extra'] = [];
    }

    $form['manage_layout'] = [
      '#type' => 'link',
      '#title' => $this->t('Manage layout'),
      '#weight' => -10,
      '#attributes' => ['class' => ['button']],
      '#url' => $this->sectionStorage->getLayoutBuilderUrl(),
      '#access' => $is_enabled,
    ];

    $form['layout'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Layout options'),
      '#tree' => TRUE,
    ];

    $form['layout']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Layout Builder'),
      '#default_value' => $is_enabled,
    ];
    $form['#entity_builders']['layout_builder'] = '::entityFormEntityBuild';

    // @todo Expand to work for all view modes in
    //   https://www.drupal.org/node/2907413.
    if ($this->entity->getMode() === 'default') {
      $entity_type = $this->entityTypeManager->getDefinition($this->entity->getTargetEntityTypeId());
      $form['layout']['allow_custom'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow each @entity to have its layout customized.', [
          '@entity' => $entity_type->getSingularLabel(),
        ]),
        '#default_value' => $this->entity->isOverridable(),
        '#states' => [
          'disabled' => [
            ':input[name="layout[enabled]"]' => ['checked' => FALSE],
          ],
          'invisible' => [
            ':input[name="layout[enabled]"]' => ['checked' => FALSE],
          ],
        ],
      ];
      if (!$is_enabled) {
        $form['layout']['allow_custom']['#attributes']['disabled'] = 'disabled';
      }
      // Prevent turning off overrides while any exist.
      if ($this->hasOverrides($this->entity)) {
        $form['layout']['enabled']['#disabled'] = TRUE;
        $form['layout']['enabled']['#description'] = $this->t('You must revert all customized layouts of this display before you can disable this option.');
        $form['layout']['allow_custom']['#disabled'] = TRUE;
        $form['layout']['allow_custom']['#description'] = $this->t('You must revert all customized layouts of this display before you can disable this option.');
        unset($form['layout']['allow_custom']['#states']);
        unset($form['#entity_builders']['layout_builder']);
      }
    }
    return $form;
  }

  /**
   * Determines if the defaults have any overrides.
   *
   * @param \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface $display
   *   The entity display.
   *
   * @return bool
   *   TRUE if there are any overrides of this default, FALSE otherwise.
   */
  protected function hasOverrides(LayoutEntityDisplayInterface $display) {
    if (!$display->isOverridable()) {
      return FALSE;
    }

    $entity_type = $this->entityTypeManager->getDefinition($display->getTargetEntityTypeId());
    $query = $this->entityTypeManager->getStorage($display->getTargetEntityTypeId())->getQuery()
      ->exists(OverridesSectionStorage::FIELD_NAME);
    if ($bundle_key = $entity_type->getKey('bundle')) {
      $query->condition($bundle_key, $display->getTargetBundle());
    }
    return (bool) $query->count()->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // Do not process field values if Layout Builder is or will be enabled.
    $set_enabled = (bool) $form_state->getValue(['layout', 'enabled'], FALSE);
    /** @var \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface $entity */
    $already_enabled = $entity->isLayoutBuilderEnabled();
    if ($already_enabled || $set_enabled) {
      $form['#fields'] = [];
      $form['#extra'] = [];
    }

    parent::copyFormValuesToEntity($entity, $form, $form_state);
  }

  /**
   * Entity builder for layout options on the entity view display form.
   */
  public function entityFormEntityBuild($entity_type_id, LayoutEntityDisplayInterface $display, &$form, FormStateInterface &$form_state) {
    $set_enabled = (bool) $form_state->getValue(['layout', 'enabled'], FALSE);
    $already_enabled = $display->isLayoutBuilderEnabled();

    if ($set_enabled) {
      $overridable = (bool) $form_state->getValue(['layout', 'allow_custom'], FALSE);
      $display->setOverridable($overridable);

      if (!$already_enabled) {
        $display->enableLayoutBuilder();
      }
    }
    elseif ($already_enabled) {
      $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl('disable'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildFieldRow(FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    if ($this->entity->isLayoutBuilderEnabled()) {
      return [];
    }

    return parent::buildFieldRow($field_definition, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildExtraFieldRow($field_id, $extra_field) {
    if ($this->entity->isLayoutBuilderEnabled()) {
      return [];
    }

    return parent::buildExtraFieldRow($field_id, $extra_field);
  }

}
