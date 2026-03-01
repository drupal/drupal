<?php

declare(strict_types=1);

namespace Drupal\admin\Hook;

use Drupal\admin\Helper;
use Drupal\admin\Settings;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Asset\AssetQueryStringInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides theme related hook implementations.
 */
class ThemeHooks implements TrustedCallbackInterface {

  /**
   * Constructs the theme related hooks.
   */
  public function __construct(
    protected readonly ThemeExtensionList $themeExtensionList,
    protected readonly ThemeHandlerInterface $themeHandler,
    protected readonly RequestStack $requestStack,
    protected readonly AssetQueryStringInterface $assetQueryString,
    protected ClassResolverInterface $classResolver,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return [
      'container',
      'managedFile',
      'messagePlaceholder',
      'operations',
      'textFormat',
      'verticalTabs',
    ];
  }

  /**
   * Implements hook_css_alter().
   *
   * Set admin CSS on top of all other CSS files.
   */
  #[Hook('css_alter')]
  public function cssAlter(array &$css): void {
    // Use anything greater than 100 to have it load after the theme as
    // CSS_AGGREGATE_THEME is set to 100. Let's be on the safe side and assign a
    // high number to it.
    $base_css = $this->themeExtensionList->getPath('admin') . '/migration/css/base/gin.css';

    if (isset($css[$base_css])) {
      $css[$base_css]['group'] = 200;
    }

    // The gin-custom.css file should be loaded just after our gin.css file.
    $custom_css = 'public://admin-custom.css';
    if (isset($css[$custom_css])) {
      $css[$custom_css]['group'] = 201;
    }
  }

  /**
   * Implements hook_element_info_alter().
   */
  #[Hook('element_info_alter')]
  public function elementInfoAlter(array &$info): void {
    // Add a pre-render function that handles the sidebar of the node form.
    // @todo Refactor when https://www.drupal.org/node/3056089 is in.
    if (isset($info['container'])) {
      $container_pre_renders = !empty($info['container']['#pre_render']) ? $info['container']['#pre_render'] : [];
      array_unshift($container_pre_renders, [__CLASS__, 'container']);

      $info['container']['#pre_render'] = $container_pre_renders;
    }

    // @todo Refactor when https://www.drupal.org/node/3016343 is fixed.
    if (isset($info['text_format'])) {
      $info['text_format']['#pre_render'][] = [__CLASS__, 'textFormat'];
    }

    // Add a pre-render function for Operations to set #dropbutton_type.
    if (isset($info['operations'])) {
      // In admin, operations should always use the extrasmall dropbutton
      // variant.
      // To add CSS classes based on variants, the element must have the
      // #dropbutton_type property before it is processed by
      // \Drupal\Core\Render\Element\Dropbutton::preRenderDropbutton(). This
      // ensures #dropbutton_type is available to preRenderDropbutton().
      $operations_pre_renders = !empty($info['operations']['#pre_render']) ? $info['operations']['#pre_render'] : [];
      array_unshift($operations_pre_renders, [__CLASS__, 'operations']);

      $info['operations']['#pre_render'] = $operations_pre_renders;

      // @todo Remove when https://www.drupal.org/node/1945262 is fixed.
      $info['operations']['#attached']['library'][] = 'core/drupal.dialog.ajax';
    }

    if (isset($info['vertical_tabs'])) {
      $info['vertical_tabs']['#pre_render'][] = [__CLASS__, 'verticalTabs'];
    }

    // Add a pre-render to managed_file.
    if (isset($info['managed_file'])) {
      $info['managed_file']['#pre_render'][] = [__CLASS__, 'managedFile'];
    }

    // Add a pre-render to status_messages to alter the placeholder markup.
    if (isset($info['status_messages'])) {
      $info['status_messages']['#pre_render'][] = [__CLASS__, 'messagePlaceholder'];
    }

    if (array_key_exists('text_format', $info)) {
      $info['text_format']['#pre_render'][] = [
        __CLASS__,
        'textFormat',
      ];
    }
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ($extension === 'toolbar') {
      $gin_info = $this->themeHandler->listInfo()['admin']->info;
      $path_prefix = '/core/themes/admin/';
      $gin_toolbar_overrides = $gin_info['libraries-override']['toolbar/toolbar'];
      foreach ($gin_toolbar_overrides['css'] as $concern => $overrides) {
        foreach ($gin_toolbar_overrides['css'][$concern] as $key => $value) {
          $config = $libraries['toolbar']['css'][$concern][$key];
          $libraries['toolbar']['css'][$concern][$path_prefix . $value] = $config;
          unset($libraries['toolbar']['css'][$concern][$key]);
        }
      }
      $gin_toolbar_menu_overrides = $gin_info['libraries-override']['toolbar/toolbar.menu'];
      foreach ($gin_toolbar_menu_overrides['css'] as $concern => $overrides) {
        foreach ($gin_toolbar_menu_overrides['css'][$concern] as $key => $value) {
          $config = $libraries['toolbar.menu']['css'][$concern][$key];
          $libraries['toolbar.menu']['css'][$concern][$path_prefix . $value] = $config;
          unset($libraries['toolbar.menu']['css'][$concern][$key]);
        }
      }
    }
  }

