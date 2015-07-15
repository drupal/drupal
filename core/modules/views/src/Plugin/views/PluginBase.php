<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\PluginBase.
 */

namespace Drupal\views\Plugin\views;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase as ComponentPluginBase;
use Drupal\Core\Render\Element;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for any views plugin types.
 *
 * Via the @Plugin definition the plugin may specify a theme function or
 * template to be used for the plugin. It also can auto-register the theme
 * implementation for that file or function.
 * - theme: the theme implementation to use in the plugin. This may be the name
 *   of the function (without theme_ prefix) or the template file (without
 *   template engine extension).
 *   If a template file should be used, the file has to be placed in the
 *   module's templates folder.
 *   Example: theme = "mymodule_row" of module "mymodule" will implement
 *   mymodule-row.html.twig in the [..]/modules/mymodule/templates folder.
 * - register_theme: (optional) When set to TRUE (default) the theme is
 *   registered automatically. When set to FALSE the plugin reuses an existing
 *   theme implementation, defined by another module or views plugin.
 * - theme_file: (optional) the location of an include file that may hold the
 *   theme or preprocess function. The location has to be relative to module's
 *   root directory.
 * - module: machine name of the module. It must be present for any plugin that
 *   wants to register a theme.
 *
 * @ingroup views_plugins
 */
abstract class PluginBase extends ComponentPluginBase implements ContainerFactoryPluginInterface, ViewsPluginInterface, DependentPluginInterface {

  /**
   * Include negotiated languages when listing languages.
   *
   * @see \Drupal\views\Plugin\views\PluginBase::listLanguages()
   */
  const INCLUDE_NEGOTIATED = 16;

  /**
   * Include entity row languages when listing languages.
   *
   * @see \Drupal\views\Plugin\views\PluginBase::listLanguages()
   */
  const INCLUDE_ENTITY = 32;

  /**
   * Query string to indicate the site default language.
   *
   * @see \Drupal\Core\Language\LanguageInterface::LANGCODE_DEFAULT
   */
  const VIEWS_QUERY_LANGUAGE_SITE_DEFAULT = '***LANGUAGE_site_default***';

  /**
   * Options for this plugin will be held here.
   *
   * @var array
   */
  public $options = array();

  /**
   * The top object of a view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  public $view = NULL;

  /**
   * The display object this plugin is for.
   *
   * For display plugins this is empty.
   *
   * @todo find a better description
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginBase
   */
  public $displayHandler;

  /**
   * Plugins's definition
   *
   * @var array
   */
  public $definition;

  /**
   * Denotes whether the plugin has an additional options form.
   *
   * @var bool
   */
  protected $usesOptions = FALSE;

  /**
   * Stores the render API renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->definition = $plugin_definition + $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    $this->view = $view;
    $this->setOptionDefaults($this->options, $this->defineOptions());
    $this->displayHandler = $display;

    $this->unpackOptions($this->options, $options);
  }

  /**
   * Information about options for all kinds of purposes will be held here.
   * @code
   * 'option_name' => array(
   *  - 'default' => default value,
   *  - 'contains' => (optional) array of items this contains, with its own
   *      defaults, etc. If contains is set, the default will be ignored and
   *      assumed to be array().
   *  ),
   * @endcode
   *
   * @return array
   *   Returns the options of this handler/plugin.
   */
  protected function defineOptions() { return array(); }

