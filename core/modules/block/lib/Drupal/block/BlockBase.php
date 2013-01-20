<?php

/**
 * @file
 * Contains \Drupal\block\BlockBase.
 */

namespace Drupal\block;

use Drupal\Component\Plugin\PluginBase;
use Drupal\block\Plugin\Core\Entity\Block;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Defines a base block implementation that most blocks plugins will extend.
 *
 * This abstract class provides the generic block configuration form, default
 * block settings, and handling for general user-defined block visibility
 * settings.
 */
abstract class BlockBase extends PluginBase implements BlockInterface {

  /**
   * The entity using this plugin.
   *
   * @var \Drupal\block\Plugin\Core\Entity\Block
   */
  protected $entity;

  /**
   * Overrides \Drupal\Component\Plugin\PluginBase::__construct().
   */
  public function __construct(array $configuration, $plugin_id, DiscoveryInterface $discovery, Block $entity) {
    parent::__construct($configuration, $plugin_id, $discovery);

    $this->entity = $entity;
  }

  /**
   * Returns plugin-specific settings for the block.
   *
   * Block plugins only need to override this method if they override the
   * defaults provided in BlockBase::settings().
   *
   * @return array
   *   An array of block-specific settings to override the defaults provided in
   *   BlockBase::settings().
   *
   * @see \Drupal\block\BlockBase::settings().
   */
  public function settings() {
    return array();
  }

  /**
   * Returns the configuration data for the block plugin.
   *
   * @return array
   *   The plugin configuration array from PluginBase::$configuration.
   *
   * @todo This doesn't belong here. Move this into a new base class in
   *   http://drupal.org/node/1764380.
   * @todo This does not return a config object, so the name is confusing.
   *
   * @see \Drupal\Component\Plugin\PluginBase::$configuration
   */
  public function getConfig() {
    if (empty($this->configuration)) {
      // If the plugin configuration is not already set, initialize it with the
      // default settings for the block plugin.
      $this->configuration = $this->settings();

      // @todo This loads the default subject. Is this the right place to do so?
      $definition = $this->getDefinition();
      if (isset($definition['subject'])) {
        $this->configuration += array('subject' => $definition['subject']);
      }
    }
    // Ensure that the default cache mode is set.
    $this->configuration += array('cache' => DRUPAL_NO_CACHE);
    return $this->configuration;
  }

  /**
   * Sets a particular value in the block settings.
   *
   * @param string $key
   *   The key of PluginBase::$configuration to set.
   * @param mixed $value
   *   The value to set for the provided key.
   *
   * @todo This doesn't belong here. Move this into a new base class in
   *   http://drupal.org/node/1764380.
   * @todo This does not set a value in config(), so the name is confusing.
   *
   * @see \Drupal\Component\Plugin\PluginBase::$configuration
   */
  public function setConfig($key, $value) {
    $this->configuration[$key] = $value;
  }

  /**
   * Indicates whether block-specific criteria allow access to the block.
   *
   * Blocks with access restrictions that should always be applied,
   * regardless of user-configured settings, should implement this method
   * with that access control logic.
   *
   * @return bool
   *   FALSE to deny access to the block, or TRUE to allow
   *   BlockBase::access() to make the access determination.
   *
   * @see \Drupal\block\BlockBase::access()
   */
  public function blockAccess() {
    // By default, the block is visible unless user-configured rules indicate
    // that it should be hidden.
    return TRUE;
  }

