<?php

namespace Drupal\content_translation\Hook;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\content_translation\ContentTranslationManager;
use Drupal\content_translation\BundleTranslationSettingsInterface;
use Drupal\language\ContentLanguageSettingsInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for content_translation.
 */
class ContentTranslationHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.content_translation':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Content Translation module allows you to translate content, comments, content blocks, taxonomy terms, users and other <a href=":field_help" title="Field module help, with background on content entities">content entities</a>. Together with the modules <a href=":language">Language</a>, <a href=":config-trans">Configuration Translation</a>, and <a href=":locale">Interface Translation</a>, it allows you to build multilingual websites. For more information, see the <a href=":translation-entity">online documentation for the Content Translation module</a>.', [
          ':locale' => \Drupal::moduleHandler()->moduleExists('locale') ? Url::fromRoute('help.page', [
            'name' => 'locale',
          ])->toString() : '#',
          ':config-trans' => \Drupal::moduleHandler()->moduleExists('config_translation') ? Url::fromRoute('help.page', [
            'name' => 'config_translation',
          ])->toString() : '#',
          ':language' => Url::fromRoute('help.page', [
            'name' => 'language',
          ])->toString(),
          ':translation-entity' => 'https://www.drupal.org/docs/8/core/modules/content-translation',
          ':field_help' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
        ]) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Enabling translation') . '</dt>';
        $output .= '<dd>' . t('In order to translate content, the website must have at least two <a href=":url">languages</a>. When that is the case, you can enable translation for the desired content entities on the <a href=":translation-entity">Content language</a> page. When enabling translation you can choose the default language for content and decide whether to show the language selection field on the content editing forms.', [
          ':url' => Url::fromRoute('entity.configurable_language.collection')->toString(),
          ':translation-entity' => Url::fromRoute('language.content_settings_page')->toString(),
          ':language-help' => Url::fromRoute('help.page', [
            'name' => 'language',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Enabling field translation') . '</dt>';
        $output .= '<dd>' . t('You can define which fields of a content entity can be translated. For example, you might want to translate the title and body field while leaving the image field untranslated. If you exclude a field from being translated, it will still show up in the content editing form, but any changes made to that field will be applied to <em>all</em> translations of that content.') . '</dd>';
        $output .= '<dt>' . t('Translating content') . '</dt>';
        $output .= '<dd>' . t('If translation is enabled you can translate a content entity via the Translate tab (or Translate link). The Translations page of a content entity gives an overview of the translation status for the current content and lets you add, edit, and delete its translations. This process is similar for every translatable content entity on your site.') . '</dd>';
        $output .= '<dt>' . t('Changing the source language for a translation') . '</dt>';
        $output .= '<dd>' . t('When you add a new translation, the original text you are translating is displayed in the edit form as the <em>source</em>. If at least one translation of the original content already exists when you add a new translation, you can choose either the original content (default) or one of the other translations as the source, using the select list in the Source language section. After saving the translation, the chosen source language is then listed on the Translate tab of the content.') . '</dd>';
        $output .= '<dt>' . t('Setting status of translations') . '</dt>';
        $output .= '<dd>' . t('If you edit a translation in one language you may want to set the status of the other translations as <em>out-of-date</em>. You can set this status by selecting the <em>Flag other translations as outdated</em> checkbox in the Translation section of the content editing form. The status will be visible on the Translations page.') . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'language.content_settings_page':
        $output = '';
        if (!\Drupal::languageManager()->isMultilingual()) {
          $output .= '<p>' . t('Before you can translate content, there must be at least two languages added on the <a href=":url">languages administration</a> page.', [
            ':url' => Url::fromRoute('entity.configurable_language.collection')->toString(),
          ]) . '</p>';
        }
        return $output;
    }
  }

  /**
   * Implements hook_language_types_info_alter().
   */
  #[Hook('language_types_info_alter')]
  public function languageTypesInfoAlter(array &$language_types): void {
    // Make content language negotiation configurable by removing the 'locked'
    // flag.
    $language_types[LanguageInterface::TYPE_CONTENT]['locked'] = FALSE;
    unset($language_types[LanguageInterface::TYPE_CONTENT]['fixed']);
  }

  /**
   * Implements hook_entity_type_alter().
   *
   * The content translation UI relies on the entity info to provide its features.
   * See the documentation of hook_entity_type_build() in the Entity API
   * documentation for more details on all the entity info keys that may be
   * defined.
   *
   * To make Content Translation automatically support an entity type some keys
   * may need to be defined, but none of them is required unless the entity path
   * is different from the usual /ENTITY_TYPE/{ENTITY_TYPE} pattern (for instance
   * "/taxonomy/term/{taxonomy_term}"). Here are a list of those optional keys:
   * - canonical: This key (in the 'links' entity info property) must be defined
   *   if the entity path is different from /ENTITY_TYPE/{ENTITY_TYPE}
   * - translation: This key (in the 'handlers' entity annotation property)
   *   specifies the translation handler for the entity type. If an entity type is
   *   translatable and no translation handler is defined,
   *   \Drupal\content_translation\ContentTranslationHandler will be assumed.
   *   Every translation handler must implement
   *   \Drupal\content_translation\ContentTranslationHandlerInterface.
   * - content_translation_ui_skip: By default, entity types that do not have a
   *   canonical link template cannot be enabled for translation. Setting this key
   *   to TRUE overrides that. When that key is set, the Content Translation
   *   module will not provide any UI for translating the entity type, and the
   *   entity type should implement its own UI. For instance, this is useful for
   *   entity types that are embedded into others for editing (which would not
   *   need a canonical link, but could still support translation).
   * - content_translation_metadata: To implement its business logic the content
   *   translation UI relies on various metadata items describing the translation
   *   state. The default implementation is provided by
   *   \Drupal\content_translation\ContentTranslationMetadataWrapper, which is
   *   relying on one field for each metadata item (field definitions are provided
   *   by the translation handler). Entity types needing to customize this
   *   behavior can specify an alternative class through the
   *   'content_translation_metadata' key in the entity type definition. Every
   *   content translation metadata wrapper needs to implement
   *   \Drupal\content_translation\ContentTranslationMetadataWrapperInterface.
   *
   * If the entity paths match the default pattern above and there is no need for
   * an entity-specific translation handler, Content Translation will provide
   * built-in support for the entity. However enabling translation for each
   * translatable bundle will be required.
   *
   * @see \Drupal\Core\Entity\Annotation\EntityType
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    // Provide defaults for translation info.
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    foreach ($entity_types as $entity_type) {
      if ($entity_type->isTranslatable()) {
        if (!$entity_type->hasHandlerClass('translation')) {
          $entity_type->setHandlerClass('translation', 'Drupal\content_translation\ContentTranslationHandler');
        }
        if (!$entity_type->get('content_translation_metadata')) {
          $entity_type->set('content_translation_metadata', 'Drupal\content_translation\ContentTranslationMetadataWrapper');
        }
        if (!$entity_type->getFormClass('content_translation_deletion')) {
          $entity_type->setFormClass('content_translation_deletion', '\Drupal\content_translation\Form\ContentTranslationDeleteForm');
        }
        $translation = $entity_type->get('translation');
        if (!$translation || !isset($translation['content_translation'])) {
          $translation['content_translation'] = [];
        }
        if ($entity_type->hasLinkTemplate('canonical')) {
          // Provide default route names for the translation paths.
          if (!$entity_type->hasLinkTemplate('drupal:content-translation-overview')) {
            $translations_path = $entity_type->getLinkTemplate('canonical') . '/translations';
            $entity_type->setLinkTemplate('drupal:content-translation-overview', $translations_path);
            $entity_type->setLinkTemplate('drupal:content-translation-add', $translations_path . '/add/{source}/{target}');
            $entity_type->setLinkTemplate('drupal:content-translation-edit', $translations_path . '/edit/{language}');
            $entity_type->setLinkTemplate('drupal:content-translation-delete', $translations_path . '/delete/{language}');
          }
          // @todo Remove this as soon as menu access checks rely on the
          //   controller. See https://www.drupal.org/node/2155787.
          $translation['content_translation'] += ['access_callback' => 'content_translation_translate_access'];
        }
        $entity_type->set('translation', $translation);
      }
      $entity_type->addConstraint('ContentTranslationSynchronizedFields');
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   *
   * Installs Content Translation's field storage definitions for the target
   * entity type, if required.
   *
   * Also clears the bundle information cache so that the bundle's translatability
   * will be set properly.
   *
   * @see content_translation_entity_bundle_info_alter()
   * @see \Drupal\content_translation\ContentTranslationManager::isEnabled()
   */
  #[Hook('language_content_settings_insert')]
  public function languageContentSettingsInsert(ContentLanguageSettingsInterface $settings) {
    if ($settings->getThirdPartySetting('content_translation', 'enabled', FALSE)) {
      _content_translation_install_field_storage_definitions($settings->getTargetEntityTypeId());
    }
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   *
   * Installs Content Translation's field storage definitions for the target
   * entity type, if required.
   *
   * Also clears the bundle information cache so that the bundle's translatability
   * will be changed properly.
   *
   * @see content_translation_entity_bundle_info_alter()
   * @see \Drupal\content_translation\ContentTranslationManager::isEnabled()
   */
  #[Hook('language_content_settings_update')]
  public function languageContentSettingsUpdate(ContentLanguageSettingsInterface $settings) {
    $original_settings = $settings->original;
    if ($settings->getThirdPartySetting('content_translation', 'enabled', FALSE) && !$original_settings->getThirdPartySetting('content_translation', 'enabled', FALSE)) {
      _content_translation_install_field_storage_definitions($settings->getTargetEntityTypeId());
    }
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();
  }

  /**
   * Implements hook_entity_bundle_info_alter().
   */
  #[Hook('entity_bundle_info_alter')]
  public function entityBundleInfoAlter(&$bundles): void {
    /** @var \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager */
    $content_translation_manager = \Drupal::service('content_translation.manager');
    foreach ($bundles as $entity_type_id => &$info) {
      foreach ($info as $bundle => &$bundle_info) {
        $bundle_info['translatable'] = $content_translation_manager->isEnabled($entity_type_id, $bundle);
        if ($bundle_info['translatable'] && $content_translation_manager instanceof BundleTranslationSettingsInterface) {
          $settings = $content_translation_manager->getBundleTranslationSettings($entity_type_id, $bundle);
          // If pending revision support is enabled for this bundle, we need to
          // hide untranslatable field widgets, otherwise changes in pending
          // revisions might be overridden by changes in later default revisions.
          $bundle_info['untranslatable_fields.default_translation_affected'] = !empty($settings['untranslatable_fields_hide']) || ContentTranslationManager::isPendingRevisionSupportEnabled($entity_type_id, $bundle);
        }
      }
    }
  }

  /**
   * Implements hook_entity_base_field_info().
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    /** @var \Drupal\content_translation\ContentTranslationManagerInterface $manager */
    $manager = \Drupal::service('content_translation.manager');
    $entity_type_id = $entity_type->id();
    if ($manager->isSupported($entity_type_id)) {
      $definitions = $manager->getTranslationHandler($entity_type_id)->getFieldDefinitions();
      $installed_storage_definitions = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions($entity_type_id);
      // We return metadata storage fields whenever content translation is enabled
      // or it was enabled before, so that we keep translation metadata around
      // when translation is disabled.
      // @todo Re-evaluate this approach and consider removing field storage
      //   definitions and the related field data if the entity type has no bundle
      //   enabled for translation.
      // @see https://www.drupal.org/node/2907777
      if ($manager->isEnabled($entity_type_id) || array_intersect_key($definitions, $installed_storage_definitions)) {
        return $definitions;
      }
    }
  }

  /**
   * Implements hook_field_info_alter().
   *
   * Content translation extends the @FieldType annotation with following key:
   * - column_groups: contains information about the field type properties
   *   which columns should be synchronized across different translations and
   *   which are translatable. This is useful for instance to translate the
   *   "alt" and "title" textual elements of an image field, while keeping the
   *   same image on every translation. Each group has the following keys:
   *   - title: Title of the column group.
   *   - translatable: (optional) If the column group should be translatable by
   *     default, defaults to FALSE.
   *   - columns: (optional) A list of columns of this group. Defaults to the
   *     name of the group as the single column.
   *   - require_all_groups_for_translation: (optional) Set to TRUE to enforce
   *     that making this column group translatable requires all others to be
   *     translatable too.
   *
   * @see Drupal\image\Plugin\Field\FieldType\ImageItem
   */
  #[Hook('field_info_alter')]
  public function fieldInfoAlter(&$info): void {
    foreach ($info as $key => $settings) {
      // Supply the column_groups key if it's not there.
      if (empty($settings['column_groups'])) {
        $info[$key]['column_groups'] = [];
      }
    }
  }

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    if ($entity->hasLinkTemplate('drupal:content-translation-overview') && content_translation_translate_access($entity)->isAllowed()) {
      $operations['translate'] = [
        'title' => t('Translate'),
        'url' => $entity->toUrl('drupal:content-translation-overview'),
        'weight' => 50,
      ];
    }
    return $operations;
  }

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    // Add the content translation entity link definition to Views data for entity
    // types having translation enabled.
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    /** @var \Drupal\content_translation\ContentTranslationManagerInterface $manager */
    $manager = \Drupal::service('content_translation.manager');
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $base_table = $entity_type->getBaseTable();
      if (isset($data[$base_table]) && $entity_type->hasLinkTemplate('drupal:content-translation-overview') && $manager->isEnabled($entity_type_id)) {
        $t_arguments = ['@entity_type_label' => $entity_type->getLabel()];
        $data[$base_table]['translation_link'] = [
          'field' => [
            'title' => t('Link to translate @entity_type_label', $t_arguments),
            'help' => t('Provide a translation link to the @entity_type_label.', $t_arguments),
            'id' => 'content_translation_link',
          ],
        ];
      }
    }
  }

  /**
   * Implements hook_menu_links_discovered_alter().
   */
  #[Hook('menu_links_discovered_alter')]
  public function menuLinksDiscoveredAlter(array &$links): void {
    // Clarify where translation settings are located.
    $links['language.content_settings_page']['title'] = new TranslatableMarkup('Content language and translation');
    $links['language.content_settings_page']['description'] = new TranslatableMarkup('Configure language and translation support for content.');
  }

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
    // Let the content translation handler alter the content entity form. This can
    // be the 'add' or 'edit' form. It also tries a 'default' form in case neither
    // of the aforementioned forms are defined.
    if ($entity instanceof ContentEntityInterface && $entity->isTranslatable() && count($entity->getTranslationLanguages()) > 1 && in_array($op, ['edit', 'add', 'default'], TRUE)) {
      $controller = \Drupal::entityTypeManager()->getHandler($entity->getEntityTypeId(), 'translation');
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
   * Implements hook_language_fallback_candidates_OPERATION_alter().
   *
   * Performs language fallback for inaccessible translations.
   */
  #[Hook('language_fallback_candidates_entity_view_alter')]
  public function languageFallbackCandidatesEntityViewAlter(&$candidates, $context): void {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $context['data'];
    $entity_type_id = $entity->getEntityTypeId();
    /** @var \Drupal\content_translation\ContentTranslationManagerInterface $manager */
    $manager = \Drupal::service('content_translation.manager');
    if ($manager->isEnabled($entity_type_id, $entity->bundle())) {
      /** @var \Drupal\content_translation\ContentTranslationHandlerInterface $handler */
      $handler = \Drupal::entityTypeManager()->getHandler($entity->getEntityTypeId(), 'translation');
      foreach ($entity->getTranslationLanguages() as $langcode => $language) {
        $metadata = $manager->getTranslationMetadata($entity->getTranslation($langcode));
        if (!$metadata->isPublished()) {
          $access = $handler->getTranslationAccess($entity, 'update');
          $entity->addCacheableDependency($access);
          if (!$access->isAllowed()) {
            // If the user has no translation update access, also check view
            // access for that translation, to allow other modules to allow access
            // to unpublished translations.
            $access = $entity->getTranslation($langcode)->access('view', NULL, TRUE);
            $entity->addCacheableDependency($access);
            if (!$access->isAllowed()) {
              unset($candidates[$langcode]);
            }
          }
        }
      }
    }
  }

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo() {
    $extra = [];
    $bundle_info_service = \Drupal::service('entity_type.bundle.info');
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type => $info) {
      foreach ($bundle_info_service->getBundleInfo($entity_type) as $bundle => $bundle_info) {
        if (\Drupal::service('content_translation.manager')->isEnabled($entity_type, $bundle)) {
          $extra[$entity_type][$bundle]['form']['translation'] = [
            'label' => t('Translation'),
            'description' => t('Translation settings'),
            'weight' => 10,
          ];
        }
      }
    }
    return $extra;
  }

  /**
   * Implements hook_form_FORM_ID_alter() for 'field_config_edit_form'.
   */
  #[Hook('form_field_config_edit_form_alter')]
  public function formFieldConfigEditFormAlter(array &$form, FormStateInterface $form_state) : void {
    $field = $form_state->getFormObject()->getEntity();
    $bundle_is_translatable = \Drupal::service('content_translation.manager')->isEnabled($field->getTargetEntityTypeId(), $field->getTargetBundle());
    $form['translatable'] = [
      '#type' => 'checkbox',
      '#title' => t('Users may translate this field'),
      '#default_value' => $field->isTranslatable(),
      '#weight' => -1,
      '#disabled' => !$bundle_is_translatable,
      '#access' => $field->getFieldStorageDefinition()->isTranslatable(),
    ];
    // Provide helpful pointers for administrators.
    if (\Drupal::currentUser()->hasPermission('administer content translation') && !$bundle_is_translatable) {
      $toggle_url = Url::fromRoute('language.content_settings_page', [], ['query' => \Drupal::destination()->getAsArray()])->toString();
      $form['translatable']['#description'] = t('To configure translation for this field, <a href=":language-settings-url">enable language support</a> for this type.', [':language-settings-url' => $toggle_url]);
    }
    if ($field->isTranslatable()) {
      \Drupal::moduleHandler()->loadInclude('content_translation', 'inc', 'content_translation.admin');
      $element = content_translation_field_sync_widget($field);
      if ($element) {
        $form['third_party_settings']['content_translation']['translation_sync'] = $element;
        $form['third_party_settings']['content_translation']['translation_sync']['#weight'] = -10;
      }
    }
  }

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity) {
    if ($entity instanceof ContentEntityInterface && $entity->isTranslatable() && !$entity->isNew() && isset($entity->original)) {
      /** @var \Drupal\content_translation\ContentTranslationManagerInterface $manager */
      $manager = \Drupal::service('content_translation.manager');
      if (!$manager->isEnabled($entity->getEntityTypeId(), $entity->bundle())) {
        return;
      }
      $langcode = $entity->language()->getId();
      $source_langcode = !$entity->original->hasTranslation($langcode) ? $manager->getTranslationMetadata($entity)->getSource() : NULL;
      \Drupal::service('content_translation.synchronizer')->synchronizeFields($entity, $langcode, $source_langcode);
    }
  }

  /**
   * Implements hook_element_info_alter().
   */
  #[Hook('element_info_alter')]
  public function elementInfoAlter(&$type): void {
    if (isset($type['language_configuration'])) {
      $type['language_configuration']['#process'][] = 'content_translation_language_configuration_element_process';
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for language_content_settings_form().
   */
  #[Hook('form_language_content_settings_form_alter')]
  public function formLanguageContentSettingsFormAlter(array &$form, FormStateInterface $form_state) : void {
    \Drupal::moduleHandler()->loadInclude('content_translation', 'inc', 'content_translation.admin');
    _content_translation_form_language_content_settings_form_alter($form, $form_state);
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(&$page): void {
    $cache = CacheableMetadata::createFromRenderArray($page);
    $route_match = \Drupal::routeMatch();
    // If the current route has no parameters, return.
    if (!($route = $route_match->getRouteObject()) || !($parameters = $route->getOption('parameters'))) {
      return;
    }
    $is_front = \Drupal::service('path.matcher')->isFrontPage();
    // Determine if the current route represents an entity.
    foreach ($parameters as $name => $options) {
      if (!isset($options['type']) || !str_starts_with($options['type'], 'entity:')) {
        continue;
      }
      $entity = $route_match->getParameter($name);
      if ($entity instanceof ContentEntityInterface && $entity->hasLinkTemplate('canonical')) {
        // Current route represents a content entity. Build hreflang links.
        foreach ($entity->getTranslationLanguages() as $language) {
          // Skip any translation that cannot be viewed.
          $translation = $entity->getTranslation($language->getId());
          $access = $translation->access('view', NULL, TRUE);
          $cache->addCacheableDependency($access);
          if (!$access->isAllowed()) {
            continue;
          }
          if ($is_front) {
            // If the current page is front page, do not create hreflang links
            // from the entity route, just add the languages to root path.
            $url = Url::fromRoute('<front>', [], ['absolute' => TRUE, 'language' => $language])->toString();
          }
          else {
            $url = $entity->toUrl('canonical')->setOption('language', $language)->setAbsolute()->toString();
          }
          $page['#attached']['html_head_link'][] = [['rel' => 'alternate', 'hreflang' => $language->getId(), 'href' => $url]];
        }
      }
      // Since entity was found, no need to iterate further.
      break;
    }
    // Apply updated caching information.
    $cache->applyTo($page);
  }

}
