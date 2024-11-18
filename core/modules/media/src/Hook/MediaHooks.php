<?php

namespace Drupal\media\Hook;

use Drupal\views\ViewExecutable;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldTypeCategoryManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\field\FieldConfigInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for media.
 */
class MediaHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.media':
        $output = '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Media module manages the creation, editing, deletion, settings, and display of media. Items are typically images, documents, slideshows, YouTube videos, tweets, Instagram photos, etc. You can reference media items from any other content on your site. For more information, see the <a href=":media">online documentation for the Media module</a>.', [':media' => 'https://www.drupal.org/docs/8/core/modules/media']) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Creating media items') . '</dt>';
        $output .= '<dd>' . t('When a new media item is created, the Media module records basic information about it, including the author, date of creation, and the <a href=":media-type">media type</a>. It also manages the <em>publishing options</em>, which define whether or not the item is published. Default settings can be configured for each type of media on your site.', [
          ':media-type' => Url::fromRoute('entity.media_type.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Listing media items') . '</dt>';
        $output .= '<dd>' . t('Media items are listed at the <a href=":media-collection">media administration page</a>.', [
          ':media-collection' => Url::fromRoute('entity.media.collection')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Creating custom media types') . '</dt>';
        $output .= '<dd>' . t('The Media module gives users with the <em>Administer media types</em> permission the ability to <a href=":media-new">create new media types</a> in addition to the default ones already configured. Each media type has an associated media source (such as the image source) which support thumbnail generation and metadata extraction. Fields managed by the <a href=":field">Field module</a> may be added for storing that metadata, such as width and height, as well as any other associated values.', [
          ':media-new' => Url::fromRoute('entity.media_type.add_form')->toString(),
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Creating revisions') . '</dt>';
        $output .= '<dd>' . t('The Media module also enables you to create multiple versions of any media item, and revert to older versions using the <em>Revision information</em> settings.') . '</dd>';
        $output .= '<dt>' . t('User permissions') . '</dt>';
        $output .= '<dd>' . t('The Media module makes a number of permissions available, which can be set by role on the <a href=":permissions">permissions page</a>.', [
          ':permissions' => Url::fromRoute('user.admin_permissions.module', [
            'modules' => 'media',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Adding media to other content') . '</dt>';
        $output .= '<dd>' . t('Users with permission to administer content types can add media support by adding a media reference field to the content type on the content type administration page. (The same is true of block types, taxonomy terms, user profiles, and other content that supports fields.) A media reference field can refer to any configured media type. It is possible to allow multiple media types in the same field.') . '</dd>';
        $output .= '</dl>';
        $output .= '<h2>' . t('Differences between Media, File, and Image reference fields') . '</h2>';
        $output .= '<p>' . t('<em>Media</em> reference fields offer several advantages over basic <em>File</em> and <em>Image</em> references:') . '</p>';
        $output .= '<ul>';
        $output .= '<li>' . t('Media reference fields can reference multiple media types in the same field.') . '</li>';
        $output .= '<li>' . t('Fields can also be added to media types themselves, which means that custom metadata like descriptions and taxonomy tags can be added for the referenced media. (Basic file and image fields do not support this.)') . '</li>';
        $output .= '<li>' . t('Media types for audio and video files are provided by default, so there is no need for additional configuration to upload these media.') . '</li>';
        $output .= '<li>' . t('Contributed or custom projects can provide additional media sources (such as third-party websites, Twitter, etc.).') . '</li>';
        $output .= '<li>' . t('Existing media items can be reused on any other content items with a media reference field.') . '</li>';
        $output .= '</ul>';
        $output .= '<p>' . t('Use <em>Media</em> reference fields for most files, images, audio, videos, and remote media. Use <em>File</em> or <em>Image</em> reference fields when creating your own media types, or for legacy files and images created before installing the Media module.') . '</p>';
        return $output;
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'media' => [
        'render element' => 'elements',
      ],
      'media_reference_help' => [
        'render element' => 'element',
        'base hook' => 'field_multiple_value_form',
      ],
      'media_oembed_iframe' => [
        'variables' => [
          'resource' => NULL,
          'media' => NULL,
          'placeholder_token' => '',
        ],
      ],
      'media_embed_error' => [
        'variables' => [
          'message' => NULL,
          'attributes' => [],
        ],
      ],
    ];
  }

  /**
   * Implements hook_entity_access().
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'delete' && $entity instanceof FieldConfigInterface && $entity->getTargetEntityTypeId() === 'media') {
      /** @var \Drupal\media\MediaTypeInterface $media_type */
      $media_type = \Drupal::entityTypeManager()->getStorage('media_type')->load($entity->getTargetBundle());
      return AccessResult::forbiddenIf($entity->id() === 'media.' . $media_type->id() . '.' . $media_type->getSource()->getConfiguration()['source_field']);
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_field_ui_preconfigured_options_alter().
   */
  #[Hook('field_ui_preconfigured_options_alter')]
  public function fieldUiPreconfiguredOptionsAlter(array &$options, $field_type): void {
    // If the field is not an "entity_reference"-based field, bail out.
    /** @var \Drupal\Core\Field\FieldTypePluginManager $field_type_manager */
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    $class = $field_type_manager->getPluginClass($field_type);
    if (!is_a($class, 'Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem', TRUE)) {
      return;
    }
    // Set the default formatter for media in entity reference fields to be the
    // "Rendered entity" formatter.
    if (!empty($options['media'])) {
      $options['media']['description'] = t('Field to reference media. Allows uploading and selecting from uploaded media.');
      $options['media']['weight'] = -25;
      $options['media']['category'] = FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY;
      $options['media']['entity_view_display']['type'] = 'entity_reference_entity_view';
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_field_ui_field_storage_add_form_alter')]
  public function formFieldUiFieldStorageAddFormAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    // Provide some help text to aid users decide whether they need a Media,
    // File, or Image reference field.
    $description_text = t('Use <em>Media</em> reference fields for most files, images, audio, videos, and remote media. Use <em>File</em> or <em>Image</em> reference fields when creating your own media types, or for legacy files and images created before installing the Media module.');
    if (\Drupal::moduleHandler()->moduleExists('help')) {
      $description_text .= ' ' . t('For more information, see the <a href="@help_url">Media help page</a>.', [
        '@help_url' => Url::fromRoute('help.page', [
          'name' => 'media',
        ])->toString(),
      ]);
    }
    $field_types = ['file_upload', 'field_ui:entity_reference:media'];
    if (in_array($form_state->getValue('new_storage_type'), $field_types)) {
      $form['group_field_options_wrapper']['description_wrapper'] = ['#type' => 'item', '#markup' => $description_text];
    }
  }

  /**
   * Implements hook_field_widget_complete_form_alter().
   */
  #[Hook('field_widget_complete_form_alter')]
  public function fieldWidgetCompleteFormAlter(array &$field_widget_complete_form, FormStateInterface $form_state, array $context): void {
    $elements =& $field_widget_complete_form['widget'];
    // Do not alter the default settings form.
    if ($context['default']) {
      return;
    }
    // Only act on entity reference fields that reference media.
    $field_type = $context['items']->getFieldDefinition()->getType();
    $target_type = $context['items']->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type');
    if ($field_type !== 'entity_reference' || $target_type !== 'media') {
      return;
    }
    // Autocomplete widgets need different help text than options widgets.
    $widget_plugin_id = $context['widget']->getPluginId();
    if (in_array($widget_plugin_id, ['entity_reference_autocomplete', 'entity_reference_autocomplete_tags'])) {
      $is_autocomplete = TRUE;
    }
    else {
      // @todo We can't yet properly alter non-autocomplete fields. Resolve this
      //   in https://www.drupal.org/node/2943020 and remove this condition.
      return;
    }
    $elements['#media_help'] = [];
    // Retrieve the media bundle list and add information for the user based on
    // which bundles are available to be created or referenced.
    $settings = $context['items']->getFieldDefinition()->getSetting('handler_settings');
    $allowed_bundles = !empty($settings['target_bundles']) ? $settings['target_bundles'] : [];
    $add_url = _media_get_add_url($allowed_bundles);
    if ($add_url) {
      $elements['#media_help']['#media_add_help'] = t('Create your media on the <a href=":add_page" target="_blank">media add page</a> (opens a new window), then add it by name to the field below.', [':add_page' => $add_url]);
    }
    $elements['#theme'] = 'media_reference_help';
    // @todo template_preprocess_field_multiple_value_form() assumes this key
    //   exists, but it does not exist in the case of a single widget that
    //   accepts multiple values. This is for some reason necessary to use
    //   our template for the entity_autocomplete_tags widget.
    //   Research and resolve this in https://www.drupal.org/node/2943020.
    if (empty($elements['#cardinality_multiple'])) {
      $elements['#cardinality_multiple'] = NULL;
    }
    // Use the title set on the element if it exists, otherwise fall back to the
    // field label.
    $elements['#media_help']['#original_label'] = $elements['#title'] ?? $context['items']->getFieldDefinition()->getLabel();
    // Customize the label for the field widget.
    // @todo Research a better approach https://www.drupal.org/node/2943024.
    $use_existing_label = t('Use existing media');
    if (!empty($elements[0]['target_id']['#title'])) {
      $elements[0]['target_id']['#title'] = $use_existing_label;
    }
    if (!empty($elements['#title'])) {
      $elements['#title'] = $use_existing_label;
    }
    if (!empty($elements['target_id']['#title'])) {
      $elements['target_id']['#title'] = $use_existing_label;
    }
    // This help text is only relevant for autocomplete widgets. When the user
    // is presented with options, they don't need to type anything or know what
    // types of media are allowed.
    if ($is_autocomplete) {
      $elements['#media_help']['#media_list_help'] = t('Type part of the media name.');
      $overview_url = Url::fromRoute('entity.media.collection');
      if ($overview_url->access()) {
        $elements['#media_help']['#media_list_link'] = t('See the <a href=":list_url" target="_blank">media list</a> (opens a new window) to help locate media.', [':list_url' => $overview_url->toString()]);
      }
      $all_bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('media');
      $bundle_labels = array_map(function ($bundle) use ($all_bundles) {
          return $all_bundles[$bundle]['label'];
      }, $allowed_bundles);
      $elements['#media_help']['#allowed_types_help'] = t('Allowed media types: %types', ['%types' => implode(", ", $bundle_labels)]);
    }
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    if (\Drupal::config('media.settings')->get('standalone_url')) {
      /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
      $entity_type = $entity_types['media'];
      $entity_type->setLinkTemplate('canonical', '/media/{media}');
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_filter_format_edit_form_alter')]
  public function formFilterFormatEditFormAlter(array &$form, FormStateInterface $form_state, $form_id) : void {
    // Add an additional validate callback so we can ensure the order of filters
    // is correct.
    $form['#validate'][] = 'media_filter_format_edit_form_validate';
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_filter_format_add_form_alter')]
  public function formFilterFormatAddFormAlter(array &$form, FormStateInterface $form_state, $form_id) : void {
    // Add an additional validate callback so we can ensure the order of filters
    // is correct.
    $form['#validate'][] = 'media_filter_format_edit_form_validate';
  }

  /**
   * Implements hook_field_widget_single_element_form_alter().
   */
  #[Hook('field_widget_single_element_form_alter')]
  public function fieldWidgetSingleElementFormAlter(&$element, FormStateInterface $form_state, $context): void {
    // Add an attribute so that text editors plugins can pass the host entity's
    // language, allowing it to present entities in the same language.
    if (!empty($element['#type']) && $element['#type'] == 'text_format') {
      $element['#attributes']['data-media-embed-host-entity-langcode'] = $context['items']->getLangcode();
    }
  }

  /**
   * Implements hook_views_query_substitutions().
   */
  #[Hook('views_query_substitutions')]
  public function viewsQuerySubstitutions(ViewExecutable $view) {
    $account = \Drupal::currentUser();
    return [
      '***VIEW_OWN_UNPUBLISHED_MEDIA***' => (int) $account->hasPermission('view own unpublished media'),
      '***ADMINISTER_MEDIA***' => (int) $account->hasPermission('administer media'),
    ];
  }

  /**
   * Implements hook_field_type_category_info_alter().
   */
  #[Hook('field_type_category_info_alter')]
  public function fieldTypeCategoryInfoAlter(&$definitions): void {
    // The `media` field type belongs in the `general` category, so the libraries
    // need to be attached using an alter hook.
    $definitions[FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY]['libraries'][] = 'media/drupal.media-icon';
  }

}
