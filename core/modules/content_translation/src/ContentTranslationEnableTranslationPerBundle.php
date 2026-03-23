<?php

declare(strict_types=1);

namespace Drupal\content_translation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a widget to enable content translation per entity bundle.
 *
 * This service is used to support entities which not using the language
 * configuration form element (language_configuration). A typical use case is
 * the user account settings form.
 *
 * @see \Drupal\language\Element\LanguageConfiguration
 * @see \Drupal\user\AccountSettingsForm::buildForm()
 *
 * @todo Remove once all core entities have language configuration.
 */
class ContentTranslationEnableTranslationPerBundle {

  use StringTranslationTrait;

  public function __construct(
    protected ContentTranslationManagerInterface $contentTranslationManager,
    protected AccountInterface $currentUser,
    protected LanguageManagerInterface $languageManager,
    protected RouteBuilderInterface $routeBuilder,
  ) {}

  /**
   * Returns the widget to be used per entity bundle.
   *
   * @param string $entityTypeId
   *   The type of the entity being configured for translation.
   * @param string $bundle
   *   The bundle of the entity being configured for translation.
   * @param array $form
   *   The configuration form render array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   */
  public function getWidget(string $entityTypeId, string $bundle, array &$form, FormStateInterface $formState): array {
    $key = $formState->get(['content_translation', 'key']);
    $context = $formState->get(['language', $key]) ?: [];
    $context += ['entity_type' => $entityTypeId, 'bundle' => $bundle];
    $formState->set(['language', $key], $context);
    $element = $this->configElementProcess(['#name' => $key], $formState, $form);
    unset($element['content_translation']['#element_validate']);
    return $element;
  }

  /**
   * Provides the language enable/disable form element.
   *
   * @param array $element
   *   The initial form element.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form current state.
   * @param array $form
   *   The form render array.
   *
   * @return array
   *   The form element render array.
   */
  public function configElementProcess(array $element, FormStateInterface $formState, array &$form): array {
    if (!empty($element['#content_translation_skip_alter']) || !$this->currentUser->hasPermission('administer content translation')) {
      return $element;
    }

    $key = $element['#name'];
    $formState->set(['content_translation', 'key'], $key);
    $context = $formState->get(['language', $key]);

    $element['content_translation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable translation'),
      // For new bundle, we don't know the bundle name yet, default to no
      // translatability.
      '#default_value' => $context['bundle'] && $this->contentTranslationManager->isEnabled($context['entity_type'], $context['bundle']),
      '#element_validate' => [self::class . ':configElementValidate'],
    ];

    $submitName = isset($form['actions']['save_continue']) ? 'save_continue' : 'submit';
    // Only add the submit handler on the submit button if the #submit property
    // is already available, otherwise this breaks the form submit function.
    if (isset($form['actions'][$submitName]['#submit'])) {
      $form['actions'][$submitName]['#submit'][] = self::class . ':configElementSubmit';
    }
    else {
      $form['#submit'][] = self::class . ':configElementSubmit';
    }

    return $element;
  }

  /**
   * Provides a validation callback for the language enable/disable element.
   *
   * Checks whether translation can be enabled: if language is set to one of the
   * special languages and language selector is not hidden, translation cannot
   * be enabled.
   *
   * @param array $element
   *   The initial form element.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form current state.
   * @param array $form
   *   The form render array.
   */
  public function configElementValidate(array $element, FormStateInterface $formState, array &$form): void {
    $key = $formState->get(['content_translation', 'key']);
    $values = $formState->getValue($key);
    if (!$values['language_alterable'] && $values['content_translation'] && $this->languageManager->isLanguageLocked($values['langcode'])) {
      $lockedLanguages = [];
      foreach ($this->languageManager->getLanguages(LanguageInterface::STATE_LOCKED) as $language) {
        $lockedLanguages[$language->getId()] = $language->getName();
      }
      // @todo Set the correct form element name as soon as the element parents
      //   are correctly set. We should be using NestedArray::getValue() but for
      //   now we cannot.
      $formState->setErrorByName('', $this->t('"Show language selector" is not compatible with translating content that has default language: %choice. Either do not hide the language selector or pick a specific language.', [
        '%choice' => $lockedLanguages[$values['langcode']],
      ]));
    }
  }

  /**
   * Provides a submit callback for the language enable/disable element.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form current state.
   */
  public function configElementSubmit(array $form, FormStateInterface $formState): void {
    $key = $formState->get(['content_translation', 'key']);
    $context = $formState->get(['language', $key]);
    $enabled = $formState->getValue([$key, 'content_translation']);

    if ($this->contentTranslationManager->isEnabled($context['entity_type'], $context['bundle']) != $enabled) {
      $this->contentTranslationManager->setEnabled($context['entity_type'], $context['bundle'], $enabled);
      $this->routeBuilder->setRebuildNeeded();
    }
  }

}
