<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\Form\EntityViewDisplayEditForm;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
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
   * @var \Drupal\layout_builder\SectionStorageInterface
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

    // Hide the table of fields.
    $form['fields']['#access'] = FALSE;
    $form['#fields'] = [];
    $form['#extra'] = [];

    $form['manage_layout'] = [
      '#type' => 'link',
      '#title' => $this->t('Manage layout'),
      '#weight' => -10,
      '#attributes' => ['class' => ['button']],
      '#url' => $this->sectionStorage->getLayoutBuilderUrl(),
    ];

    // @todo Expand to work for all view modes in
    //   https://www.drupal.org/node/2907413.
    if ($this->entity->getMode() === 'default') {
      $form['layout'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $this->t('Layout options'),
        '#tree' => TRUE,
      ];

      $entity_type = $this->entityTypeManager->getDefinition($this->entity->getTargetEntityTypeId());
      $form['layout']['allow_custom'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow each @entity to have its layout customized.', [
          '@entity' => $entity_type->getSingularLabel(),
        ]),
        '#default_value' => $this->entity->isOverridable(),
      ];
      // Prevent turning off overrides while any exist.
      if ($this->hasOverrides($this->entity)) {
        $form['layout']['allow_custom']['#disabled'] = TRUE;
        $form['layout']['allow_custom']['#description'] = $this->t('You must revert all customized layouts of this display before you can disable this option.');
      }
      else {
        $form['#entity_builders'][] = '::entityFormEntityBuild';
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
      ->exists('layout_builder__layout');
    if ($bundle_key = $entity_type->getKey('bundle')) {
      $query->condition($bundle_key, $display->getTargetBundle());
    }
    return (bool) $query->count()->execute();
  }

  /**
   * Entity builder for layout options on the entity view display form.
   */
  public function entityFormEntityBuild($entity_type_id, LayoutEntityDisplayInterface $display, &$form, FormStateInterface &$form_state) {
    $new_value = (bool) $form_state->getValue(['layout', 'allow_custom'], FALSE);
    $display->setOverridable($new_value);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildFieldRow(FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  protected function buildExtraFieldRow($field_id, $extra_field) {
    // Intentionally empty.
  }

}