  /**
   * Implements hook_page_attachments_alter().
   */
  #[Hook('page_attachments_alter')]
  public function pageAttachmentsAlter(array &$page): void {
    $theme_path = $this->requestStack->getCurrentRequest()?->getBasePath() . '/' . $this->themeExtensionList->getPath('admin');
    $query_string = $this->assetQueryString->get();

    // Attach non-JavaScript specific CSS via a noscript tag to prevent unwanted
    // layout shifts.
    $page['#attached']['html_head'][] = [
      [
        '#tag' => 'link',
        '#noscript' => TRUE,
        '#attributes' => [
          'rel' => 'stylesheet',
          'href' => $theme_path . '/css/components/dropbutton-noscript.css?' . $query_string,
        ],
      ],
      'dropbutton_noscript',
    ];

    $page['#attached']['html_head'][] = [
      [
        '#tag' => 'link',
        '#noscript' => TRUE,
        '#attributes' => [
          'rel' => 'stylesheet',
          'href' => $theme_path . '/css/components/views-ui-noscript.css?' . $query_string,
        ],
      ],
      'views_ui_noscript',
    ];

    // Attach the init script.
    $page['#attached']['library'][] = 'admin/init';

    // Attach breadcrumb styles.
    $page['#attached']['library'][] = 'admin/breadcrumb';

    // Attach accent library.
    $page['#attached']['library'][] = 'admin/accent';

    // Attach sticky library.
    $page['#attached']['library'][] = 'admin/sticky';

    // Custom CSS file.
    if (file_exists('public://admin-custom.css')) {
      $page['#attached']['library'][] = 'admin/admin_custom_css';
    }

    $settings = Settings::getInstance();
    // Expose theme settings to JS.
    $page['#attached']['drupalSettings']['gin']['dark_mode'] = $settings->get('enable_dark_mode');
    $page['#attached']['drupalSettings']['gin']['dark_mode_class'] = 'gin--dark-mode';
    $page['#attached']['drupalSettings']['gin']['accent_colors'] = Helper::accentColors();
    $page['#attached']['drupalSettings']['gin']['preset_accent_color'] = $settings->get('preset_accent_color');
    $page['#attached']['drupalSettings']['gin']['accent_color'] = $settings->get('accent_color');
    $page['#attached']['drupalSettings']['gin']['preset_focus_color'] = $settings->get('preset_focus_color');
    $page['#attached']['drupalSettings']['gin']['focus_color'] = $settings->get('focus_color');
    $page['#attached']['drupalSettings']['gin']['high_contrast_mode'] = $settings->get('high_contrast_mode');
    $page['#attached']['drupalSettings']['gin']['high_contrast_mode_class'] = 'gin--high-contrast-mode';
    $page['#attached']['drupalSettings']['gin']['show_user_theme_settings'] = $settings->get('show_user_theme_settings');

    // Expose stylesheets to JS.
    $base_theme_url = '/' . $this->themeExtensionList->getPath('admin');
    $page['#attached']['drupalSettings']['gin']['variables_css_path'] = $base_theme_url . '/migration/css/theme/variables.css';
    $page['#attached']['drupalSettings']['gin']['accent_css_path'] = $base_theme_url . '/migration/css/theme/accent.css';

    $page['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#attributes' => [
          'type' => 'application/json',
          'id' => 'gin-setting-dark_mode',
        ],
        '#value' => new FormattableMarkup('{ "ginDarkMode": "@value" }', ['@value' => $settings->get('enable_dark_mode') ?? 'unknown']),
      ],
      'gin_dark_mode',
    ];
  }

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(array &$theme_registry): void {
    if (!empty($theme_registry['admin_block_content'])) {
      $theme_registry['admin_block_content']['variables']['attributes'] = [];
    }

    // @todo Remove when https://www.drupal.org/node/3016346 is fixed.
    if (!empty($theme_registry['text_format_wrapper'])) {
      $theme_registry['text_format_wrapper']['variables']['disabled'] = FALSE;
    }

    foreach (['toolbar', 'menu__toolbar'] as $registry_item) {
      if (isset($theme_registry[$registry_item])) {
        $theme_registry[$registry_item]['path'] = 'core/themes/admin/templates/navigation';
      }
    }
  }

  /**
   * Prerender callback for managed_file.
   */
  public static function managedFile(array $element): array {
    if (!empty($element['remove_button']) && is_array($element['remove_button'])) {
      $element['remove_button']['#attributes']['class'][] = 'button--extrasmall';
      $element['remove_button']['#attributes']['class'][] = 'remove-button';
    }

    if (!empty($element['upload_button']) && is_array($element['upload_button'])) {
      $element['upload_button']['#attributes']['class'][] = 'upload-button';
    }

    // Wrap single-cardinality widgets with a details element.
    $single_file_widget = empty($element['#do_not_wrap_in_details']) && !empty($element['#cardinality']) && $element['#cardinality'] === 1;
    if ($single_file_widget && empty($element['#single_wrapped'])) {
      $element['#theme_wrappers']['details'] = [
        '#title' => $element['#title'],
        '#summary_attributes' => [],
        '#attributes' => ['open' => TRUE],
        '#value' => NULL,
        // The description of the single cardinality file widgets will be
        // displayed by the managed file widget.
        '#description' => NULL,
        '#required' => $element['#required'],
        '#errors' => NULL,
        '#disabled' => !empty($element['#disabled']),
      ];
      $element['#single_wrapped'] = TRUE;

      $upload_is_accessible = empty($element['#default_value']['fids']) && (!isset($element['upload']['#access']) || $element['upload']['#access'] !== FALSE);
      if ($upload_is_accessible) {
        // Change widget title. This is the same title that is used by the
        // multiple file widget.
        // @see https://git.drupalcode.org/project/drupal/blob/ade7b950a1/core/modules/file/src/Plugin/Field/FieldWidget/FileWidget.php#L192
        $element['#title'] = t('Add a new file');
      }
      else {
        // If the field has a value, the file upload title doesn't have to be
        // visible because the wrapper element will have the same title as the
        // managed file widget. The title is kept in the markup as visually
        // hidden for accessibility.
        $element['#title_display'] = 'invisible';
      }
    }

    return $element;
  }

  /**
   * Prerender callback for Vertical Tabs element.
   */
  public static function verticalTabs(array $element): array {
    $group_type_is_details = isset($element['group']['#type']) && $element['group']['#type'] === 'details';
    $groups_are_present = isset($element['group']['#groups']) && is_array($element['group']['#groups']);

    // If the vertical tabs have a details group, add attributes to those
    // details elements so they are styled as accordion items and have BEM
    // classes.
    if ($group_type_is_details && $groups_are_present) {
      $group_keys = Element::children($element['group']['#groups'], TRUE);
      $first_key = TRUE;
      $last_group_with_child_key = NULL;
      $last_group_with_child_key_last_child_key = NULL;

      $group_key = implode('][', $element['#parents']);
      // Only check siblings against groups because we are only looking for
      // group elements.
      if (in_array($group_key, $group_keys, TRUE)) {
        $children_keys = Element::children($element['group']['#groups'][$group_key], TRUE);

        foreach ($children_keys as $child_key) {
          $last_group_with_child_key = $group_key;
          $type = $element['group']['#groups'][$group_key][$child_key]['#type'] ?? NULL;
          if ($type === 'details') {
            // Add BEM class to specify the details element is in a vertical
            // tabs group.
            $element['group']['#groups'][$group_key][$child_key]['#attributes']['class'][] = 'vertical-tabs__item';
            $element['group']['#groups'][$group_key][$child_key]['#vertical_tab_item'] = TRUE;

            if ($first_key) {
              $element['group']['#groups'][$group_key][$child_key]['#attributes']['class'][] = 'vertical-tabs__item--first';
              $first_key = FALSE;
            }

            $last_group_with_child_key_last_child_key = $child_key;
          }
        }
      }

      if ($last_group_with_child_key && $last_group_with_child_key_last_child_key) {
        $element['group']['#groups'][$last_group_with_child_key][$last_group_with_child_key_last_child_key]['#attributes']['class'][] = 'vertical-tabs__item--last';
      }

      $element['#attributes']['class'][] = 'vertical-tabs__items';
    }

    return $element;
  }

  /**
   * Prerender callback for the Operations element.
   */
  public static function operations(array $element): array {
    if (empty($element['#dropbutton_type'])) {
      $element['#dropbutton_type'] = 'extrasmall';
    }
    return $element;
  }

  /**
   * Prerender callback for container elements.
   *
   * @param array $element
   *   The container element.
   *
   * @return array
   *   The processed container element.
   */
  public static function container(array $element): array {
    if (!empty($element['#accordion'])) {
      // The container must work as an accordion list wrapper.
      $element['#attributes']['class'][] = 'accordion';
      $children_keys = Element::children($element['#groups']['advanced'], TRUE);

      foreach ($children_keys as $key) {
        $element['#groups']['advanced'][$key]['#attributes']['class'][] = 'accordion__item';

        // Mark children with type Details as accordion item.
        if (!empty($element['#groups']['advanced'][$key]['#type']) && $element['#groups']['advanced'][$key]['#type'] === 'details') {
          $element['#groups']['advanced'][$key]['#accordion_item'] = TRUE;
        }
      }
    }

    return $element;
  }

  /**
   * Prerender callback for text_format elements.
   */
  public static function textFormat(array $element): array {
    // Add clearfix for filter wrapper.
    $element['format']['#attributes']['class'][] = 'clearfix';
    // Hide format select label visually.
    $element['format']['format']['#wrapper_attributes']['class'][] = 'form-item--editor-format';
    $element['format']['format']['#attributes']['class'][] = 'form-element--extrasmall';
    $element['format']['format']['#attributes']['class'][] = 'form-element--editor-format';

    // Fix JS inconsistencies of the 'text_textarea_with_summary' widgets.
    // @todo Remove when https://www.drupal.org/node/3016343 is fixed.
    if (
      !empty($element['summary']) &&
      $element['summary']['#type'] === 'textarea'
    ) {
      $element['#attributes']['class'][] = 'js-text-format-wrapper';
      $element['value']['#wrapper_attributes']['class'][] = 'js-form-type-textarea';
    }

    if (
      !empty($element['#description']) &&
      \Drupal::classResolver(Settings::class)->get('show_description_toggle') &&
      Helper::isContentForm()
    ) {
      if ($element['#type'] === 'text_format') {
        $element['value']['#description_toggle'] = TRUE;
      }
      else {
        $element['#description_toggle'] = TRUE;
        $element['#description_display'] = 'invisible';
      }

    }

    return $element;
  }

  /**
   * Prerender callback for status_messages placeholder.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   The updated renderable array containing the placeholder.
   */
  public static function messagePlaceholder(array $element): array {
    if (isset($element['fallback']['#markup'])) {
      $element['fallback']['#markup'] = '<div data-drupal-messages-fallback class="hidden messages-list"></div>';
    }
    return $element;
  }

}
