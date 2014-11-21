<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeManager.
 */

namespace Drupal\Core\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\StackedRouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides the default implementation of a theme manager.
 */
class ThemeManager implements ThemeManagerInterface {

  /**
   * The theme negotiator.
   *
   * @var \Drupal\Core\Theme\ThemeNegotiatorInterface
   */
  protected $themeNegotiator;

  /**
   * The theme registry used to render an output.
   *
   * @var \Drupal\Core\Theme\Registry
   */
  protected $themeRegistry;

  /**
   * Contains the current active theme.
   *
   * @var \Drupal\Core\Theme\ActiveTheme
   */
  protected $activeTheme;

  /**
   * @var \Drupal\Core\Theme\ThemeInitialization
   */
  protected $themeInitialization;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a new ThemeManager object.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   * @param \Drupal\Core\Theme\ThemeNegotiatorInterface $theme_negotiator
   *   The theme negotiator.
   * @param \Drupal\Core\Theme\ThemeInitialization $theme_initialization
   *   The theme initialization.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct($root, Registry $theme_registry, ThemeNegotiatorInterface $theme_negotiator, ThemeInitialization $theme_initialization, RequestStack $request_stack, ModuleHandlerInterface $module_handler) {
    $this->root = $root;
    $this->themeNegotiator = $theme_negotiator;
    $this->themeRegistry = $theme_registry;
    $this->themeInitialization = $theme_initialization;
    $this->requestStack = $request_stack;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function render($hook, array $variables) {
    return $this->theme($hook, $variables);
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveTheme(RouteMatchInterface $route_match = NULL) {
    if (!isset($this->activeTheme)) {
      $this->initTheme($route_match);
    }
    return $this->activeTheme;
  }

  /**
   * {@inheritdoc}
   */
  public function hasActiveTheme() {
    return isset($this->activeTheme);
  }

  /**
   * {@inheritdoc}
   */
  public function resetActiveTheme() {
    $this->activeTheme = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveTheme(ActiveTheme $active_theme) {
    $this->activeTheme = $active_theme;
    if ($active_theme) {
      $this->themeInitialization->loadActiveTheme($active_theme);
    }
    return $this;
  }

  /**
   * Generates themed output (internal use only).
   *
   * @see \Drupal\Core\Render\RendererInterface::render();
   */
  protected function theme($hook, $variables = array()) {
    static $default_attributes;

    $active_theme = $this->getActiveTheme();

    // If called before all modules are loaded, we do not necessarily have a full
    // theme registry to work with, and therefore cannot process the theme
    // request properly. See also \Drupal\Core\Theme\Registry::get().
    if (!$this->moduleHandler->isLoaded() && !defined('MAINTENANCE_MODE')) {
      throw new \Exception(t('_theme() may not be called until all modules are loaded.'));
    }

    $theme_registry = $this->themeRegistry->getRuntime();

    // If an array of hook candidates were passed, use the first one that has an
    // implementation.
    if (is_array($hook)) {
      foreach ($hook as $candidate) {
        if ($theme_registry->has($candidate)) {
          break;
        }
      }
      $hook = $candidate;
    }
    // Save the original theme hook, so it can be supplied to theme variable
    // preprocess callbacks.
    $original_hook = $hook;

    // If there's no implementation, check for more generic fallbacks.
    // If there's still no implementation, log an error and return an empty
    // string.
    if (!$theme_registry->has($hook)) {
      // Iteratively strip everything after the last '__' delimiter, until an
      // implementation is found.
      while ($pos = strrpos($hook, '__')) {
        $hook = substr($hook, 0, $pos);
        if ($theme_registry->has($hook)) {
          break;
        }
      }
      if (!$theme_registry->has($hook)) {
        // Only log a message when not trying theme suggestions ($hook being an
        // array).
        if (!isset($candidate)) {
          \Drupal::logger('theme')->warning('Theme hook %hook not found.', array('%hook' => $hook));
        }
        // There is no theme implementation for the hook passed. Return FALSE so
        // the function calling _theme() can differentiate between a hook that
        // exists and renders an empty string and a hook that is not
        // implemented.
        return FALSE;
      }
    }

    $info = $theme_registry->get($hook);

    // If a renderable array is passed as $variables, then set $variables to
    // the arguments expected by the theme function.
    if (isset($variables['#theme']) || isset($variables['#theme_wrappers'])) {
      $element = $variables;
      $variables = array();
      if (isset($info['variables'])) {
        foreach (array_keys($info['variables']) as $name) {
          if (isset($element["#$name"]) || array_key_exists("#$name", $element)) {
            $variables[$name] = $element["#$name"];
          }
        }
      }
      else {
        $variables[$info['render element']] = $element;
        // Give a hint to render engines to prevent infinite recursion.
        $variables[$info['render element']]['#render_children'] = TRUE;
      }
    }

    // Merge in argument defaults.
    if (!empty($info['variables'])) {
      $variables += $info['variables'];
    }
    elseif (!empty($info['render element'])) {
      $variables += array($info['render element'] => array());
    }
    // Supply original caller info.
    $variables += array(
      'theme_hook_original' => $original_hook,
    );

    // Set base hook for later use. For example if '#theme' => 'node__article'
    // is called, we run hook_theme_suggestions_node_alter() rather than
    // hook_theme_suggestions_node__article_alter(), and also pass in the base
    // hook as the last parameter to the suggestions alter hooks.
    if (isset($info['base hook'])) {
      $base_theme_hook = $info['base hook'];
    }
    else {
      $base_theme_hook = $hook;
    }

    // Invoke hook_theme_suggestions_HOOK().
    $suggestions = $this->moduleHandler->invokeAll('theme_suggestions_' . $base_theme_hook, array($variables));
    // If _theme() was invoked with a direct theme suggestion like
    // '#theme' => 'node__article', add it to the suggestions array before
    // invoking suggestion alter hooks.
    if (isset($info['base hook'])) {
      $suggestions[] = $hook;
    }

    // Invoke hook_theme_suggestions_alter() and
    // hook_theme_suggestions_HOOK_alter().
    $hooks = array(
      'theme_suggestions',
      'theme_suggestions_' . $base_theme_hook,
    );
    $this->moduleHandler->alter($hooks, $suggestions, $variables, $base_theme_hook);
    $this->alter($hooks, $suggestions, $variables, $base_theme_hook);

    // Check if each suggestion exists in the theme registry, and if so,
    // use it instead of the hook that _theme() was called with. For example, a
    // function may call _theme('node', ...), but a module can add
    // 'node__article' as a suggestion via hook_theme_suggestions_HOOK_alter(),
    // enabling a theme to have an alternate template file for article nodes.
    foreach (array_reverse($suggestions) as $suggestion) {
      if ($theme_registry->has($suggestion)) {
        $info = $theme_registry->get($suggestion);
        break;
      }
    }

    // Include a file if the theme function or variable preprocessor is held
    // elsewhere.
    if (!empty($info['includes'])) {
      foreach ($info['includes'] as $include_file) {
        include_once $this->root . '/' . $include_file;
      }
    }

    // Invoke the variable preprocessors, if any.
    if (isset($info['base hook'])) {
      $base_hook = $info['base hook'];
      $base_hook_info = $theme_registry->get($base_hook);
      // Include files required by the base hook, since its variable
      // preprocessors might reside there.
      if (!empty($base_hook_info['includes'])) {
        foreach ($base_hook_info['includes'] as $include_file) {
          include_once $this->root . '/' . $include_file;
        }
      }
      // Replace the preprocess functions with those from the base hook.
      if (isset($base_hook_info['preprocess functions'])) {
        // Set a variable for the 'theme_hook_suggestion'. This is used to
        // maintain backwards compatibility with template engines.
        $theme_hook_suggestion = $hook;
        $info['preprocess functions'] = $base_hook_info['preprocess functions'];
      }
    }
    if (isset($info['preprocess functions'])) {
      foreach ($info['preprocess functions'] as $preprocessor_function) {
        if (function_exists($preprocessor_function)) {
          $preprocessor_function($variables, $hook, $info);
        }
      }
      // Allow theme preprocess functions to set $variables['#attached'] and use
      // it like the #attached property on render arrays. In Drupal 8, this is
      // the (only) officially supported method of attaching assets from
      // preprocess functions. Assets attached here should be associated with
      // the template that we're preprocessing variables for.
      if (isset($variables['#attached'])) {
        $preprocess_attached = ['#attached' => $variables['#attached']];
        drupal_render($preprocess_attached);
      }
    }

    // Generate the output using either a function or a template.
    $output = '';
    if (isset($info['function'])) {
      if (function_exists($info['function'])) {
        $output = SafeMarkup::set($info['function']($variables));
      }
    }
    else {
      $render_function = 'twig_render_template';
      $extension = '.html.twig';

      // The theme engine may use a different extension and a different
      // renderer.
      $theme_engine = $active_theme->getEngine();
      if (isset($theme_engine)) {
        if ($info['type'] != 'module') {
          if (function_exists($theme_engine . '_render_template')) {
            $render_function = $theme_engine . '_render_template';
          }
          $extension_function = $theme_engine . '_extension';
          if (function_exists($extension_function)) {
            $extension = $extension_function();
          }
        }
      }

      // In some cases, a template implementation may not have had
      // template_preprocess() run (for example, if the default implementation
      // is a function, but a template overrides that default implementation).
      // In these cases, a template should still be able to expect to have
      // access to the variables provided by template_preprocess(), so we add
      // them here if they don't already exist. We don't want the overhead of
      // running template_preprocess() twice, so we use the 'directory' variable
      // to determine if it has already run, which while not completely
      // intuitive, is reasonably safe, and allows us to save on the overhead of
      // adding some new variable to track that.
      if (!isset($variables['directory'])) {
        $default_template_variables = array();
        template_preprocess($default_template_variables, $hook, $info);
        $variables += $default_template_variables;
      }
      if (!isset($default_attributes)) {
        $default_attributes = new Attribute();
      }
      foreach (array('attributes', 'title_attributes', 'content_attributes') as $key) {
        if (isset($variables[$key]) && !($variables[$key] instanceof Attribute)) {
          if ($variables[$key]) {
            $variables[$key] = new Attribute($variables[$key]);
          }
          else {
            // Create empty attributes.
            $variables[$key] = clone $default_attributes;
          }
        }
      }

      // Render the output using the template file.
      $template_file = $info['template'] . $extension;
      if (isset($info['path'])) {
        $template_file = $info['path'] . '/' . $template_file;
      }
      // Add the theme suggestions to the variables array just before rendering
      // the template for backwards compatibility with template engines.
      $variables['theme_hook_suggestions'] = $suggestions;
      // For backwards compatibility, pass 'theme_hook_suggestion' on to the
      // template engine. This is only set when calling a direct suggestion like
      // '#theme' => 'menu__shortcut_default' when the template exists in the
      // current theme.
      if (isset($theme_hook_suggestion)) {
        $variables['theme_hook_suggestion'] = $theme_hook_suggestion;
      }
      $output = $render_function($template_file, $variables);
    }

    return (string) $output;
  }

  /**
   * Initializes the active theme for a given route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  protected function initTheme(RouteMatchInterface $route_match = NULL) {
    // Determine the active theme for the theme negotiator service. This includes
    // the default theme as well as really specific ones like the ajax base theme.
    if (!$route_match) {
      $route_match = \Drupal::routeMatch();
    }
    if ($route_match instanceof StackedRouteMatchInterface) {
      $route_match = $route_match->getMasterRouteMatch();
    }
    $theme = $this->themeNegotiator->determineActiveTheme($route_match);
    $this->activeTheme = $this->themeInitialization->initTheme($theme);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Should we cache some of these information?
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    // Most of the time, $type is passed as a string, so for performance,
    // normalize it to that. When passed as an array, usually the first item in
    // the array is a generic type, and additional items in the array are more
    // specific variants of it, as in the case of array('form', 'form_FORM_ID').
    if (is_array($type)) {
      $extra_types = $type;
      $type = array_shift($extra_types);
      // Allow if statements in this function to use the faster isset() rather
      // than !empty() both when $type is passed as a string, or as an array with
      // one item.
      if (empty($extra_types)) {
        unset($extra_types);
      }
    }

    $theme_keys = array();
    $theme = $this->getActiveTheme();
    foreach ($theme->getBaseThemes() as $base) {
      $theme_keys[] = $base->getName();
    }

    $theme_keys[] = $theme->getName();
    $functions = array();
    foreach ($theme_keys as $theme_key) {
      $function = $theme_key . '_' . $type . '_alter';
      if (function_exists($function)) {
        $functions[] = $function;
      }
      if (isset($extra_types)) {
        foreach ($extra_types as $extra_type) {
          $function = $theme_key . '_' . $extra_type . '_alter';
          if (function_exists($function)) {
            $functions[] = $function;
          }
        }
      }
    }

    foreach ($functions as $function) {
      $function($data, $context1, $context2);
    }
  }

}
