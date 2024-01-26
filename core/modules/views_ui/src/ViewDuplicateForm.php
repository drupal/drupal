<?php

namespace Drupal\views_ui;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the Views duplicate form.
 *
 * @internal
 */
class ViewDuplicateForm extends ViewFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('language_manager')
    );
  }

  /**
   * Constructs a ViewDuplicateForm.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Drupal's module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, LanguageManagerInterface $language_manager) {
    $this->setModuleHandler($moduleHandler);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    // Do not prepare the entity while it is being added.
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    parent::form($form, $form_state);

    $form['#title'] = $this->t('Duplicate of @label', ['@label' => $this->entity->label()]);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View name'),
      '#required' => TRUE,
      '#size' => 32,
      '#maxlength' => 255,
      '#default_value' => $this->t('Duplicate of @label', ['@label' => $this->entity->label()]),
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#machine_name' => [
        'exists' => '\Drupal\views\Views::getView',
        'source' => ['label'],
      ],
      '#default_value' => '',
      '#description' => $this->t('A unique machine-readable name for this View. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Duplicate'),
    ];
    return $actions;
  }

  /**
   * Form submission handler for the 'clone' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The original ID gets set to NULL when duplicating, so we need to store it
    // here.
    $original_id = $this->entity->id();
    $this->entity = $this->entity->createDuplicate();
    $this->entity->set('label', $form_state->getValue('label'));
    $this->entity->set('id', $form_state->getValue('id'));
    $this->entity->save();
    $this->copyTranslations($original_id);

    // Redirect the user to the view admin form.
    $form_state->setRedirectUrl($this->entity->toUrl('edit-form'));
  }

  /**
   * Copies all translations that existed on the original View.
   *
   * @param string $original_id
   *   The original View ID.
   */
  private function copyTranslations(string $original_id): void {
    if (!$this->moduleHandler->moduleExists('config_translation')) {
      return;
    }
    $current_langcode = $this->languageManager->getConfigOverrideLanguage()
      ->getId();
    $languages = $this->languageManager->getLanguages();
    $original_name = 'views.view.' . $original_id;
    $duplicate_name = 'views.view.' . $this->entity->id();
    foreach ($languages as $language) {
      $langcode = $language->getId();
      if ($langcode !== $current_langcode) {
        $original_translation = $this->languageManager->getLanguageConfigOverride($langcode, $original_name)
          ->get();
        if ($original_translation) {
          $duplicate_translation = $this->languageManager->getLanguageConfigOverride($langcode, $duplicate_name);
          $duplicate_translation->setData($original_translation);
          $duplicate_translation->save();
        }
      }
    }
  }

}
