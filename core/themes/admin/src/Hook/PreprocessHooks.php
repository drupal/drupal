<?php

declare(strict_types=1);

namespace Drupal\admin\Hook;

use Drupal\admin\Helper;
use Drupal\admin\Settings;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Asset\AssetQueryStringInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

// cspell:ignore imce

/**
 * Provides preprocess implementations.
 */
final class PreprocessHooks implements TrustedCallbackInterface {

  use StringTranslationTrait;

  /**
   * Constructs the theme related hooks.
   */
  public function __construct(
    protected readonly ThemeExtensionList $themeExtensionList,
    protected readonly ThemeHandlerInterface $themeHandler,
    protected readonly ThemeManagerInterface $themeManager,
    protected readonly RequestStack $requestStack,
    protected readonly AssetQueryStringInterface $assetQueryString,
    protected readonly RouteMatchInterface $currentRouteMatch,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly AccountInterface $currentUser,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly BlockManagerInterface $blockManager,
    protected readonly RendererInterface $renderer,
    protected readonly ThemeSettingsProvider $themeSettingsProvider,
    protected readonly FileUrlGeneratorInterface $fileUrlGenerator,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected ClassResolverInterface $classResolver,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return [
      'lazyToolbarUserPicture',
    ];
  }

  /**
   * Static lazy loader for the user picture.
   *
   * @return array
   *   The user picture as a render array.
   */
  public static function lazyToolbarUserPicture(): array {
    return \Drupal::classResolver(PreprocessHooks::class)->toolbarUserPicture();
  }