  /**
   * Fills up the options of the plugin with defaults.
   *
   * @param array $storage
   *   An array which stores the actual option values of the plugin.
   * @param array $options
   *   An array which describes the options of a plugin. Each element is an
   *   associative array containing:
   *   - default: The default value of one option. Should be translated to the
   *     interface text language selected for page if translatable.
   *   - (optional) contains: An array which describes the available options
   *     under the key. If contains is set, the default will be ignored and
   *     assumed to be an empty array.
   *   - (optional) 'bool': TRUE if the value is boolean, else FALSE.
   */
  protected function setOptionDefaults(array &$storage, array $options) {
    foreach ($options as $option => $definition) {
      if (isset($definition['contains'])) {
        $storage[$option] = array();
        $this->setOptionDefaults($storage[$option], $definition['contains']);
      }
      else {
        $storage[$option] = $definition['default'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function filterByDefinedOptions(array &$storage) {
    $this->doFilterByDefinedOptions($storage, $this->defineOptions());
  }

  /**
   * Do the work to filter out stored options depending on the defined options.
   *
   * @param array $storage
   *   The stored options.
   *
   * @param array $options
   *   The defined options.
   */
  protected function doFilterByDefinedOptions(array &$storage, array $options) {
    foreach ($storage as $key => $sub_storage) {
      if (!isset($options[$key])) {
        unset($storage[$key]);
      }

      if (isset($options[$key]['contains'])) {
        $this->doFilterByDefinedOptions($storage[$key], $options[$key]['contains']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unpackOptions(&$storage, $options, $definition = NULL, $all = TRUE, $check = TRUE) {
    if ($check && !is_array($options)) {
      return;
    }

    if (!isset($definition)) {
      $definition = $this->defineOptions();
    }

    foreach ($options as $key => $value) {
      if (is_array($value)) {
        // Ignore arrays with no definition.
        if (!$all && empty($definition[$key])) {
          continue;
        }

        if (!isset($storage[$key]) || !is_array($storage[$key])) {
          $storage[$key] = array();
        }

        // If we're just unpacking our known options, and we're dropping an
        // unknown array (as might happen for a dependent plugin fields) go
        // ahead and drop that in.
        if (!$all && isset($definition[$key]) && !isset($definition[$key]['contains'])) {
          $storage[$key] = $value;
          continue;
        }

        $this->unpackOptions($storage[$key], $value, isset($definition[$key]['contains']) ? $definition[$key]['contains'] : array(), $all, FALSE);
      }
      else if ($all || !empty($definition[$key])) {
        $storage[$key] = $value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function destroy() {
    unset($this->view, $this->display, $this->query);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // Some form elements belong in a fieldset for presentation, but can't
    // be moved into one because of the $form_state->getValues() hierarchy. Those
    // elements can add a #fieldset => 'fieldset_name' property, and they'll
    // be moved to their fieldset during pre_render.
    $form['#pre_render'][] = array(get_class($this), 'preRenderAddFieldsetMarkup');
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) { }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) { }

  /**
   * {@inheritdoc}
   */
  public function query() { }

  /**
   * {@inheritdoc}
   */
  public function themeFunctions() {
    return $this->view->buildThemeFunctions($this->definition['theme']);
  }

  /**
   * {@inheritdoc}
   */
  public function validate() { return array(); }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Settings');
  }

  /**
   * {@inheritdoc}
   */
  public function pluginTitle() {
    // Short_title is optional so its defaults to an empty string.
    if (!empty($this->definition['short_title'])) {
      return SafeMarkup::checkPlain($this->definition['short_title']);
    }
    return SafeMarkup::checkPlain($this->definition['title']);
  }

  /**
   * {@inheritdoc}
   */
  public function usesOptions() {
    return $this->usesOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function globalTokenReplace($string = '', array $options = array()) {
    return \Drupal::token()->replace($string, array('view' => $this->view), $options);
  }

  /**
   * Replaces Views' tokens in a given string. The resulting string will be
   * sanitized with Xss::filterAdmin.
   *
   * This used to be a simple strtr() scattered throughout the code. Some Views
   * tokens, such as arguments (e.g.: %1 or !1), still use the old format so we
   * handle those as well as the new Twig-based tokens (e.g.: {{ field_name }})
   *
   * @param $text
   *   Unsanitized string with possible tokens.
   * @param $tokens
   *   Array of token => replacement_value items.
   *
   * @return String
   */
  protected function viewsTokenReplace($text, $tokens) {
    if (!strlen($text)) {
      // No need to run filterAdmin on an empty string.
      return '';
    }
    if (empty($tokens)) {
      return Xss::filterAdmin($text);
    }

    // Separate Twig tokens from other tokens (e.g.: contextual filter tokens in
    // the form of %1).
    $twig_tokens = array();
    $other_tokens = array();
    foreach ($tokens as $token => $replacement) {
      if (strpos($token, '{{') !== FALSE) {
        // Twig wants a token replacement array stripped of curly-brackets.
        $token = trim(str_replace(array('{', '}'), '', $token));
        $twig_tokens[$token] = $replacement;
      }
      else {
        $other_tokens[$token] = $replacement;
      }
    }

    // Non-Twig tokens are a straight string replacement, Twig tokens get run
    // through an inline template for rendering and replacement.
    $text = strtr($text, $other_tokens);
    if ($twig_tokens) {
      // Use the unfiltered text for the Twig template, then filter the output.
      // Otherwise, Xss::filterAdmin could remove valid Twig syntax before the
      // template is parsed.
      $build = array(
        '#type' => 'inline_template',
        '#template' => $text,
        '#context' => $twig_tokens,
        '#post_render' => [
          function ($children, $elements) {
            return Xss::filterAdmin($children);
          }
        ],
      );

      return $this->getRenderer()->render($build);
    }
    else {
      return $text;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableGlobalTokens($prepared = FALSE, array $types = array()) {
    $info = \Drupal::token()->getInfo();
    // Site and view tokens should always be available.
    $types += array('site', 'view');
    $available = array_intersect_key($info['tokens'], array_flip($types));

    // Construct the token string for each token.
    if ($prepared) {
      $prepared = array();
      foreach ($available as $type => $tokens) {
        foreach (array_keys($tokens) as $token) {
          $prepared[$type][] = "[$type:$token]";
        }
      }

      return $prepared;
    }

    return $available;
  }

  /**
   * {@inheritdoc}
   */
  public function globalTokenForm(&$form, FormStateInterface $form_state) {
    $token_items = array();

    foreach ($this->getAvailableGlobalTokens() as $type => $tokens) {
      $item = array(
        '#markup' => $type,
        'children' => array(),
      );
      foreach ($tokens as $name => $info) {
        $item['children'][$name] = "[$type:$name]" . ' - ' . $info['name'] . ': ' . $info['description'];
      }

      $token_items[$type] = $item;
    }

    $form['global_tokens'] = array(
      '#type' => 'details',
      '#title' => $this->t('Available global token replacements'),
    );
    $form['global_tokens']['list'] = array(
      '#theme' => 'item_list',
      '#items' => $token_items,
      '#attributes' => array(
        'class' => array('global-tokens'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderAddFieldsetMarkup(array $form) {
    foreach (Element::children($form) as $key) {
      $element = $form[$key];
      // In our form builder functions, we added an arbitrary #fieldset property
      // to any element that belongs in a fieldset. If this form element has
      // that property, move it into its fieldset.
      if (isset($element['#fieldset']) && isset($form[$element['#fieldset']])) {
        $form[$element['#fieldset']][$key] = $element;
        // Remove the original element this duplicates.
        unset($form[$key]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderFlattenData($form) {
    foreach (Element::children($form) as $key) {
      $element = $form[$key];
      if (!empty($element['#flatten'])) {
        foreach (Element::children($element) as $child_key) {
          $form[$child_key] = $form[$key][$child_key];
        }
        // All done, remove the now-empty parent.
        unset($form[$key]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    $definition = $this->getPluginDefinition();
    return $definition['provider'];
  }

  /**
   * Makes an array of languages, optionally including special languages.
   *
   * @param int $flags
   *   (optional) Flags for which languages to return (additive). Options:
   *   - \Drupal\Core\Language::STATE_ALL (default): All languages
   *     (configurable and default).
   *   - \Drupal\Core\Language::STATE_CONFIGURABLE: Configurable languages.
   *   - \Drupal\Core\Language::STATE_LOCKED: Locked languages.
   *   - \Drupal\Core\Language::STATE_SITE_DEFAULT: Add site default language;
   *     note that this is not included in STATE_ALL.
   *   - \Drupal\views\Plugin\views\PluginBase::INCLUDE_NEGOTIATED: Add
   *     negotiated language types.
   *   - \Drupal\views\Plugin\views\PluginBase::INCLUDE_ENTITY: Add
   *     entity row language types. Note that these are only supported for
   *     display options, not substituted in queries.
   * @param array|null $current_values
   *   The currently-selected options in the list, if available.
   *
   * @return array
   *   An array of language names, keyed by the language code. Negotiated and
   *   special languages have special codes that are substituted in queries by
   *   PluginBase::queryLanguageSubstitutions().
   *   Only configurable languages and languages that are in $current_values are
   *   included in the list.
   */
  protected function listLanguages($flags = LanguageInterface::STATE_ALL, array $current_values = NULL) {
    $manager = \Drupal::languageManager();
    $languages = $manager->getLanguages($flags);
    $list = array();

    // The entity languages should come first, if requested.
    if ($flags & PluginBase::INCLUDE_ENTITY) {
      $list['***LANGUAGE_entity_translation***'] = $this->t('Content language of view row');
      $list['***LANGUAGE_entity_default***'] = $this->t('Original language of content in view row');
    }

    // STATE_SITE_DEFAULT comes in with ID set
    // to LanguageInterface::LANGCODE_SITE_DEFAULT.
    // Since this is not a real language, surround it by '***LANGUAGE_...***',
    // like the negotiated languages below.
    if ($flags & LanguageInterface::STATE_SITE_DEFAULT) {
      $list[PluginBase::VIEWS_QUERY_LANGUAGE_SITE_DEFAULT] = $this->t($languages[LanguageInterface::LANGCODE_SITE_DEFAULT]->getName());
      // Remove site default language from $languages so it's not added
      // twice with the real languages below.
      unset($languages[LanguageInterface::LANGCODE_SITE_DEFAULT]);
    }

    // Add in negotiated languages, if requested.
    if ($flags & PluginBase::INCLUDE_NEGOTIATED) {
      $types_info = $manager->getDefinedLanguageTypesInfo();
      $types = $manager->getLanguageTypes();
      // We only go through the configured types.
      foreach ($types as $id) {
        if (isset($types_info[$id]['name'])) {
          $name = $types_info[$id]['name'];
          // Surround IDs by '***LANGUAGE_...***', to avoid query collisions.
          $id = '***LANGUAGE_' . $id . '***';
          $list[$id] = $this->t('!type language selected for page', array('!type' => $name));
        }
      }
      if (!empty($current_values)) {
        foreach ($types_info as $id => $type) {
          $id = '***LANGUAGE_' . $id . '***';
          // If this (non-configurable) type is among the current values,
          // add that option too, so it is not lost. If not among the current
          // values, skip displaying it to avoid user confusion.
          if (isset($type['name']) && !isset($list[$id]) && in_array($id, $current_values)) {
            $list[$id] = $this->t('!type language selected for page', array('!type' => $type['name']));
          }
        }
      }
    }

    // Add real languages.
    foreach ($languages as $id => $language) {
      $list[$id] = $this->t($language->getName());
    }

    return $list;
  }

  /**
   * Returns substitutions for Views queries for languages.
   *
   * This is needed so that the language options returned by
   * PluginBase::listLanguages() are able to be used in queries. It is called
   * by the Views module implementation of hook_views_query_substitutions()
   * to get the language-related substitutions.
   *
   * @return array
   *   An array in the format of hook_views_query_substitutions() that gives
   *   the query substitutions needed for the special language types.
   */
  public static function queryLanguageSubstitutions() {
    $changes = array();
    $manager = \Drupal::languageManager();

    // Handle default language.
    $default = $manager->getDefaultLanguage()->getId();
    $changes[PluginBase::VIEWS_QUERY_LANGUAGE_SITE_DEFAULT] = $default;

    // Handle negotiated languages.
    $types = $manager->getDefinedLanguageTypesInfo();
    foreach ($types as $id => $type) {
      if (isset($type['name'])) {
        $changes['***LANGUAGE_' . $id . '***'] = $manager->getCurrentLanguage($id)->getId();
      }
    }

    return $changes;
  }

  /**
   * Returns the render API renderer.
   *
   * @return \Drupal\Core\Render\RendererInterface
   */
  protected function getRenderer() {
    if (!isset($this->renderer)) {
      $this->renderer = \Drupal::service('renderer');
    }

    return $this->renderer;
  }

}
