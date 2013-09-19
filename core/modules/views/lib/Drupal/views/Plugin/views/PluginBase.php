<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\PluginBase.
 */

namespace Drupal\views\Plugin\views;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase as ComponentPluginBase;
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
 *   Example: theme = "mymodule_row" of module "mymodule" will implement either
 *   theme_mymodule_row() or mymodule-row.tpl.php in the
 *   [..]/modules/mymodule/templates folder.
 * - register_theme: (optional) When set to TRUE (default) the theme is
 *   registered automatically. When set to FALSE the plugin reuses an existing
 *   theme implementation, defined by another module or views plugin.
 * - theme_file: (optional) the location of an include file that may hold the
 *   theme or preprocess function. The location has to be relative to module's
 *   root directory.
 * - module: machine name of the module. It must be present for any plugin that
 *   wants to register a theme.
 */
abstract class PluginBase extends ComponentPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Options for this plugin will be held here.
   *
   * @var array
   */
  public $options = array();

  /**
   * The top object of a view.
   *
   * @var Drupal\views\ViewExecutable
   */
  public $view = NULL;

  /**
   * The display object this plugin is for.
   *
   * For display plugins this is empty.
   *
   * @todo find a better description
   *
   * @var Drupal\views\Plugin\views\display\DisplayPluginBase
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
   * Constructs a Plugin object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->definition = $plugin_definition + $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Initialize the plugin.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $display
   *   The display handler.
   * @param array $options
   *   The options configured for this plugin.
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
   *  - 'translatable' => (optional) TRUE/FALSE (wrap in t() on export if true),
   *  - 'contains' => (optional) array of items this contains, with its own
   *      defaults, etc. If contains is set, the default will be ignored and
   *      assumed to be array().
   *  - 'bool' => (optional) TRUE/FALSE Is the value a boolean value. This will
   *      change the export format to TRUE/FALSE instead of 1/0.
   *  ),
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
   *   - default: The default value of one option
   *   - (optional) contains: An array which describes the available options
   *     under the key. If contains is set, the default will be ignored and
   *     assumed to be an empty array.
   *   - (optional) 'translatable': TRUE if it should be translated, else FALSE.
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
   * Unpack options over our existing defaults, drilling down into arrays
   * so that defaults don't get totally blown away.
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
   * Clears a plugin.
   */
  public function destroy() {
    unset($this->view, $this->display, $this->query);
  }

  /**
   * Init will be called after construct, when the plugin is attached to a
   * view and a display.
   */

  /**
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    // Some form elements belong in a fieldset for presentation, but can't
    // be moved into one because of the form_state['values'] hierarchy. Those
    // elements can add a #fieldset => 'fieldset_name' property, and they'll
    // be moved to their fieldset during preRender.
    $form['#pre_render'][] = 'views_ui_pre_render_add_fieldset_markup';
  }

  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, &$form_state) { }

  /**
   * Handle any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, &$form_state) { }

  /**
   * Add anything to the query that we might need to.
   */
  public function query() { }

  /**
   * Provide a full list of possible theme templates used by this style.
   */
  public function themeFunctions() {
    return $this->view->buildThemeFunctions($this->definition['theme']);
  }

  /**
   * Validate that the plugin is correct and can be saved.
   *
   * @return
   *   An array of error strings to tell the user what is wrong with this
   *   plugin.
   */
  public function validate() { return array(); }

  /**
   * Returns the summary of the settings in the display.
   */
  public function summaryTitle() {
    return t('Settings');
  }

  /**
   * Return the human readable name of the display.
   *
   * This appears on the ui beside each plugin and beside the settings link.
   */
  public function pluginTitle() {
    // Short_title is optional so its defaults to an empty string.
    if (!empty($this->definition['short_title'])) {
      return check_plain($this->definition['short_title']);
    }
    return check_plain($this->definition['title']);
  }

  /**
   * Returns the usesOptions property.
   */
  public function usesOptions() {
    return $this->usesOptions;
  }

  /**
   * Returns a string with any core tokens replaced.
   *
   * @param string $string
   *   The string to preform the token replacement on.
   * @param array $options
   *   An array of options, as passed to \Drupal\Core\Utility\Token::replace().
   *
   * @return string
   *   The tokenized string.
   */
  public function globalTokenReplace($string = '', array $options = array()) {
    return \Drupal::token()->replace($string, array('view' => $this->view), $options);
  }

  /**
   * Returns an array of available token replacements.
   *
   * @param bool $prepared
   *   Whether to return the raw token info for each token or an array of
   *   prepared tokens for each type. E.g. "[view:name]".
   * @param array $types
   *   An array of additional token types to return, defaults to 'site' and
   *   'view'.
   *
   * @return array
   *   An array of available token replacement info or tokens, grouped by type.
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
   * Adds elements for available core tokens to a form.
   *
   * @param array $form
   *   The form array to alter, passed by reference.
   * @param array $form_state
   *   The form state array to alter, passed by reference.
   */
  public function globalTokenForm(&$form, &$form_state) {
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
      '#title' => t('Available global token replacements'),
      '#collapsed' => TRUE,
    );
    $form['global_tokens']['list'] = array(
      '#theme' => 'item_list',
      '#items' => $token_items,
      '#attributes' => array(
        'class' => array('global-tokens'),
      ),
    );
  }

}