  /**
   * Implements hook_preprocess_HOOK() for admin_block.
   */
  #[Hook('preprocess_admin_block')]
  public function adminBlock(array &$variables): void {
    if (!empty($variables['block']['content'])) {
      $variables['block']['content']['#attributes']['class'][] = 'admin-list--panel';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for admin_block.
   */
  #[Hook('preprocess_admin_block_content')]
  public function adminBlockContent(array &$variables): void {
    foreach ($variables['content'] as &$item) {
      $link_attributes = $item['url']->getOption('attributes') ?: [];
      $link_attributes['class'][] = 'admin-item__link';
      $item['url']->setOption('attributes', $link_attributes);
      $item['link'] = Link::fromTextAndUrl($item['title'], $item['url']);

      if (empty($item['description']) || empty($item['description']['#markup'])) {
        unset($item['description']);
      }
    }
  }

  /**
   * Implements hook_preprocess_block() for block content.
   *
   * Disables contextual links for all blocks except for layout builder blocks.
   */
  #[Hook('preprocess_block')]
  public function block(array &$variables): void {
    if (isset($variables['title_suffix']['contextual_links']) && !isset($variables['elements']['#contextual_links']['layout_builder_block'])) {
      unset($variables['title_suffix']['contextual_links'], $variables['elements']['#contextual_links']);

      $variables['attributes']['class'] = array_diff($variables['attributes']['class'], ['contextual-region']);
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for block_content_add_list.
   *
   * Makes block_content_add_list variables compatible with entity_add_list.
   */
  #[Hook('preprocess_block_content_add_list')]
  public function blockContentAddList(array &$variables): void {
    if (!empty($variables['content'])) {
      $query = $this->requestStack->getCurrentRequest()->query->all();
      /** @var \Drupal\block_content\BlockContentTypeInterface $type */
      foreach ($variables['content'] as $type) {
        $label = $type->label();
        $description = $type->getDescription();
        $type_id = $type->id();
        $add_url = Url::fromRoute('block_content.add_form', [
          'block_content_type' => $type_id,
        ], [
          'query' => $query,
        ]);
        $variables['bundles'][$type_id] = [
          'label' => $label,
          'add_link' => Link::fromTextAndUrl($label, $add_url),
          'description' => [],
        ];

        if (!empty($description)) {
          $variables['bundles'][$type_id]['description'] = [
            '#markup' => $description,
          ];
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for breadcrumb.
   */
  #[Hook('preprocess_breadcrumb')]
  public function breadcrumb(array &$variables): void {
    if (empty($variables['breadcrumb'])) {
      return;
    }

    $cacheability = new CacheableMetadata();
    $cacheability->addCacheContexts(['route']);

    $entity = NULL;
    $route_name = $this->currentRouteMatch->getRouteName();
    // Entity will be found in the route parameters.
    if (($route = $this->currentRouteMatch->getRouteObject()) && ($parameters = $route->getOption('parameters'))) {
      // Determine if the current route represents an entity.
      foreach ($parameters as $name => $options) {
        if (isset($options['type']) && str_starts_with($options['type'], 'entity:')) {
          $routeEntity = $this->currentRouteMatch->getParameter($name);
          if ($routeEntity instanceof ContentEntityInterface && $routeEntity->hasLinkTemplate('canonical')) {
            $entity = $routeEntity;
          }
          break;
        }
      }
    }

    $operation_label = NULL;
    if ($entity !== NULL) {
      $url = $entity->toUrl();
      $entity_type_id = $entity->getEntityTypeId();

      $entity_type = $entity->getEntityType();
      $type_label = $entity_type->getSingularLabel();
      $bundle_key = $entity_type->getKey('bundle');

      if ($bundle_key) {
        $bundle_entity = $entity->get($bundle_key)->entity;
        $type_label = $bundle_entity->label();
      }

      if ($entity_type->id() === 'user') {
        $type_label = 'account';
      }

      $operation_labels = [
        '#entity.(?<entityTypeId>.+).canonical#' => $this->t('View @bundle', ['@bundle' => $type_label]),
        '#entity.(?<entityTypeId>.+).delete_form#' => $this->t('Delete @bundle', ['@bundle' => $type_label]),
        '#entity.(?<entityTypeId>.+).delete_multiple_form#' => $this->t('Delete @bundle', ['@bundle' => $type_label]),
        '#entity.(?<entityTypeId>.+).edit_form#' => $this->t('Edit @bundle', ['@bundle' => $type_label]),
        '#entity.(?<entityTypeId>.+).add_form#' => $this->t('Add @bundle', ['@bundle' => $type_label]),
        '#entity.(?<entityTypeId>.+).add_page#' => $this->t('Add @bundle', ['@bundle' => $type_label]),
        '#entity.(?<entityTypeId>.+).reset_form#' => $this->t('Reset @bundle', ['@bundle' => $type_label]),
        '#entity.(?<entityTypeId>.+).cancel_form#' => $this->t('Cancel @bundle', ['@bundle' => $type_label]),
        '#entity.(?<entityTypeId>.+).clone_form#' => $this->t('Clone @bundle', ['@bundle' => $type_label]),
      ];

      foreach ($operation_labels as $regex => $label) {
        if (preg_match($regex, $route_name)) {
          $operation_label = $label;
          break;
        }
      }

      $url_access = $url->access(NULL, TRUE);
      $cacheability->addCacheableDependency($url_access);

      // Media handling.
      if ($entity_type_id === 'media') {
        $media_config = $this->configFactory->get('media.settings');
        $cacheability->addCacheableDependency($media_config);

        if (!$media_config->get('standalone_url')) {
          $url = Url::fromRoute('<front>');
        }
      }

      // Custom block handling (a custom block cannot be viewed standalone).
      if ($entity_type_id === 'block_content') {
        $url = Url::fromRoute('<front>');
      }
    }

    // Back to site item.
    foreach ($variables['breadcrumb'] as $key => $item) {
      if ($key === 0) {
        $variables['breadcrumb'][$key]['text'] = $this->t('Back to site');
        $variables['breadcrumb'][$key]['attributes']['title'] = $this->t('Return to site content');

        if (isset($url, $url_access) && $url_access->isAllowed()) {
          // Link to the canonical route of the entity.
          $variables['breadcrumb'][$key]['url'] = $url;
        }
        else {
          // Let escapeAdmin override the return URL.
          $variables['breadcrumb'][$key]['attributes']['data'] = 'data-gin-toolbar-escape-admin';
        }
      }
      elseif (isset($url) && $item['url'] === $url->setAbsolute(FALSE)->toString()) {
        // Remove as we already have the back to site link set.
        unset($variables['breadcrumb'][$key]);
      }
    }

    // Adjust breadcrumb for nodes: unset all items, except home link.
    if ($entity instanceof NodeInterface) {
      foreach ($variables['breadcrumb'] as $key => $item) {
        if ($key > 0) {
          unset($variables['breadcrumb'][$key]);
        }
      }
    }

    // Adjust breadcrumb for entities.
    if ($operation_label !== NULL) {
      // Add bundle info.
      $variables['breadcrumb'][] = [
        'text' => $operation_label,
        'url' => '',
      ];
    }

    $cacheability->applyTo($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for datetime_wrapper.
   */
  #[Hook('preprocess_datetime_wrapper')]
  public function datetimeWrapper(array &$variables): void {
    if (!empty($variables['element']['#errors'])) {
      $variables['title_attributes']['class'][] = 'has-error';
    }

    if (!empty($variables['element']['#disabled'])) {
      $variables['title_attributes']['class'][] = 'is-disabled';

      if (!empty($variables['description_attributes'])) {
        $variables['description_attributes']->addClass('is-disabled');
      }
    }
    $this->preprocessDescriptionToggle($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for details.
   */
  #[Hook('preprocess_details')]
  public function details(array &$variables): void {
    // @todo Revisit when https://www.drupal.org/node/3056089 is in.
    $element = $variables['element'];

    if (!empty($element['#accordion_item'])) {
      // Details should appear as an accordion item.
      $variables['accordion_item'] = TRUE;
    }

    if (!empty($element['#accordion'])) {
      // Details should appear as a standalone accordion.
      $variables['accordion'] = TRUE;
    }

    if (!empty($element['#theme']) &&  $element['#theme'] === 'file_widget_multiple') {
      // Mark the details required if needed. If the file widget allows
      // uploading multiple files, the required state is checked by checking the
      // state of the first child.
      $variables['required'] = $element[0]['#required'] ?? !empty($element['#required']);

      // If the error is the same as the one in the multiple field widget
      // element, we have to avoid displaying it twice. Stark has this issue as
      // well.
      // @todo Revisit when https://www.drupal.org/node/3084906 is fixed.
      if (isset($element['#errors'], $variables['errors']) && $element['#errors'] === $variables['errors']) {
        unset($variables['errors']);
      }
    }

    $variables['disabled'] = !empty($element['#disabled']);
    $this->preprocessDescriptionToggle($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for entity_add_list.
   */
  #[Hook('preprocess_entity_add_list')]
  public function entityAddList(array &$variables): void {
    // Remove description if empty.
    foreach ($variables['bundles'] as $type_id => $values) {
      if (isset($values['description']['#markup']) && empty($values['description']['#markup'])) {
        $variables['bundles'][$type_id]['description'] = [];
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for field_multiple_value_form.
   */
  #[Hook('preprocess_field_multiple_value_form')]
  public function fieldMultipleValueForm(array &$variables): void {
    // Make disabled available for the template.
    $variables['disabled'] = !empty($variables['element']['#disabled']);

    if ($variables['multiple']) {
      // Add an additional CSS class to the field label table cell. The table
      // header cell should always exist unless removed by contrib.
      // @see \Drupal\Core\Field\FieldPreprocess::preprocessFieldMultipleValueForm().
      if (isset($variables['table']['#header'][0]['data']['#attributes'])) {
        $variables['table']['#header'][0]['data']['#attributes']->removeClass('label');
        $variables['table']['#header'][0]['data']['#attributes']->addClass('form-item__label', 'form-item__label--multiple-value-form');
      }

      if ($variables['disabled']) {
        $variables['table']['#attributes']['class'][] = 'tabledrag-disabled';
        $variables['table']['#attributes']['class'][] = 'js-tabledrag-disabled';

        // We will add the 'is-disabled' CSS class to the disabled table header
        // cells.
        $header_attributes['class'][] = 'is-disabled';
        foreach ($variables['table']['#header'] as &$cell) {
          if (is_array($cell) && isset($cell['data'])) {
            $cell += ['class' => []];
            $cell['class'][] = 'is-disabled';
          }
          else {
            // We have to modify the structure of this header cell.
            $cell = [
              'data' => $cell,
              'class' => ['is-disabled'],
            ];
          }
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for fieldset.
   */
  #[Hook('preprocess_fieldset')]
  public function fieldset(array &$variables): void {
    $element = $variables['element'];
    $composite_types = ['checkboxes', 'radios'];

    if (!empty($element['#type']) && !empty($variables['element']['#children_errors']) && in_array($element['#type'], $composite_types, TRUE)) {
      $variables['legend_span']['attributes']->addClass('has-error');
    }

    if (!empty($element['#disabled'])) {
      $variables['legend_span']['attributes']->addClass('is-disabled');

      if (!empty($variables['description']) && !empty($variables['description']['attributes'])) {
        $variables['description']['attributes']->addClass('is-disabled');
      }
    }

    // Remove 'container-inline' class from the main attributes and add a flag
    // instead.
    // @todo Remove this after https://www.drupal.org/node/3059593 has been
    //   resolved.
    if (!empty($variables['attributes']['class'])) {
      $container_inline_key = array_search('container-inline', $variables['attributes']['class'], TRUE);

      if ($container_inline_key !== FALSE) {
        unset($variables['attributes']['class'][$container_inline_key]);
        $variables['inline_items'] = TRUE;
      }
    }
    $this->preprocessDescriptionToggle($variables);
  }

  /**
   * Implements hook_preprocess_fieldset__media_library_widget().
   *
   * @todo Remove this when https://www.drupal.org/project/drupal/issues/2999549
   *   lands.
   *
   * @see \Drupal\media_library\Plugin\Field\FieldWidget\MediaLibraryWidget::formElement()
   */
  #[Hook('preprocess_fieldset__media_library_widget')]
  public function fieldsetMediaLibraryWidget(array &$variables): void {
    if (isset($variables['prefix']['weight_toggle'])) {
      $variables['prefix']['weight_toggle']['#attributes']['class'][] = 'action-link';
      $variables['prefix']['weight_toggle']['#attributes']['class'][] = 'action-link--extrasmall';
      $variables['prefix']['weight_toggle']['#attributes']['class'][] = 'action-link--icon-show';
      $variables['prefix']['weight_toggle']['#attributes']['class'][] = 'media-library-widget__toggle-weight';
    }
    if (isset($variables['suffix']['open_button'])) {
      $variables['suffix']['open_button']['#attributes']['class'][] = 'media-library-open-button';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for file_managed_file.
   */
  #[Hook('preprocess_file_managed_file')]
  public function fileManagedFile(array &$variables): void {
    // Produce the same renderable element structure as image widget has.
    $child_keys = Element::children($variables['element']);
    foreach ($child_keys as $child_key) {
      $variables['data'][$child_key] = $variables['element'][$child_key];
    }
    $this->fileAndImageWidget($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for file_widget_multiple.
   */
  #[Hook('preprocess_file_widget_multiple')]
  public function fileWidgetMultiple(array &$variables): void {
    $has_upload = FALSE;

    if (isset($variables['table']['#type']) && $variables['table']['#type'] === 'table') {
      // Add a variant class for the table.
      $variables['table']['#attributes']['class'][] = 'table-file-multiple-widget';

      // Mark table disabled if the field widget is disabled.
      if (isset($variables['element']['#disabled']) && $variables['element']['#disabled']) {
        $variables['table']['#attributes']['class'][] = 'tabledrag-disabled';
        $variables['table']['#attributes']['class'][] = 'js-tabledrag-disabled';

        // We will add the 'is-disabled' CSS class to the disabled table header
        // cells.
        foreach ($variables['table']['#header'] as &$cell) {
          if (is_array($cell) && isset($cell['data'])) {
            $cell += ['class' => []];
            $cell['class'][] = 'is-disabled';
          }
          else {
            // We have to modify the structure of this header cell.
            $cell = [
              'data' => $cell,
              'class' => ['is-disabled'],
            ];
          }
        }
      }

      // Mark operations column cells with a CSS class.
      if (isset($variables['table']['#rows']) && is_array($variables['table']['#rows'])) {
        foreach ($variables['table']['#rows'] as $row_key => $row) {
          if (isset($row['data']) && is_array($row['data'])) {
            $last_cell = end($row['data']);
            $last_cell_key = key($row['data']);

            if (is_array($last_cell['data'])) {
              foreach ($last_cell['data'] as $last_cell_item) {
                if (isset($last_cell_item['#attributes']['class']) && is_array($last_cell_item['#attributes']['class']) && in_array('remove-button', $last_cell_item['#attributes']['class'], TRUE)) {
                  $variables['table']['#rows'][$row_key]['data'][$last_cell_key] += ['class' => []];
                  $variables['table']['#rows'][$row_key]['data'][$last_cell_key]['class'][] = 'file-operations-cell';
                  break;
                }
              }
            }
          }
        }
      }

      // Add a CSS class to the table if an upload widget is present. This is
      // required for removing the border of the last table row.
      if (!empty($variables['element'])) {
        $element_keys = Element::children($variables['element']);

        foreach ($element_keys as $delta) {
          if (!isset($variables['element'][$delta]['upload']['#access']) || $variables['element'][$delta]['upload']['#access'] !== FALSE) {
            $has_upload = TRUE;
            break;
          }
        }
      }
      $variables['table']['#attributes']['class'][] = $has_upload ? 'table-file-multiple-widget--has-upload' : 'table-file-multiple-widget--no-upload';
    }

    $table_is_not_empty = !empty($variables['table']['#rows']);
    $table_is_accessible = !isset($variables['table']['#access']) || ($variables['table']['#access'] !== FALSE);
    $variables['has_table'] = $table_is_not_empty && $table_is_accessible;
  }

  /**
   * Implements hook_preprocess_HOOK() for filter_tips.
   */
  #[Hook('preprocess_filter_tips')]
  public function filterTips(array &$variables): void {
    $variables['#attached']['library'][] = 'filter/drupal.filter';
  }

  /**
   * Implements hook_preprocess_HOOK() for form_element.
   */
  #[Hook('preprocess_form_element')]
  public function formElement(array &$variables): void {
    if (!empty($variables['element']['#errors'])) {
      $variables['label']['#attributes']['class'][] = 'has-error';
    }

    if ($variables['disabled']) {
      $variables['label']['#attributes']['class'][] = 'is-disabled';

      if (!empty($variables['description']['attributes'])) {
        $variables['description']['attributes']->addClass('is-disabled');
      }
    }
    $this->preprocessDescriptionToggle($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for form_element__password.
   */
  #[Hook('preprocess_form_element__password')]
  public function formElementPassword(array &$variables): void {
    if (!empty($variables['element']['#array_parents']) && in_array('pass1', $variables['element']['#array_parents'], TRUE)) {
      // This is the main password form element.
      $variables['attributes']['class'][] = 'password-confirm__password';
    }

    if (!empty($variables['element']['#array_parents']) && in_array('pass2', $variables['element']['#array_parents'], TRUE)) {
      // This is the password confirm form element.
      $variables['attributes']['class'][] = 'password-confirm__confirm';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for form_element__password_confirm.
   */
  #[Hook('preprocess_form_element__password_confirm')]
  public function formElementPasswordConfirm(array &$variables): void {
    // Add CSS classes needed for theming the password confirm widget.
    $variables['attributes']['class'][] = 'password-confirm';
    $variables['attributes']['class'][] = 'is-initial';
    $variables['attributes']['class'][] = 'is-password-empty';
    $variables['attributes']['class'][] = 'is-confirm-empty';
  }

  /**
   * Implements hook_preprocess_HOOK() for HTML.
   */
  #[Hook('preprocess_html')]
  public function html(array &$variables): void {
    // Check if IMCE is active.
    if (isset($variables['attributes']['class']) && in_array('imce-page', $variables['attributes']['class'], TRUE)) {
      return;
    }

    // Get theme settings.
    $settings = Settings::getInstance();

    // Old way to set accent color.
    $variables['html_attributes']['data-gin-accent'] = $settings->get('preset_accent_color');

    // New way to set accent color.
    $accent_colors = Helper::accentColors();
    $preset = $settings->get('preset_accent_color');
    $accent_color = '';

    if ($preset === 'custom' && $settings->get('accent_color')) {
      $accent_color = $settings->get('accent_color');
    }
    elseif (isset($accent_colors[$preset]['hex'])) {
      $accent_color = $accent_colors[$preset]['hex'];
    }

    if ($accent_color) {
      $variables['html_attributes']['style'] = '--accent-base: ' . $accent_color . ';';
    }

    // Set focus color.
    $variables['html_attributes']['data-gin-focus'] = $settings->get('preset_focus_color');

    // High contrast mode.
    if ($settings->get('high_contrast_mode')) {
      $variables['html_attributes']['class'][] = 'gin--high-contrast-mode';
    }

    // Set layout density.
    $variables['html_attributes']['data-gin-layout-density'] = $settings->get('layout_density');

    // Edit form? Use the new admin Edit form layout.
    if (Helper::isContentForm()) {
      $variables['attributes']['class'][] = 'gin--edit-form';
    }

    // Only add toolbar/navigation class if user has permission.
    if (
      !$this->currentUser->hasPermission('access toolbar') &&
      !$this->currentUser->hasPermission('access navigation')
    ) {
      return;
    }

    // Check if Navigation module is active.
    if ($this->moduleHandler->moduleExists('navigation')) {
      $variables['attributes']['class'][] = 'gin--navigation';
    }
    else {
      // Set toolbar class.
      $variables['attributes']['class'][] = 'gin--toolbar';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for image_widget.
   */
  #[Hook('preprocess_image_widget')]
  public function imageWidget(array &$variables): void {
    // This prevents image widget templates from rendering preview container
    // HTML to users that do not have permission to access these previews.
    // @todo Revisit in https://drupal.org/node/953034
    // @todo Revisit in https://drupal.org/node/3114318
    if (isset($variables['data']['preview']['#access']) && $variables['data']['preview']['#access'] === FALSE) {
      unset($variables['data']['preview']);
    }
    $this->fileAndImageWidget($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for input.
   */
  #[Hook('preprocess_input')]
  public function input(array &$variables): void {
    if (
      !empty($variables['element']['#title_display']) &&
      $variables['element']['#title_display'] === 'attribute' &&
      !empty((string) $variables['element']['#title'])
    ) {
      $variables['attributes']['title'] = (string) $variables['element']['#title'];
    }

    $type_api = $variables['element']['#type'];
    $type_html = $variables['attributes']['type'];
    $text_types_html = [
      'text',
      'email',
      'tel',
      'number',
      'search',
      'password',
      'date',
      'time',
      'file',
      'color',
      'datetime-local',
      'url',
      'month',
      'week',
    ];

    if (in_array($type_html, $text_types_html, TRUE)) {
      $variables['attributes']['class'][] = 'form-element';
      $variables['attributes']['class'][] = Html::getClass('form-element--type-' . $type_html);
      $variables['attributes']['class'][] = Html::getClass('form-element--api-' . $type_api);

      if (!empty($variables['element']['#autocomplete_route_name'])) {
        $variables['autocomplete_message'] = $this->t('Loading…');
      }
    }

    if (in_array($type_html, ['checkbox', 'radio'])) {
      $variables['attributes']['class'][] = 'form-boolean';
      $variables['attributes']['class'][] = Html::getClass('form-boolean--type-' . $type_html);
    }
  }

  /**
   * Implements hook_preprocess_install_page().
   */
  #[Hook('preprocess_install_page')]
  public function installPage(array &$variables): void {
    // Admin has custom styling for the install page.
    $variables['#attached']['library'][] = 'admin/install-page';
  }

  /**
   * Implements hook_preprocess_item_list__media_library_add_form_media_list().
   *
   * This targets each new, unsaved media item added to the media library,
   * before they are saved.
   */
  #[Hook('preprocess_item_list__media_library_add_form_media_list')]
  public function itemListMediaLibraryAddFormMediaList(array &$variables): void {
    foreach ($variables['items'] as &$item) {
      $item['value']['preview']['#attributes']['class'][] = 'media-library-add-form__preview';
      $item['value']['fields']['#attributes']['class'][] = 'media-library-add-form__fields';
      $item['value']['remove_button']['#attributes']['class'][] = 'media-library-add-form__remove-button';

      $item['value']['remove_button']['#attributes']['class'][] = 'button--extrasmall';
      // #source_field_name is set by AddFormBase::buildEntityFormElement() to
      // help themes and form_alter hooks identify the source field.
      $fields = &$item['value']['fields'];
      $source_field_name = $fields['#source_field_name'];

      // Set this flag so we can remove the details element.
      $fields[$source_field_name]['widget'][0]['#do_not_wrap_in_details'] = TRUE;

      if (isset($fields[$source_field_name])) {
        $fields[$source_field_name]['#attributes']['class'][] = 'media-library-add-form__source-field';
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for links.
   */
  #[Hook('preprocess_links')]
  public function links(array &$variables): void {
    foreach ($variables['links'] as $links_item) {
      if (!empty($links_item['link']) && !empty($links_item['link']['#url']) && $links_item['link']['#url'] instanceof Url && $links_item['link']['#url']->isRouted()) {
        switch ($links_item['link']['#url']->getRouteName()) {
          case 'system.theme_settings_theme':
            $links_item['link'] = Helper::convertLinkToActionLink($links_item['link'], 'cog', 'small');
            break;

          case 'system.theme_uninstall':
            $links_item['link'] = Helper::convertLinkToActionLink($links_item['link'], 'ex', 'small');
            break;

          case 'system.theme_set_default':
            $links_item['link'] = Helper::convertLinkToActionLink($links_item['link'], 'checkmark', 'small');
            break;

          case 'system.theme_install':
            $links_item['link'] = Helper::convertLinkToActionLink($links_item['link'], 'plus', 'small');
            break;

        }
      }
    }

    // This makes it so array keys of #links items are added as a class. This
    // functionality was removed in Drupal 8.1, but still necessary in some
    // instances.
    // @todo Remove in https://drupal.org/node/3120962
    if (!empty($variables['links'])) {
      foreach ($variables['links'] as $key => $value) {
        if (!is_numeric($key)) {
          $class = Html::getClass($key);
          $variables['links'][$key]['attributes']->addClass($class);
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for links__action_links.
   */
  #[Hook('preprocess_links__action_links')]
  public function linksActionLinks(array &$variables): void {
    $variables['attributes']['class'][] = 'action-links';
    foreach ($variables['links'] as $delta => $link_item) {
      $variables['links'][$delta]['attributes']->addClass('action-links__item');
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for links__dropbutton.
   */
  #[Hook('preprocess_links__dropbutton')]
  public function linksDropbutton(array &$variables): void {
    // Add the right CSS class for the dropbutton list that helps reducing FOUC.
    if (!empty($variables['links'])) {
      $variables['attributes']['class'][] = count($variables['links']) > 1
        ? 'dropbutton--multiple'
        : 'dropbutton--single';
    }

    foreach ($variables['links'] as &$link_data) {
      $link_data['attributes']->addClass('dropbutton__item');
    }
  }

  /**
   * Implements hook_preprocess_links__media_library_menu().
   *
   * This targets the menu of available media types in the media library's modal
   * dialog.
   *
   * @todo Do this in the relevant template once
   *   https://www.drupal.org/project/drupal/issues/3088856 is resolved.
   */
  #[Hook('preprocess_links__media_library_menu')]
  public function linksMediaLibraryMenu(array &$variables): void {
    foreach ($variables['links'] as &$link) {
      // Add a class to the Media Library menu items.
      $link['attributes']->addClass('media-library-menu__item');
      $link['link']['#options']['attributes']['class'][] = 'media-library-menu__link';
    }
  }

  /**
   * Implements hook_preprocess_maintenance_page().
   */
  #[Hook('preprocess_maintenance_page')]
  public function maintenancePage(array &$variables): void {
    // Admin has custom styling for the maintenance page.
    $variables['#attached']['library'][] = 'admin/maintenance-page';
  }

  /**
   * Implements hook_preprocess_media_library_item__small().
   *
   * This targets each pre-selected media item selected when adding new media in
   * the modal media library dialog.
   */
  #[Hook('preprocess_media_library_item__small')]
  public function mediaLibraryItemSmall(array &$variables): void {
    $variables['content']['select']['#attributes']['class'][] = 'media-library-item__click-to-select-checkbox';
  }

  /**
   * Implements hook_preprocess_media_library_item__widget().
   *
   * This targets each media item selected in an entity reference field.
   */
  #[Hook('preprocess_media_library_item__widget')]
  public function mediaLibraryItemWidget(array &$variables): void {
    $variables['content']['remove_button']['#attributes']['class'][] = 'media-library-item__remove';
    $variables['content']['remove_button']['#attributes']['class'][] = 'icon-link';
  }

  /**
   * Implements hook_preprocess_HOOK() for menu_local_action.
   */
  #[Hook('preprocess_menu_local_action')]
  public function menuLocalAction(array &$variables): void {
    $variables['link']['#options']['attributes']['class'][] = 'button--primary';
    $variables['attributes']['class'][] = 'local-actions__item';
    $legacy_class_key = array_search('button-action', $variables['link']['#options']['attributes']['class'], TRUE);

    if ($legacy_class_key !== FALSE) {
      $variables['link']['#options']['attributes']['class'][$legacy_class_key] = 'button--action';
    }

    // Are we displaying an edit form?
    if (Helper::formActions()) {
      $classes = &$variables['link']['#options']['attributes']['class'];
      $classes = array_filter($classes, static function ($e) {
        return $e !== 'button--primary';
      });
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for menu-local-task templates.
   */
  #[Hook('preprocess_menu_local_task')]
  public function menuLocalTask(array &$variables): void {
    $variables['link']['#options']['attributes']['class'][] = 'tabs__link';
    $variables['link']['#options']['attributes']['class'][] = 'js-tabs-link';

    // Ensure is-active class is set when the tab is active. The generic active
    // link handler applies stricter comparison rules than what is necessary for
    // tabs.
    if (isset($variables['is_active']) && $variables['is_active'] === TRUE) {
      $variables['link']['#options']['attributes']['class'][] = 'is-active';
    }

    if (isset($variables['element']['#level'])) {
      $variables['level'] = $variables['element']['#level'];
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for menu-local-task Views UI templates.
   */
  #[Hook('preprocess_menu_local_task__views_ui')]
  public function menuLocalTaskViewsUi(array &$variables): void {
    // Remove 'tabs__link' without adding a new class because it couldn't be
    // used reliably.
    // @see https://www.drupal.org/node/3051605
    $link_class_index = array_search('tabs__link', $variables['link']['#options']['attributes']['class'], TRUE);
    if ($link_class_index !== FALSE) {
      unset($variables['link']['#options']['attributes']['class'][$link_class_index]);
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for menu-local-tasks templates.
   *
   * Use preprocess hook to set #attached to child elements because they will be
   * processed by Twig and \Drupal::service('renderer')->render() will be
   * invoked.
   */
  #[Hook('preprocess_menu_local_tasks')]
  public function menuLocalTasks(array &$variables): void {
    if (!empty($variables['primary'])) {
      $variables['primary']['#attached'] = [
        'library' => [
          'admin/drupal.nav-tabs',
        ],
      ];
    }
    elseif (!empty($variables['secondary'])) {
      $variables['secondary']['#attached'] = [
        'library' => [
          'admin/drupal.nav-tabs',
        ],
      ];
    }

    foreach (Element::children($variables['primary']) as $key) {
      $variables['primary'][$key]['#level'] = 'primary';
    }
    foreach (Element::children($variables['secondary']) as $key) {
      $variables['secondary'][$key]['#level'] = 'secondary';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for navigation.
   */
  #[Hook('preprocess_navigation')]
  public function navigation(array &$variables): void {
    // Get theme configs.
    $logo_default = Settings::getInstance()->getDefault('logo.use_default');
    $variables['icon_path'] = !$logo_default ? Settings::getInstance()->getDefault('logo.path') : '';
    $variables['navigation'] = $this->moduleHandler->moduleExists('navigation');
    $variables['is_backend'] = TRUE;

    // Attach the new drupal navigation styles.
    $variables['#attached']['library'][] = 'admin/navigation';
  }

  /**
   * Implements hook_preprocess_HOOK() for node_add_list.
   *
   * Makes node_add_list variables compatible with entity_add_list.
   */
  #[Hook('preprocess_node_add_list')]
  public function nodeAddList(array &$variables): void {
    if (!empty($variables['content'])) {
      /** @var \Drupal\node\NodeTypeInterface $type */
      foreach ($variables['content'] as $type) {
        $label = $type->label();
        $description = $type->getDescription();
        $type_id = $type->id();
        $add_url = Url::fromRoute('node.add', ['node_type' => $type_id]);
        $variables['bundles'][$type_id] = [
          'label' => $label,
          'add_link' => Link::fromTextAndUrl($label, $add_url),
          'description' => [],
        ];
        if (!empty($description)) {
          $variables['bundles'][$type_id]['description'] = [
            '#markup' => $description,
          ];
        }
      }
      $variables['attributes']['class'][] = 'node-type-list';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for page.
   */
  #[Hook('preprocess_page')]
  public function page(array &$variables): void {
    // Required for allowing sub-theming admin.
    $activeThemeName = $this->themeManager->getActiveTheme()->getName();
    $variables['active_admin_theme'] = $activeThemeName;
    $variables['active_navigation'] = $this->moduleHandler->moduleExists('navigation');
    // Expose Route name.
    $variables['route_name'] = $this->currentRouteMatch->getRouteName();

    if (preg_match('#entity\.(?<entity_type_id>.+)\.canonical#', $variables['route_name'], $matches)) {
      $entity = $this->requestStack->getCurrentRequest()->attributes->get($matches['entity_type_id']);

      if ($entity instanceof EntityInterface && $entity->hasLinkTemplate('edit-form') && $entity->access('update')) {
        $variables['entity_title'] = $entity->label();
        $variables['entity_edit_url'] = $entity->toUrl('edit-form');
      }
    }

    // Get form actions.
    if ($form_actions = Helper::formActions()) {
      if ($this->moduleHandler->moduleExists('navigation')) {
        $variables['gin_form_actions'] = '';
      }
      else {
        $variables['gin_form_actions'] = $form_actions;
      }
      $variables['gin_form_actions_class'] = 'gin-sticky-form-actions--preprocessed';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for page__node__revisions.
   */
  #[Hook('preprocess_page__node__revisions')]
  public function pageNodeRevisions(array &$page): void {
    // Attach the init script.
    $page['#attached']['library'][] = 'admin/revisions';
  }

  /**
   * Implements hook_preprocess_HOOK() for page_title.
   */
  #[Hook('preprocess_page_title')]
  public function pageTitle(array &$variables): void {
    if (preg_match('/entity\.node\..*/', $this->currentRouteMatch->getRouteName(), $matches)) {
      $node = $this->currentRouteMatch->getParameter('node');
      if ($node instanceof Node) {
        if ($node->isDefaultTranslation() && !in_array($matches[0], [
          'entity.node.content_translation_add',
          'entity.node.delete_form',
        ])) {
          $variables['title'] = $node->getTitle();
        }
        elseif ($matches[0] === 'entity.node.edit_form') {
          $variables['title_attributes']['class'][] = 'page-title--is-translation';
          $args = [
            '@title' => $node->getTitle(),
            '@language' => $node->language()->getName(),
          ];
          $variables['title'] = $this->t('@title <span class="page-title__language">(@language translation)</span>', $args);
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for select.
   */
  #[Hook('preprocess_select')]
  public function select(array &$variables): void {
    if (!empty($variables['element']['#title_display']) && $variables['element']['#title_display'] === 'attribute' && !empty((string) $variables['element']['#title'])) {
      $variables['attributes']['title'] = (string) $variables['element']['#title'];
    }

    $variables['attributes']['class'][] = 'form-element';
    $variables['attributes']['class'][] = $variables['element']['#multiple'] ?
      'form-element--type-select-multiple' :
      'form-element--type-select';

    if (in_array('block-region-select', $variables['attributes']['class'], TRUE)) {
      $variables['attributes']['class'][] = 'form-element--extrasmall';
    }

    if (in_array('block-weight', $variables['attributes']['class'], TRUE)) {
      $variables['attributes']['class'][] = 'form-element--extrasmall';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for status_messages.
   */
  #[Hook('preprocess_status_messages')]
  public function statusMessages(array &$variables): void {
    $variables['title_ids'] = [];
    foreach ($variables['message_list'] as $message_type => $messages) {
      $variables['title_ids'][$message_type] = Html::getUniqueId("message-$message_type-title");
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for system_themes_page.
   */
  #[Hook('preprocess_system_themes_page')]
  public function systemThemesPage(array &$variables): void {
    if (!empty($variables['theme_groups'])) {
      foreach ($variables['theme_groups'] as &$theme_group) {
        if (!empty($theme_group['themes'])) {
          foreach ($theme_group['themes'] as &$theme_card) {
            // @todo Remove dependency on attributes after
            //   https://www.drupal.org/project/drupal/issues/2511548 has been
            //   resolved.
            if (isset($theme_card['screenshot']['#attributes']) && $theme_card['screenshot']['#attributes'] instanceof Attribute && $theme_card['screenshot']['#attributes']->hasClass('no-screenshot')) {
              unset($theme_card['screenshot']);
            }

            $theme_card['title_id'] = Html::getUniqueId($theme_card['name'] . '-label');
            $description_is_empty = empty((string) $theme_card['description']);

            // Set description_id only if the description is not empty.
            if (!$description_is_empty) {
              $theme_card['description_id'] = Html::getUniqueId($theme_card['name'] . '-description');
            }

            if (!empty($theme_card['operations']) && !empty($theme_card['operations']['#theme']) && $theme_card['operations']['#theme'] === 'links') {
              $theme_card['operations']['#theme'] = 'links__action_links';
            }
          }
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for table.
   */
  #[Hook('preprocess_field_ui_table')]
  #[Hook('preprocess_table')]
  public function table(array &$variables): void {
    // Adding table sort indicator CSS class for inactive sort link.
    // @todo Revisit after https://www.drupal.org/node/3025726 or
    //   https://www.drupal.org/node/1973418 is in.
    if (!empty($variables['header'])) {
      foreach ($variables['header'] as &$header_cell) {
        if ($header_cell['content'] instanceof Link) {
          $query = $header_cell['content']->getUrl()->getOption('query') ?: [];

          if (isset($query['order'], $query['sort'])) {
            $header_cell['attributes']->addClass('sortable-heading');
          }
        }
      }
    }

    // Mark the whole table and the first cells if rows are draggable.
    if (!empty($variables['rows'])) {
      $draggable_row_found = FALSE;
      foreach ($variables['rows'] as &$row) {
        /** @var \Drupal\Core\Template\Attribute $row['attributes'] */
        if (!empty($row['attributes']) && $row['attributes']->hasClass('draggable')) {
          if (!$draggable_row_found) {
            $variables['attributes']['class'][] = 'draggable-table';
            $draggable_row_found = TRUE;
          }
          $first_cell_key = array_key_first($row['cells']);
          // The 'attributes' key is always here and it is an
          // \Drupal\Core\Template\Attribute.
          // @see \Drupal\Core\Theme\ThemePreprocess::preprocessTable();
          $row['cells'][$first_cell_key]['attributes']->addClass('tabledrag-cell');

          // Check that the first cell is empty or not.
          if (empty($row['cells'][$first_cell_key]) || empty($row['cells'][$first_cell_key]['content'])) {
            $row['cells'][$first_cell_key]['attributes']->addClass('tabledrag-cell--only-drag');
          }
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for text_format_wrapper.
   */
  #[Hook('preprocess_text_format_wrapper')]
  public function textFormatWrapper(array &$variables): void {
    // @todo Remove when https://www.drupal.org/node/3016343 is fixed.
    $description_attributes = [];
    if (!empty($variables['attributes']['id'])) {
      $description_attributes['id'] = $variables['attributes']['aria-describedby'] = $variables['attributes']['id'];
      unset($variables['attributes']['id']);
    }
    $variables['description_attributes'] = new Attribute($description_attributes);

    if (!empty($variables['description']) && Settings::getInstance()->get('show_description_toggle')) {
      $variables['description_display'] = 'invisible';
      $variables['description_toggle'] = TRUE;
    }
  }

  /**
   * Implements toolbar preprocess.
   *
   * This is also called by system_preprocess_toolbar() in instances where Admin
   * is the administration theme but not the active theme.
   *
   * @see system_preprocess_toolbar()
   */
  #[Hook('preprocess_toolbar')]
  public function toolbar(array &$variables): void {
    $variables['attributes']['data-drupal-gin-processed-toolbar'] = TRUE;

    // The controller resolver does not support Closures at this time. For now,
    // we use a wrapper function to load the service with dependencies.
    // @see https://www.drupal.org/project/drupal/issues/3060638
    $variables['user_picture'] = [
      '#lazy_builder' => [static::class . '::lazyToolbarUserPicture', []],
      '#create_placeholder' => TRUE,
    ];

    // Check if Navigation module is active.
    if ($this->moduleHandler->moduleExists('navigation')) {
      // Attach the new drupal navigation styles.
      $variables['#attached']['library'][] = 'admin/navigation';
      return;
    }

    // Toolbar library.
    $variables['#attached']['library'][] = 'admin/toolbar';
  }

  /**
   * Lazy builder callback for the user picture.
   */
  #[Hook('preprocess_toolbar_user_picture')]
  public function toolbarUserPicture(): array {
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $url = $user->toUrl();

    // If the user is anonymous, we cannot link to the user profile.
    if ($user->isAnonymous()) {
      $url = Url::fromUri('route:<nolink>');
    }

    $build = [
      '#type' => 'link',
      '#url' => $url,
      '#title' => [
        '#markup' => $user->getDisplayName(),
      ],
      '#attributes' => [
        'id' => 'toolbar-item-user-secondary',
        'class' => [
          'toolbar-icon',
          'toolbar-icon-user',
          'trigger',
          'toolbar-item',
        ],
        'role' => 'button',
      ],
    ];

    /** @var \Drupal\image\ImageStyleInterface|null $style */
    $style = NULL;
    try {
      $style = $this->entityTypeManager->getStorage('image_style')->load('thumbnail');
    }
    catch (PluginNotFoundException) {
      // The image style plugin does not exists. $style stays NULL and no user
      // picture will be added.
    }
    if ($style === NULL) {
      return ['link' => $build];
    }

    /** @var \Drupal\file\FileInterface|null $file */
    $file = $user->user_picture->entity;
    if ($file === NULL) {
      return ['link' => $build];
    }

    $image_url = $style->buildUrl($file->getFileUri());

    $build['#attributes']['class'] = ['toolbar-item icon-user'];
    $build['#title'] = [
      '#type' => 'html_tag',
      '#tag' => 'img',
      '#attributes' => [
        'src' => $image_url,
        'alt' => $user->getAccountName(),
        'class' => [
          'icon-user__image',
        ],
      ],
    ];

    return ['link' => $build];
  }

  /**
   * Implements hook_preprocess_HOOK() for top_bar.
   */
  #[Hook('preprocess_top_bar')]
  public function topBar(array &$variables): void {
    if (!$this->moduleHandler->moduleExists('navigation')) {
      return;
    }

    // Get local actions.
    $plugin_block = $this->blockManager->createInstance('local_actions_block', []);
    $block_content = $plugin_block->build();
    $variables['gin_local_actions'] = $this->renderer->render($block_content);
    $variables['#attached']['library'][] = 'admin/top_bar';

    // Get form actions.
    if ($form_actions = Helper::formActions()) {
      $variables['gin_form_actions'] = $form_actions;
      $variables['gin_form_actions_class'] = 'gin-sticky-form-actions--preprocessed';
      $variables['#attached']['library'][] = 'admin/top_bar';
    }

    // Get breadcrumb.
    $plugin_block = $this->blockManager->createInstance('system_breadcrumb_block', []);
    $block_content = $plugin_block->build();
    $variables['gin_breadcrumbs'] = $this->renderer->render($block_content);
    $variables['#attached']['library'][] = 'admin/top_bar';
  }

  /**
   * Implements hook_preprocess_HOOK() for views_exposed_form.
   */
  #[Hook('preprocess_views_exposed_form')]
  public function viewsExposedForm(array &$variables): void {
    $form = &$variables['form'];

    // Add BEM classes for items in the form.
    // Sorted keys.
    $child_keys = Element::children($form, TRUE);
    $last_key = NULL;
    $child_before_actions_key = NULL;

    foreach ($child_keys as $child_key) {
      if (!empty($form[$child_key]['#type'])) {
        if ($form[$child_key]['#type'] === 'actions') {
          // We need the key of the element that precedes the actions element.
          $child_before_actions_key = $last_key;
          $form[$child_key]['#attributes']['class'][] = 'views-exposed-form__item';
          $form[$child_key]['#attributes']['class'][] = 'views-exposed-form__item--actions';
        }

        if (!in_array($form[$child_key]['#type'], ['hidden', 'actions'])) {
          $form[$child_key]['#wrapper_attributes']['class'][] = 'views-exposed-form__item';
          $last_key = $child_key;
        }
      }
    }

    if ($child_before_actions_key) {
      // Add a modifier class to the item that precedes the form actions.
      $form[$child_before_actions_key]['#wrapper_attributes']['class'][] = 'views-exposed-form__item--preceding-actions';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for views_ui_display_tab_bucket.
   */
  #[Hook('preprocess_views_ui_display_tab_bucket')]
  public function viewsUiDisplayTabBucket(array &$variables): void {
    // Instead of re-styling Views UI dropbuttons with module-specific CSS
    // styles, change dropbutton variants to the extra small version.
    // @todo Revisit after https://www.drupal.org/node/3057581 is added.
    if (!empty($variables['actions']) && $variables['actions']['#type'] === 'dropbutton') {
      $variables['actions']['#dropbutton_type'] = 'extrasmall';
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for Views UI rearrange filter form.
   */
  #[Hook('preprocess_views_ui_rearrange_filter_form')]
  public function viewsUiRearrangeFilterForm(array &$variables): void {
    foreach ($variables['table']['#rows'] as &$row) {
      // Remove the container-inline class from the operator table cell.
      if (isset($row['data'][0]['class'])) {
        $row['data'][0]['class'] = array_diff($row['data'][0]['class'], ['container-inline']);
      }
    }
  }

  /**
   * Implements hook_preprocess_views_view_fields().
   *
   * This targets each rendered media item in the grid display of the media
   * library's modal dialog.
   */
  #[Hook('preprocess_views_view_fields__media_library')]
  public function viewsViewFieldsMediaLibrary(array &$variables): void {
    // Add classes to media rendered entity field so it can be targeted for
    // styling. Adding this class in a template is very difficult to do.
    if (isset($variables['fields']['rendered_entity']->wrapper_attributes)) {
      $variables['fields']['rendered_entity']->wrapper_attributes->addClass('media-library-item__click-to-select-trigger');
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for views_view_table.
   *
   * @todo Revisit after https://www.drupal.org/node/3025726 or
   *   https://www.drupal.org/node/1973418 is in.
   */
  #[Hook('preprocess_views_view_table')]
  public function viewsViewTable(array &$variables): void {
    if (!empty($variables['header'])) {
      foreach ($variables['header'] as &$header_cell) {
        if (!empty($header_cell['url'])) {
          $parsed_url = UrlHelper::parse($header_cell['url']);
          $query = !empty($parsed_url['query']) ? $parsed_url['query'] : [];

          if (isset($query['order'], $query['sort'])) {
            $header_cell['attributes']->addClass('sortable-heading');
          }
        }
      }
    }
  }

  /**
   * Helper pre-process callback for file_managed_file and image_widget.
   *
   * @param array $variables
   *   The renderable array of image and file widgets, with 'element' and 'data'
   *   keys.
   */
  private function fileAndImageWidget(array &$variables): void {
    $element = $variables['element'];
    $main_item_keys = [
      'upload',
      'upload_button',
      'remove_button',
    ];

    // Calculate helper values for the template.
    $upload_is_accessible = !isset($element['upload']['#access']) || $element['upload']['#access'] !== FALSE;
    $is_multiple = !empty($element['#cardinality']) && $element['#cardinality'] !== 1;
    $has_value = !empty($element['#value']['fids']);

    // File widget properties.
    $display_can_be_displayed = !empty($element['#display_field']);
    // Display is rendered in a separate table cell for multiple value widgets.
    $display_is_visible = $display_can_be_displayed && !$is_multiple && isset($element['display']['#type']) && $element['display']['#type'] !== 'hidden';
    $description_can_be_displayed = !empty($element['#description_field']);
    $description_is_visible = $description_can_be_displayed && isset($element['description']);

    // Image widget properties.
    $alt_can_be_displayed = !empty($element['#alt_field']);
    $alt_is_visible = $alt_can_be_displayed && (!isset($element['alt']['#access']) || $element['alt']['#access'] !== FALSE);
    $title_can_be_displayed = !empty($element['#title_field']);
    $title_is_visible = $title_can_be_displayed && (!isset($element['title']['#access']) || $element['title']['#access'] !== FALSE);

    $variables['multiple'] = $is_multiple;
    $variables['upload'] = $upload_is_accessible;
    $variables['has_value'] = $has_value;
    $variables['has_meta'] = $alt_is_visible || $title_is_visible || $display_is_visible || $description_is_visible;
    $variables['display'] = $display_is_visible;

    // Handle the default checkbox display after the file is uploaded.
    if (array_key_exists('display', $element)) {
      $variables['data']['display']['#checked'] = $element['display']['#value'];
    }

    // Render file upload input and upload button (or file name and remove
    // button, if the field is not empty) in an emphasized div.
    foreach ($variables['data'] as $key => $item) {
      $item_is_filename = isset($item['filename']['#file']) && $item['filename']['#file'] instanceof FileInterface;

      // Move filename to main items.
      if ($item_is_filename) {
        $variables['main_items']['filename'] = $item;
        unset($variables['data'][$key]);
        continue;
      }

      // Move buttons, upload input and hidden items to main items.
      if (in_array($key, $main_item_keys, TRUE)) {
        $variables['main_items'][$key] = $item;
        unset($variables['data'][$key]);
      }
    }
  }

  /**
   * Generic preprocess enabling toggle.
   *
   * @param array $variables
   *   The variables array (modify in place).
   */
  private function preprocessDescriptionToggle(array &$variables): void {
    $isEnabled = Settings::getInstance()->get('show_description_toggle') && Helper::isContentForm();
    if ((isset($variables['element']['#description_toggle']) && $variables['element']['#description_toggle']) || $isEnabled) {
      if (!empty($variables['description'])) {
        $variables['description_display_toggle'] = $variables['description_display'] ?? 'after';
        $variables['description_display'] = 'invisible';
        $variables['description_toggle'] = TRUE;
      }
      // Add toggle for text_format, description is in wrapper.
      elseif (!empty($variables['element']['#description_toggle'])) {
        $variables['description_toggle'] = TRUE;
      }
    }
  }

}
