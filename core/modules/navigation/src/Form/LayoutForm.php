<?php

declare(strict_types=1);

namespace Drupal\navigation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\layout_builder\Form\LayoutBuilderEntityFormTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for configuring navigation blocks.
 *
 * @internal
 */
final class LayoutForm extends FormBase {

  use LayoutBuilderEntityFormTrait {
    buildActions as buildActionsElement;
    saveTasks as saveTasks;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId(): string {
    return 'navigation_layout';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'navigation_layout';
  }

  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * Constructs a new LayoutForm.
   */
  public function __construct(protected LayoutTempstoreRepositoryInterface $layoutTempstoreRepository) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('layout_builder.tempstore_repository')
    );
  }

  /**
   * Handles switching the configuration type selector.
   *
   * @return array
   *   An associative array containing the structure of the form.
   */
  public function enableEditMode($form, FormStateInterface $form_state): array {
    if ($form_state::hasAnyErrors()) {
      return $form;
    }

    $this->handleFormElementsVisibility($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?SectionStorageInterface $section_storage = NULL) {
    $form['#prefix'] = '<div id="js-config-form-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attributes']['class'][] = 'layout-builder-form';
    $this->sectionStorage = $section_storage;

    $form['layout_builder'] = [
      '#type' => 'layout_builder',
      '#section_storage' => $section_storage,
    ];
    $form['#attached']['library'][] = 'navigation/navigation.layoutBuilder';

    $form['actions'] = [
      'enable_edit_mode' => [
        '#type' => 'submit',
        '#value' => $this->t('Enable edit mode'),
        '#name' => 'enable_edit_mode',
        '#ajax' => [
          'callback' => '::enableEditMode',
          'wrapper' => 'js-config-form-wrapper',
          'effect' => 'fade',
        ],
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#name' => 'save',
      ],
    ] + $this->buildActionsElement([]);

    $this->handleFormElementsVisibility($form, FALSE);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $user_input = $form_state->getUserInput();
    if (isset($user_input['save'])) {
      $this->save($form, $form_state);
    }
  }

  /**
   * Saves the Layout changes.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function save(array &$form, FormStateInterface $form_state): void {
    $this->sectionStorage->save();
    $this->saveTasks($form_state, new TranslatableMarkup('Saved navigation blocks'));
  }

  /**
   * Handles visibility of the form elements based on the edit mode status.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param bool $edit_mode_enabled
   *   Boolean indicating whether the Navigation layout edit mode is enabled.
   */
  protected function handleFormElementsVisibility(array &$form, bool $edit_mode_enabled = TRUE): array {
    // Edit mode elements are visible only in edit mode.
    $form['actions']['submit']['#access'] =
    $form['actions']['discard_changes']['#access'] =
    $form['actions']['preview_toggle']['#access'] =
    $form['actions']['preview_toggle']['toggle_content_preview']['#access'] =
    $form['layout_builder']['#access'] = $edit_mode_enabled;

    // Edit mode flag element is only visible when edit mode is disabled.
    $form['actions']['enable_edit_mode']['#access'] = !$edit_mode_enabled;

    return $form;
  }

}
