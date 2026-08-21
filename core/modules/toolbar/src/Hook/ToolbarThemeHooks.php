<?php

namespace Drupal\toolbar\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Hook implementations for toolbar.
 */
class ToolbarThemeHooks {

  public function __construct(
    protected RendererInterface $renderer,
    protected readonly ThemeHandlerInterface $themeHandler,
    protected readonly ThemeManagerInterface $themeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    $items['toolbar'] = [
      'render element' => 'element',
      'initial preprocess' => static::class . ':preprocessToolbar',
    ];
    $items['menu__toolbar'] = [
      'base hook' => 'menu',
      'variables' => [
        'menu_name' => NULL,
        'items' => [],
        'attributes' => [],
      ],
    ];
    return $items;
  }

  /**
   * Prepares variables for administration toolbar templates.
   *
   * Default template: toolbar.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing the properties and children of
   *     the tray. Properties used: #children, #attributes and #bar.
   */
  public function preprocessToolbar(array &$variables): void {
    $element = $variables['element'];

    // Prepare the toolbar attributes.
    $variables['attributes'] = $element['#attributes'];
    $variables['toolbar_attributes'] = new Attribute($element['#bar']['#attributes']);
    $variables['toolbar_heading'] = $element['#bar']['#heading'];

    // Prepare the trays and tabs for each toolbar item as well as the remainder
    // variable that will hold any non-tray, non-tab elements.
    $variables['trays'] = [];
    $variables['tabs'] = [];
    $variables['remainder'] = [];
    foreach (Element::children($element) as $key) {
      // Early rendering to collect the wrapper attributes from
      // ToolbarItem elements.
      if (!empty($element[$key])) {
        $this->renderer->render($element[$key]);
      }
      // Add the tray.
      if (isset($element[$key]['tray'])) {
        $attributes = [];
        if (!empty($element[$key]['tray']['#wrapper_attributes'])) {
          $attributes = $element[$key]['tray']['#wrapper_attributes'];
        }
        $variables['trays'][$key] = [
          'links' => $element[$key]['tray'],
          'attributes' => new Attribute($attributes),
        ];
        if (array_key_exists('#heading', $element[$key]['tray'])) {
          $variables['trays'][$key]['label'] = $element[$key]['tray']['#heading'];
        }
      }

      // Add the tab.
      if (isset($element[$key]['tab'])) {
        $attributes = [];
        // Pass the wrapper attributes along.
        if (!empty($element[$key]['#wrapper_attributes'])) {
          $attributes = $element[$key]['#wrapper_attributes'];
        }

        $variables['tabs'][$key] = [
          'link' => $element[$key]['tab'],
          'attributes' => new Attribute($attributes),
        ];
      }

      // Add other non-tray, non-tab child elements to the remainder variable
      // for later rendering.
      foreach (Element::children($element[$key]) as $child_key) {
        if (!in_array($child_key, ['tray', 'tab'])) {
          $variables['remainder'][$key][$child_key] = $element[$key][$child_key];
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for HTML document templates.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(&$variables): void {
    if (!$this->currentUser->hasPermission('access toolbar')) {
      return;
    }
    $variables['attributes']['class'][] = 'toolbar-loading';
  }

  /**
   * Implements hook_preprocess_toolbar().
   */
  #[Hook('preprocess_toolbar')]
  public function preprocessToolbarForClaro(array &$variables, $hook, $info): void {
    // When Claro is the admin theme, Claro overrides the active theme's if that
    // active theme is not Claro. Because of these potential overrides, the
    // toolbar cache should be invalidated any time the default or admin theme
    // changes.
    $variables['#cache']['tags'][] = 'config:system.theme';
    // If Claro is the admin theme but not the active theme, still include
    // Claro's toolbar preprocessing.
    if ($this->isClaroAdminAndNotActive()) {
      $variables['attributes']['data-drupal-claro-processed-toolbar'] = TRUE;
    }
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension): void {
    if ($extension !== 'toolbar') {
      return;
    }
    $admin_theme = $this->configFactory->get('system.theme')->get('admin');
    $active_theme = $this->themeManager->getActiveTheme()->getName();
    if ($active_theme === $admin_theme) {
      return;
    }
    $overrides = [
      'claro' => [
        'toolbar' => [
          'component' => ['css/toolbar.module.css' => 'components/toolbar.module.css'],
          'theme' => [
            'css/toolbar.theme.css' => 'theme/toolbar.theme.css',
            'css/toolbar.icons.theme.css' => 'theme/toolbar.icons.theme.css',
          ],
        ],
        'toolbar.menu' => [
          'state' => ['css/toolbar.menu.css' => 'state/toolbar.menu.css'],
        ],
      ],
      'default_admin' => [
        'toolbar' => [
          'theme' => [
            'css/toolbar.theme.css' => 'theme/toolbar.theme.css',
            'css/toolbar.icons.theme.css' => 'theme/toolbar.icons.theme.css',
          ],
        ],
        'toolbar.menu' => [
          'state' => ['css/toolbar.menu.css' => 'state/toolbar.menu.css'],
        ],
      ],
    ];
    if (!isset($overrides[$admin_theme])) {
      return;
    }
    $path_prefix = '/core/modules/toolbar/css/' . $admin_theme . '/';
    foreach ($overrides[$admin_theme] as $library => $concerns) {
      foreach ($concerns as $concern => $replacements) {
        foreach ($replacements as $original => $replacement) {
          $config = $libraries[$library]['css'][$concern][$original];
          $libraries[$library]['css'][$concern][$path_prefix . $replacement] = $config;
          unset($libraries[$library]['css'][$concern][$original]);
        }
      }
    }
  }

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(array &$theme_registry): void {
    // If Claro is the admin theme but not the active theme, use Claro's toolbar
    // templates.
    if ($this->isClaroAdminAndNotActive()) {
      // If the active theme is not Claro, but Claro is the admin theme, this
      // alters the registry so Claro's toolbar templates are used.
      foreach (['toolbar', 'menu__toolbar'] as $registry_item) {
        if (isset($theme_registry[$registry_item])) {
          $theme_registry[$registry_item]['path'] = 'core/themes/claro/templates/navigation';
        }
      }
    }
  }

  /**
   * Determines if Claro is the admin theme but not the active theme.
   *
   * @return bool
   *   TRUE if Claro is the admin theme but not the active theme.
   */
  protected function isClaroAdminAndNotActive() {
    $admin_theme = $this->configFactory->get('system.theme')->get('admin');
    $active_theme = $this->themeManager->getActiveTheme()->getName();
    return $active_theme !== 'claro' && $admin_theme === 'claro';
  }

}
