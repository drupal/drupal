<?php

declare(strict_types=1);

namespace Drupal\content_translation\Hook;

use Drupal\content_translation\BundleTranslationSettingsInterface;
use Drupal\content_translation\ContentTranslationManager;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\content_translation\FieldSyncWidget;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Form hook implementations for content_translation.
 *
 * This is separate from the other form hook class to reduce the number of
 * dependencies. These hooks are only invoked on the language edit form.
 */
class ContentTranslationFormLanguageHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountInterface $currentUser,
    protected readonly ContentTranslationManagerInterface $contentTranslationManager,
    protected readonly EntityFieldManagerInterface $entityFieldManager,
    protected readonly EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    protected readonly FieldSyncWidget $fieldSyncWidget,
  ) {}

  /**
   * Implements hook_form_FORM_ID_alter() for language_content_settings_form().
   */
  #[Hook('form_language_content_settings_form_alter')]
  public function formLanguageContentSettingsFormAlter(array &$form, FormStateInterface $form_state) : void {
    // Inject into the content language settings the translation settings if the
    // user has the required permission.
    if (!$this->currentUser->hasPermission('administer content translation')) {
      return;
    }

    $default = $form['entity_types']['#default_value'];
    foreach ($default as $entity_type_id => $enabled) {
      $default[$entity_type_id] = $enabled || $this->contentTranslationManager->isEnabled($entity_type_id) ? $entity_type_id : FALSE;
    }
    $form['entity_types']['#default_value'] = $default;

    $form['#attached']['library'][] = 'content_translation/drupal.content_translation.admin';

    foreach ($form['#labels'] as $entity_type_id => $label) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $storage_definitions = $entity_type instanceof ContentEntityTypeInterface ? $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id) : [];

      $entity_type_translatable = $this->contentTranslationManager->isSupported($entity_type_id);
      foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type_id) as $bundle => $bundle_info) {
        // Here we do not want the widget to be altered and hold also the
        // "Enable translation" checkbox, which would be redundant. Hence we
        // add this key to be able to skip alterations. Alter the title and
        // display the message about UI integration.
        $form['settings'][$entity_type_id][$bundle]['settings']['language']['#content_translation_skip_alter'] = TRUE;
        if (!$entity_type_translatable) {
          $form['settings'][$entity_type_id]['#title'] = $this->t('@label (Translation is not supported).', ['@label' => $entity_type->getLabel()]);
          continue;
        }

        // Displayed the "shared fields widgets" toggle.
        if ($this->contentTranslationManager instanceof BundleTranslationSettingsInterface) {
          $settings = $this->contentTranslationManager->getBundleTranslationSettings($entity_type_id, $bundle);
          $force_hidden = ContentTranslationManager::isPendingRevisionSupportEnabled($entity_type_id, $bundle);
          $form['settings'][$entity_type_id][$bundle]['settings']['content_translation']['untranslatable_fields_hide'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Hide non translatable fields on translation forms'),
            '#default_value' => $force_hidden || !empty($settings['untranslatable_fields_hide']),
            '#disabled' => $force_hidden,
            '#description' => $force_hidden ? $this->t('Moderated content requires non-translatable fields to be edited in the original language form.') : '',
            '#states' => [
              'visible' => [
                ':input[name="settings[' . $entity_type_id . '][' . $bundle . '][translatable]"]' => [
                  'checked' => TRUE,
                ],
              ],
            ],
          ];
        }

        $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
        if ($fields) {
          foreach ($fields as $field_name => $definition) {
            if ($definition->isComputed() || (!empty($storage_definitions[$field_name]) && $this->isFieldTranslatabilityConfigurable($entity_type, $storage_definitions[$field_name]))) {
              $form['settings'][$entity_type_id][$bundle]['fields'][$field_name] = [
                '#label' => $definition->getLabel(),
                '#type' => 'checkbox',
                '#default_value' => $definition->isTranslatable(),
              ];
              // Display the column translatability configuration widget.
              $column_element = $this->fieldSyncWidget->widget($definition, "settings[{$entity_type_id}][{$bundle}][columns][{$field_name}]");
              if ($column_element) {
                $form['settings'][$entity_type_id][$bundle]['columns'][$field_name] = $column_element;
              }
            }
          }
          if (!empty($form['settings'][$entity_type_id][$bundle]['fields'])) {
            // Only show the checkbox to enable translation if the bundles in
            // the entity might have fields and if there are fields to
            // translate.
            $form['settings'][$entity_type_id][$bundle]['translatable'] = [
              '#type' => 'checkbox',
              '#default_value' => $this->contentTranslationManager->isEnabled($entity_type_id, $bundle),
            ];
          }
        }
      }
    }

    $form['#validate'][] = [static::class, 'languageContentSettingsValidate'];
    $form['#submit'][] = [static::class, 'languageContentSettingsSubmit'];
  }

  /**
   * Form validation handler for content_translation_admin_settings_form().
   *
   * @see content_translation_admin_settings_form_submit()
   */
  public static function languageContentSettingsValidate(array $form, FormStateInterface $form_state): void {
    $settings = &$form_state->getValue('settings');
    foreach ($settings as $entity_type => $entity_settings) {
      foreach ($entity_settings as $bundle => $bundle_settings) {
        if (!empty($bundle_settings['translatable'])) {
          $name = "settings][$entity_type][$bundle][translatable";

          $translatable_fields = isset($settings[$entity_type][$bundle]['fields']) ? array_filter($settings[$entity_type][$bundle]['fields']) : FALSE;
          if (empty($translatable_fields)) {
            $t_args = ['%bundle' => $form['settings'][$entity_type][$bundle]['settings']['#label']];
            $form_state->setErrorByName($name, t('At least one field needs to be translatable to enable %bundle for translation.', $t_args));
          }

          $values = $bundle_settings['settings']['language'];
          if (empty($values['language_alterable']) && \Drupal::languageManager()->isLanguageLocked($values['langcode'])) {
            $locked_languages = [];
            foreach (\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_LOCKED) as $language) {
              $locked_languages[] = $language->getName();
            }
            $form_state->setErrorByName($name, t('Translation is not supported if language is always one of: @locked_languages', ['@locked_languages' => implode(', ', $locked_languages)]));
          }
        }
      }
    }
  }

  /**
   * Form submission handler for content_translation_admin_settings_form().
   *
   * @see content_translation_admin_settings_form_validate()
   */
  public static function languageContentSettingsSubmit(array $form, FormStateInterface $form_state): void {
    /** @var \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager */
    $content_translation_manager = \Drupal::service('content_translation.manager');
    $entity_types = $form_state->getValue('entity_types');
    $settings = &$form_state->getValue('settings');

    // If an entity type is not translatable all its bundles and fields must be
    // marked as non-translatable. Similarly, if a bundle is made
    // non-translatable all of its fields will be not translatable.
    foreach ($settings as $entity_type_id => &$entity_settings) {
      foreach ($entity_settings as $bundle => &$bundle_settings) {
        $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);
        if (!empty($bundle_settings['translatable'])) {
          $bundle_settings['translatable'] = $bundle_settings['translatable'] && $entity_types[$entity_type_id];
        }
        if (!empty($bundle_settings['fields'])) {
          foreach ($bundle_settings['fields'] as $field_name => $translatable) {
            $translatable = $translatable && $bundle_settings['translatable'];
            // If we have column settings and no column is translatable, no
            // point in making the field translatable.
            if (isset($bundle_settings['columns'][$field_name]) && !array_filter($bundle_settings['columns'][$field_name])) {
              $translatable = FALSE;
            }
            $field_config = $fields[$field_name]->getConfig($bundle);
            if ($field_config->isTranslatable() != $translatable) {
              $field_config
                ->setTranslatable($translatable)
                ->save();
            }
          }
        }
        if (isset($bundle_settings['translatable'])) {
          // Store whether a bundle has translation enabled or not.
          $content_translation_manager->setEnabled($entity_type_id, $bundle, $bundle_settings['translatable']);

          // Store any other bundle settings.
          if ($content_translation_manager instanceof BundleTranslationSettingsInterface) {
            $content_translation_manager->setBundleTranslationSettings($entity_type_id, $bundle, $bundle_settings['settings']['content_translation']);
          }

          // Save translation_sync settings.
          if (!empty($bundle_settings['columns'])) {
            foreach ($bundle_settings['columns'] as $field_name => $column_settings) {
              $field_config = $fields[$field_name]->getConfig($bundle);
              if ($field_config->isTranslatable()) {
                $field_config->setThirdPartySetting('content_translation', 'translation_sync', $column_settings);
              }
              // If the field does not have translatable enabled we need to
              // reset the sync settings to their defaults.
              else {
                $field_config->unsetThirdPartySetting('content_translation', 'translation_sync');
              }
              $field_config->save();
            }
          }
        }
      }
    }

    // Ensure menu router information is correctly rebuilt.
    \Drupal::service('router.builder')->setRebuildNeeded();
  }

  /**
   * Checks whether translatability should be configurable for a field.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   *   The field storage definition.
   *
   * @return bool
   *   TRUE if field translatability can be configured, FALSE otherwise.
   */
  protected function isFieldTranslatabilityConfigurable(EntityTypeInterface $entity_type, FieldStorageDefinitionInterface $definition) {
    // Allow to configure only fields supporting multilingual storage. We skip
    // our own fields as they are always translatable. Additionally we skip a
    // set of well-known fields implementing entity system business logic.
    return $definition->isTranslatable() &&
      $definition->getProvider() != 'content_translation' &&
      !in_array($definition->getName(), [
        $entity_type->getKey('langcode'),
        $entity_type->getKey('default_langcode'),
        'revision_translation_affected',
      ]);
  }

}