  /**
   * Implements \Drupal\block\BlockInterface::access().
   *
   * Adds the user-configured per-role, per-path, and per-language visibility
   * settings to all blocks, and invokes hook_block_access().
   *
   * Most plugins should not override this method unless they need to remove
   * the user-defined access restrictions. To add specific access
   * restrictions for a particular block type, override
   * BlockBase::blockAccess() instead.
   *
   * @see hook_block_access()
   * @see \Drupal\block\BlockBase::blockAccess()
   */
  public function access() {
    // If the block-specific access restrictions indicate the block is not
    // accessible, always deny access.
    if (!$this->blockAccess()) {
      return FALSE;
    }

    // Otherwise, check for other access restrictions.
    global $user;

    // Deny access to disabled blocks.
    if (!$this->entity->get('status')) {
      return FALSE;
    }

    // User role access handling.
    // If a block has no roles associated, it is displayed for every role.
    // For blocks with roles associated, if none of the user's roles matches
    // the settings from this block, access is denied.
    $visibility = $this->entity->get('visibility');
    if (!empty($visibility['role']['roles']) && !array_intersect(array_filter($visibility['role']['roles']), array_keys($user->roles))) {
      // No match.
      return FALSE;
    }

    // Page path handling.
    // Limited visibility blocks must list at least one page.
    if (!empty($visibility['path']['visibility']) && $visibility['path']['visibility'] == BLOCK_VISIBILITY_LISTED && empty($visibility['path']['pages'])) {
      return FALSE;
    }

    // Match path if necessary.
    if (!empty($visibility['path']['pages'])) {
      // Assume there are no matches until one is found.
      $page_match = FALSE;

      // Convert path to lowercase. This allows comparison of the same path
      // with different case. Ex: /Page, /page, /PAGE.
      $pages = drupal_strtolower($visibility['path']['pages']);
      if ($visibility['path']['visibility'] < BLOCK_VISIBILITY_PHP) {
        // Compare the lowercase path alias (if any) and internal path.
        $path = current_path();
        $path_alias = drupal_strtolower(drupal_container()->get('path.alias_manager')->getPathAlias($path));
        $page_match = drupal_match_path($path_alias, $pages) || (($path != $path_alias) && drupal_match_path($path, $pages));
        // When $block->visibility has a value of 0
        // (BLOCK_VISIBILITY_NOTLISTED), the block is displayed on all pages
        // except those listed in $block->pages. When set to 1
        // (BLOCK_VISIBILITY_LISTED), it is displayed only on those pages
        // listed in $block->pages.
        $page_match = !($visibility['path']['visibility'] xor $page_match);
      }
      elseif (module_exists('php')) {
        $page_match = php_eval($visibility['path']['pages']);
      }

      // If there are page visibility restrictions and this page does not
      // match, deny access.
      if (!$page_match) {
        return FALSE;
      }
    }

    // Language visibility settings.
    if (!empty($visibility['language']['langcodes']) && array_filter($visibility['language']['langcodes'])) {
      if (empty($visibility['language']['langcodes'][language($visibility['language']['language_type'])->langcode])) {
        return FALSE;
      }
    }

    // Check other modules for block access rules.
    foreach (module_implements('block_access') as $module) {
      if (module_invoke($module, 'block_access', $this->entity) === FALSE) {
        return FALSE;
      }
    }

    // If nothing denied access to the block, it is accessible.
    return TRUE;
  }

  /**
   * Implements \Drupal\block\BlockInterface::form().
   *
   * Creates a generic configuration form for all block types. Individual
   * block plugins can add elements to this form by overriding
   * BlockBase::blockForm(). Most block plugins should not override this
   * method unless they need to alter the generic form elements.
   *
   * @see \Drupal\block\BlockBase::blockForm()
   */
  public function form($form, &$form_state) {
    $entity = $form_state['entity'];
    $definition = $this->getDefinition();
    $form['id'] = array(
      '#type' => 'value',
      '#value' => $entity->id(),
    );
    $form['module'] = array(
      '#type' => 'value',
      '#value' => $definition['module'],
    );

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#maxlength' => 255,
      '#default_value' => !$entity->isNew() ? $entity->label() : $definition['subject'],
    );
    $form['machine_name'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine name'),
      '#maxlength' => 64,
      '#description' => t('A unique name to save this block configuration. Must be alpha-numeric and be underscore separated.'),
      '#default_value' => $entity->id(),
      '#machine_name' => array(
        'exists' => 'block_load',
        'replace_pattern' => '[^a-z0-9_.]+',
      ),
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
    );

    // Region settings.
    $form['region'] = array(
      '#type' => 'select',
      '#title' => t('Region'),
      '#description' => t('Select the region where this block should be displayed.'),
      '#default_value' => $entity->get('region'),
      '#empty_value' => BLOCK_REGION_NONE,
      '#options' => system_region_list($entity->get('theme'), REGIONS_VISIBLE),
    );

