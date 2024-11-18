<?php

namespace Drupal\media_library\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\media\MediaTypeForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\media_library\MediaLibraryState;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;
use Drupal\media_library\Form\OEmbedForm;
use Drupal\media_library\Form\FileUploadForm;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for media_library.
 */
class MediaLibraryHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.media_library':
        $output = '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Media Library module provides a rich, visual interface for managing media, and allows media to be reused in entity reference fields or embedded into text content. It overrides the <a href=":media-collection">media administration page</a>, allowing users to toggle between the existing table-style interface and a new grid-style interface for browsing and performing administrative operations on media.', [
          ':media-collection' => Url::fromRoute('entity.media.collection')->toString(),
        ]) . '</p>';
        $output .= '<p>' . t('To learn more about media management, begin by reviewing the <a href=":media-help">documentation for the Media module</a>. For more information about the media library and related functionality, see the <a href=":media-library-handbook">online documentation for the Media Library module</a>.', [
          ':media-help' => Url::fromRoute('help.page', [
            'name' => 'media',
          ])->toString(),
          ':media-library-handbook' => 'https://www.drupal.org/docs/8/core/modules/media-library-module',
        ]) . '</p>';
        $output .= '<h2>' . t('Selection dialog') . '</h2>';
        $output .= '<p>' . t('When selecting media for an entity reference field or a text editor, Media Library opens a modal dialog to help users easily find and select media. The modal dialog can toggle between a grid-style and table-style interface, and new media items can be uploaded directly into it.') . '</p>';
        $output .= '<p>' . t('Within the dialog, media items are divided up by type. If more than one media type can be selected by the user, the available types will be displayed as a set of vertical tabs. To users who have appropriate permissions, each media type may also present a short form allowing you to upload or create new media items of that type.') . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Grid-style vs. table-style interface') . '</dt>';
        $output .= '<dd>' . t('The Media Library module provides a new grid-style interface for the media administration page that displays media as thumbnails, with minimal textual information, allowing users to visually browse media in their site. The existing table-style interface is better suited to displaying additional information about media items, in addition to being more accessible to users with assistive technology.') . '</dd>';
        $output .= '<dt>' . t('Reusing media in entity reference fields') . '</dt>';
        $output .= '<dd>' . t('Any entity reference field that references media can use the media library. To enable, configure the form display for the field to use the "Media library" widget.') . '</dd>';
        $output .= '<dt>' . t('Embedding media in text content') . '</dt>';
        $output .= '<dd>' . t('To use the media library within CKEditor, you must add the "Insert from Media Library" button to the CKEditor toolbar, and enable the "Embed media" filter in the text format associated with the text editor.') . '</dd>';
        $output .= '</dl>';
        $output .= '<h2>' . t('Customize') . '</h2>';
        $output .= '<ul>';
        $output .= '<li>';
        if (\Drupal::moduleHandler()->moduleExists('views_ui') && \Drupal::currentUser()->hasPermission('administer views')) {
          $output .= t('Both the table-style and grid-style interfaces are regular views and can be customized via the <a href=":views-ui">Views UI</a>, including sorting and filtering. This is the case for both the administration page and the modal dialog.', [':views_ui' => Url::fromRoute('entity.view.collection')->toString()]);
        }
        else {
          $output .= t('Both the table-style and grid-style interfaces are regular views and can be customized via the Views UI, including sorting and filtering. This is the case for both the administration page and the modal dialog.');
        }
        $output .= '</li>';
        $output .= '<li>' . t('In the grid-style interface, the fields that are displayed (including which image style is used for images) can be customized by configuring the "Media library" view mode for each of your <a href=":media-types">media types</a>. The thumbnail images in the grid-style interface can be customized by configuring the "Media Library thumbnail (220×220)" image style.', [
          ':media-types' => Url::fromRoute('entity.media_type.collection')->toString(),
        ]) . '</li>';
        $output .= '<li>' . t('When adding new media items within the modal dialog, the fields that are displayed can be customized by configuring the "Media library" form mode for each of your <a href=":media-types">media types</a>.', [
          ':media-types' => Url::fromRoute('entity.media_type.collection')->toString(),
        ]) . '</li>';
        $output .= '</ul>';
        return $output;
    }
  }

  /**
   * Implements hook_media_source_info_alter().
   */
  #[Hook('media_source_info_alter')]
  public function mediaSourceInfoAlter(array &$sources): void {
    if (empty($sources['audio_file']['forms']['media_library_add'])) {
      $sources['audio_file']['forms']['media_library_add'] = FileUploadForm::class;
    }
    if (empty($sources['file']['forms']['media_library_add'])) {
      $sources['file']['forms']['media_library_add'] = FileUploadForm::class;
    }
    if (empty($sources['image']['forms']['media_library_add'])) {
      $sources['image']['forms']['media_library_add'] = FileUploadForm::class;
    }
    if (empty($sources['video_file']['forms']['media_library_add'])) {
      $sources['video_file']['forms']['media_library_add'] = FileUploadForm::class;
    }
    if (empty($sources['oembed:video']['forms']['media_library_add'])) {
      $sources['oembed:video']['forms']['media_library_add'] = OEmbedForm::class;
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'media__media_library' => [
        'base hook' => 'media',
      ],
      'media_library_wrapper' => [
        'render element' => 'element',
      ],
      'media_library_item' => [
        'render element' => 'element',
      ],
    ];
  }

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view) {
    $add_classes = function (&$option, array $classes_to_add) {
      $classes = $option ? preg_split('/\s+/', trim($option)) : [];
      $classes = array_filter($classes);
      $classes = array_merge($classes, $classes_to_add);
      $option = implode(' ', array_unique($classes));
    };
    if ($view->id() === 'media_library') {
      if ($view->current_display === 'page') {
        $add_classes($view->style_plugin->options['row_class'], ['js-media-library-item', 'js-click-to-select']);
        if (array_key_exists('media_bulk_form', $view->field)) {
          $add_classes($view->field['media_bulk_form']->options['element_class'], ['js-click-to-select-checkbox']);
        }
      }
      elseif (str_starts_with($view->current_display, 'widget')) {
        if (array_key_exists('media_library_select_form', $view->field)) {
          $add_classes($view->field['media_library_select_form']->options['element_wrapper_class'], ['js-click-to-select-checkbox']);
        }
        $add_classes($view->display_handler->options['css_class'], ['js-media-library-view']);
      }
      $add_classes($view->style_plugin->options['row_class'], ['js-media-library-item', 'js-click-to-select']);
      if ($view->display_handler->options['defaults']['css_class']) {
        $add_classes($view->displayHandlers->get('default')->options['css_class'], ['js-media-library-view']);
      }
      else {
        $add_classes($view->display_handler->options['css_class'], ['js-media-library-view']);
      }
    }
  }

  /**
   * Implements hook_views_post_render().
   */
  #[Hook('views_post_render')]
  public function viewsPostRender(ViewExecutable $view, &$output, CachePluginBase $cache) {
    if ($view->id() === 'media_library') {
      $output['#attached']['library'][] = 'media_library/view';
      if (str_starts_with($view->current_display, 'widget')) {
        try {
          $query = MediaLibraryState::fromRequest($view->getRequest())->all();
        }
        catch (\InvalidArgumentException $e) {
          // MediaLibraryState::fromRequest() will throw an exception if the view
          // is being previewed, since not all required query parameters will be
          // present. In a preview, however, this can be omitted since we're
          // merely previewing.
          // @todo Use the views API for checking for the preview mode when it
          //   lands. https://www.drupal.org/project/drupal/issues/3060855
          if (empty($view->preview) && empty($view->live_preview)) {
            throw $e;
          }
        }
        // If the current query contains any parameters we use to contextually
        // filter the view, ensure they persist across AJAX rebuilds.
        // The ajax_path is shared for all AJAX views on the page, but our query
        // parameters are prefixed and should not interfere with any other views.
        // @todo Rework or remove this in https://www.drupal.org/node/2983451
        if (!empty($query)) {
          $ajax_path =& $output['#attached']['drupalSettings']['views']['ajax_path'];
          $parsed_url = UrlHelper::parse($ajax_path);
          $query = array_merge($query, $parsed_url['query']);
          // Reset the pager so that the user starts on the first page.
          unset($query['page']);
          $ajax_path = $parsed_url['path'] . '?' . UrlHelper::buildQuery($query);
        }
      }
    }
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) : void {
    // Add a process callback to ensure that the media library view's exposed
    // filters submit button is not moved to the modal dialog's button area.
    if ($form_id === 'views_exposed_form' && str_starts_with($form['#id'], 'views-exposed-form-media-library-widget')) {
      $form['#after_build'][] = '_media_library_views_form_media_library_after_build';
    }
    // Configures media_library displays when a type is submitted.
    if ($form_state->getFormObject() instanceof MediaTypeForm) {
      $form['actions']['submit']['#submit'][] = '_media_library_media_type_form_submit';
      // @see field_ui_form_alter()
      if (isset($form['actions']['save_continue'])) {
        $form['actions']['save_continue']['#submit'][] = '_media_library_media_type_form_submit';
      }
    }
  }

  /**
   * Implements hook_field_ui_preconfigured_options_alter().
   */
  #[Hook('field_ui_preconfigured_options_alter')]
  public function fieldUiPreconfiguredOptionsAlter(array &$options, $field_type): void {
    // If the field is not an "entity_reference"-based field, bail out.
    $class = \Drupal::service('plugin.manager.field.field_type')->getPluginClass($field_type);
    if (!is_a($class, EntityReferenceItem::class, TRUE)) {
      return;
    }
    // Set the default field widget for media to be the Media library.
    if (!empty($options['media'])) {
      $options['media']['entity_form_display']['type'] = 'media_library_widget';
    }
  }

  /**
   * Implements hook_local_tasks_alter().
   *
   * Removes tasks for the Media library if the view display no longer exists.
   */
  #[Hook('local_tasks_alter')]
  public function localTasksAlter(&$local_tasks): void {
    /** @var \Symfony\Component\Routing\RouteCollection $route_collection */
    $route_collection = \Drupal::service('router')->getRouteCollection();
    foreach (['media_library.grid', 'media_library.table'] as $key) {
      if (isset($local_tasks[$key]) && !$route_collection->get($local_tasks[$key]['route_name'])) {
        unset($local_tasks[$key]);
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('image_style_access')]
  public function imageStyleAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Prevent the fallback 'media_library' image style from being deleted.
    // @todo Lock the image style instead of preventing delete access.
    //   https://www.drupal.org/project/drupal/issues/2247293
    if ($operation === 'delete' && $entity->id() === 'media_library') {
      return AccessResult::forbidden();
    }
  }

}
