<?php

declare(strict_types=1);

namespace Drupal\content_translation\Hook;

use Drupal\content_translation\ContentTranslationEnableTranslationPerBundle;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\content_translation\FieldSyncWidget;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Form hook implementations for content_translation.
 */
class ContentTranslationFormHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountInterface $currentUser,
    protected readonly ContentTranslationManagerInterface $contentTranslationManager,
    protected readonly RedirectDestinationInterface $redirectDestination,
    protected readonly FieldSyncWidget $fieldSyncWidget,
    protected readonly ContentTranslationEnableTranslationPerBundle $contentTranslationWidget,
  ) {}

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state) : void {
    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof ContentEntityFormInterface) {
      return;
    }
    $entity = $form_object->getEntity();
    $op = $form_object->getOperation();
    // Let the content translation handler alter the content entity form. This
    // can be the 'add' or 'edit' form. It also tries a 'default' form in case
    // neither of the aforementioned forms are defined.
    if ($entity instanceof ContentEntityInterface
      && $entity->isTranslatable()
      && count($entity->getTranslationLanguages()) > 1
      && in_array($op, ['edit', 'add', 'default'], TRUE)
    ) {
      $controller = $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'translation');
      $controller->entityFormAlter($form, $form_state, $entity);
      // @todo Move the following lines to the code generating the property form
      //   elements once we have an official #multilingual FAPI key.
      $translations = $entity->getTranslationLanguages();
      $form_langcode = $form_object->getFormLangcode($form_state);
      // Handle fields shared between translations when there is at least one
      // translation available or a new one is being created.
      if (!$entity->isNew() && (!isset($translations[$form_langcode]) || count($translations) > 1)) {
        foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
          // Allow the widget to define if it should be treated as multilingual
          // by respecting an already set #multilingual key.
          if (isset($form[$field_name]) && !isset($form[$field_name]['#multilingual'])) {
            $form[$field_name]['#multilingual'] = $definition->isTranslatable();
          }
        }
      }
      // The footer region, if defined, may contain multilingual widgets so we
      // need to always display it.
      if (isset($form['footer'])) {
        $form['footer']['#multilingual'] = TRUE;
      }
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for 'field_config_edit_form'.
   */
  #[Hook('form_field_config_edit_form_alter')]
  public function formFieldConfigEditFormAlter(array &$form, FormStateInterface $form_state) : void {
    $field = $form_state->getFormObject()->getEntity();
    $bundle_is_translatable = $this->contentTranslationManager->isEnabled($field->getTargetEntityTypeId(), $field->getTargetBundle());
    $form['translatable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Users may translate this field'),
      '#default_value' => $field->isTranslatable(),
      '#weight' => -1,
      '#disabled' => !$bundle_is_translatable,
      '#access' => $field->getFieldStorageDefinition()->isTranslatable(),
    ];
    // Provide helpful pointers for administrators.
    if ($this->currentUser->hasPermission('administer content translation') && !$bundle_is_translatable) {
      $toggle_url = Url::fromRoute('language.content_settings_page', [], ['query' => $this->redirectDestination->getAsArray()])->toString();
      $form['translatable']['#description'] = $this->t('To configure translation for this field, <a href=":language-settings-url">enable language support</a> for this type.', [':language-settings-url' => $toggle_url]);
    }
    if ($field->isTranslatable()) {
      $element = $this->fieldSyncWidget->widget($field);
      if ($element) {
        $form['third_party_settings']['content_translation']['translation_sync'] = $element;
        $form['third_party_settings']['content_translation']['translation_sync']['#weight'] = -10;
      }
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for 'user_admin_settings' form.
   */
  #[Hook('form_user_admin_settings_alter')]
  public function userAccountSettingsFormAlter(array &$form, FormStateInterface $form_state): void {
    // Insert the new element just after 'anonymous_settings'.
    $index = array_search('anonymous_settings', array_keys($form));
    $form = array_slice($form, 0, $index + 1) + [
      'language' => [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#open' => TRUE,
        '#tree' => TRUE,
      ],
    ] + $form;
    $form_state->set(['content_translation', 'key'], 'language');
    $form['language'] += $this->contentTranslationWidget->getWidget('user', 'user', $form, $form_state);
  }

  /**
   * Implements hook_form_FORM_ID_alter() for 'comment_admin_overview' form.
   */
  #[Hook('form_comment_admin_overview_alter')]
  public function commentAdminOverviewFormAlter(array &$form, FormStateInterface $form_state): void {
    $storage = $this->entityTypeManager->getStorage('comment');
    $destination = $this->redirectDestination->getAsArray();

    $comments = $storage->loadMultiple(array_keys($form['comments']['#options']));
    foreach ($comments as $cid => $comment) {
      if ($this->contentTranslationManager->access($comment)->isAllowed()) {
        $comment_uri_options = $comment->toUrl()->getOptions() + ['query' => $destination];
        $form['comments']['#options'][$cid]['operations']['data']['#links']['translate'] = [
          'title' => $this->t('Translate'),
          'url' => $comment->toUrl('drupal:content-translation-overview', $comment_uri_options),
        ];
      }
    }
  }

}