    // Visibility settings.
    $form['visibility'] = array(
      '#type' => 'vertical_tabs',
      '#title' => t('Visibility settings'),
      '#attached' => array(
        'js' => array(drupal_get_path('module', 'block') . '/block.js'),
      ),
      '#tree' => TRUE,
      '#weight' => 10,
    );

    // Per-path visibility.
    $form['visibility']['path'] = array(
      '#type' => 'details',
      '#title' => t('Pages'),
      '#collapsed' => TRUE,
      '#group' => 'visibility',
      '#weight' => 0,
    );

    // @todo remove this access check and inject it in some other way. In fact
    //   this entire visibility settings section probably needs a separate user
    //   interface in the near future.
    $visibility = $entity->get('visibility');
    $access = user_access('use PHP for settings');
    if (!empty($visibility['path']['visibility']) && $visibility['path']['visibility'] == BLOCK_VISIBILITY_PHP && !$access) {
      $form['visibility']['path']['visibility'] = array(
        '#type' => 'value',
        '#value' => BLOCK_VISIBILITY_PHP,
      );
      $form['visibility']['path']['pages'] = array(
        '#type' => 'value',
        '#value' => !empty($visibility['path']['pages']) ? $visibility['path']['pages'] : '',
      );
    }
    else {
      $options = array(
        BLOCK_VISIBILITY_NOTLISTED => t('All pages except those listed'),
        BLOCK_VISIBILITY_LISTED => t('Only the listed pages'),
      );
      $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %user for the current user's page and %user-wildcard for every user page. %front is the front page.", array('%user' => 'user', '%user-wildcard' => 'user/*', '%front' => '<front>'));

      if (module_exists('php') && $access) {
        $options += array(BLOCK_VISIBILITY_PHP => t('Pages on which this PHP code returns <code>TRUE</code> (experts only)'));
        $title = t('Pages or PHP code');
        $description .= ' ' . t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', array('%php' => '<?php ?>'));
      }
      else {
        $title = t('Pages');
      }
      $form['visibility']['path']['visibility'] = array(
        '#type' => 'radios',
        '#title' => t('Show block on specific pages'),
        '#options' => $options,
        '#default_value' => !empty($visibility['path']['visibility']) ? $visibility['path']['visibility'] : BLOCK_VISIBILITY_NOTLISTED,
      );
      $form['visibility']['path']['pages'] = array(
        '#type' => 'textarea',
        '#title' => '<span class="element-invisible">' . $title . '</span>',
        '#default_value' => !empty($visibility['path']['pages']) ? $visibility['path']['pages'] : '',
        '#description' => $description,
      );
    }

    // Configure the block visibility per language.
    if (module_exists('language') && language_multilingual()) {
      $configurable_language_types = language_types_get_configurable();

      // Fetch languages.
      $languages = language_list(LANGUAGE_ALL);
      foreach ($languages as $language) {
        // @todo $language->name is not wrapped with t(), it should be replaced
        //   by CMI translation implementation.
        $langcodes_options[$language->langcode] = $language->name;
      }
      $form['visibility']['language'] = array(
        '#type' => 'details',
        '#title' => t('Languages'),
        '#collapsed' => TRUE,
        '#group' => 'visibility',
        '#weight' => 5,
      );
      // If there are multiple configurable language types, let the user pick
      // which one should be applied to this visibility setting. This way users
      // can limit blocks by interface language or content language for exmaple.
      $language_types = language_types_info();
      $language_type_options = array();
      foreach ($configurable_language_types as $type_key) {
        $language_type_options[$type_key] = $language_types[$type_key]['name'];
      }
      $form['visibility']['language']['language_type'] = array(
        '#type' => 'radios',
        '#title' => t('Language type'),
        '#options' => $language_type_options,
        '#default_value' => !empty($visibility['language']['language_type']) ? $visibility['language']['language_type'] : $configurable_language_types[0],
        '#access' => count($language_type_options) > 1,
      );
      $form['visibility']['language']['langcodes'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Show this block only for specific languages'),
        '#default_value' => !empty($visibility['language']['langcodes']) ? $visibility['language']['langcodes'] : array(),
        '#options' => $langcodes_options,
        '#description' => t('Show this block only for the selected language(s). If you select no languages, the block will be visibile in all languages.'),
      );
    }

    // Per-role visibility.
    $role_options = array_map('check_plain', user_role_names());
    $form['visibility']['role'] = array(
      '#type' => 'details',
      '#title' => t('Roles'),
      '#collapsed' => TRUE,
      '#group' => 'visibility',
      '#weight' => 10,
    );
    $form['visibility']['role']['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Show block for specific roles'),
      '#default_value' => !empty($visibility['role']['roles']) ? $visibility['role']['roles'] : array(),
      '#options' => $role_options,
      '#description' => t('Show this block only for the selected role(s). If you select no roles, the block will be visible to all users.'),
    );

    // Add plugin-specific settings for this block type.
    $form['settings'] = $this->blockForm(array(), $form_state);
    return $form;
  }

  /**
   * Returns the configuration form elements specific to this block plugin.
   *
   * Blocks that need to add form elements to the normal block configuration
   * form should implement this method.
   *
   * @param array $form
   *   The form definition array for the block configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @return array $form
   *   The renderable form array representing the entire configuration form.
   *
   * @see \Drupal\block\BlockBase::form()
   */
  public function blockForm($form, &$form_state) {
    return array();
  }

  /**
   * Implements \Drupal\block\BlockInterface::validate().
   *
   * Most block plugins should not override this method. To add validation
   * for a specific block type, override BlockBase::blockValdiate().
   *
   * @todo Add inline documentation to this method.
   *
   * @see \Drupal\block\BlockBase::blockValidate()
   */
  public function validate($form, &$form_state) {
    if (!empty($form['machine_name']['#disabled'])) {
      $config_id = explode('.', $form_state['values']['machine_name']);
      $form_state['values']['machine_name'] = array_pop($config_id);
    }
    $form_state['values']['visibility']['role']['roles'] = array_filter($form_state['values']['visibility']['role']['roles']);
    if ($form_state['entity']->isNew()) {
      form_set_value($form['id'], $form_state['entity']->get('theme') . '.' . $form_state['values']['machine_name'], $form_state);
    }
    $this->blockValidate($form, $form_state);
  }

  /**
   * Adds block type-specific validation for the block form.
   *
   * Note that this method takes the form structure and form state arrays for
   * the full block configuration form as arguments, not just the elements
   * defined in BlockBase::blockForm().
   *
   * @param array $form
   *   The form definition array for the full block configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @see \Drupal\block\BlockBase::blockForm()
   * @see \Drupal\block\BlockBase::blockSubmit()
   * @see \Drupal\block\BlockBase::validate()
   */
  public function blockValidate($form, &$form_state) {}

  /**
   * Implements \Drupal\block\BlockInterface::submit().
   *
   * Most block plugins should not override this method. To add submission
   * handling for a specific block type, override BlockBase::blockSubmit().
   *
   * @todo Add inline documentation to this method.
   *
   * @see \Drupal\block\BlockBase::blockSubmit()
   */
  public function submit($form, &$form_state) {
    if (!form_get_errors()) {
      $this->blockSubmit($form, $form_state);

      drupal_set_message(t('The block configuration has been saved.'));
      cache_invalidate_tags(array('content' => TRUE));
      $form_state['redirect'] = 'admin/structure/block/list/block_plugin_ui:' . $form_state['entity']->get('theme');
    }
  }

  /**
   * Adds block type-specific submission handling for the block form.
   *
   * Note that this method takes the form structure and form state arrays for
   * the full block configuration form as arguments, not just the elements
   * defined in BlockBase::blockForm().
   *
   * @param array $form
   *   The form definition array for the full block configuration form.
   * @param array $form_state
   *   An array containing the current state of the configuration form.
   *
   * @see \Drupal\block\BlockBase::blockForm()
   * @see \Drupal\block\BlockBase::blockValidate()
   * @see \Drupal\block\BlockBase::submit()
   */
  public function blockSubmit($form, &$form_state) {}

}
