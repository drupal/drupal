<?php

/**
 * @file
 * Contains \Drupal\Core\Mail\MailManager.
 */

namespace Drupal\Core\Mail;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\String;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;

/**
 * Mail plugin manager.
 */
class MailManager extends DefaultPluginManager {

  /**
   * Config object for mail system configurations.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $mailConfig;

  /**
   * List of already instantiated mail plugins.
   *
   * @var array
   */
  protected $instances = array();

  /**
   * Constructs the MailManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct('Plugin/Mail', $namespaces, $module_handler, 'Drupal\Core\Annotation\Mail');
    $this->alterInfo('mail_backend_info');
    $this->setCacheBackend($cache_backend, $language_manager, 'mail_backend_plugins');
    $this->mailConfig = $config_factory->get('system.mail');
  }

  /**
   * Overrides PluginManagerBase::getInstance().
   *
   * Returns an instance of the mail plugin to use for a given message ID.
   *
   * The selection of a particular implementation is controlled via the config
   * 'system.mail.interface', which is a keyed array.  The default
   * implementation is the mail plugin whose ID is the value of 'default' key. A
   * more specific match first to key and then to module will be used in
   * preference to the default. To specify a different plugin for all mail sent
   * by one module, set the plugin ID as the value for the key corresponding to
   * the module name. To specify a plugin for a particular message sent by one
   * module, set the plugin ID as the value for the array key that is the
   * message ID, which is "${module}_${key}".
   *
   * For example to debug all mail sent by the user module by logging it to a
   * file, you might set the variable as something like:
   *
   * @code
   * array(
   *   'default' => 'php_mail',
   *   'user' => 'devel_mail_log',
   * );
   * @endcode
   *
   * Finally, a different system can be specified for a specific message ID (see
   * the $key param), such as one of the keys used by the contact module:
   *
   * @code
   * array(
   *   'default' => 'php_mail',
   *   'user' => 'devel_mail_log',
   *   'contact_page_autoreply' => 'null_mail',
   * );
   * @endcode
   *
   * Other possible uses for system include a mail-sending plugin that actually
   * sends (or duplicates) each message to SMS, Twitter, instant message, etc,
   * or a plugin that queues up a large number of messages for more efficient
   * bulk sending or for sending via a remote gateway so as to reduce the load
   * on the local server.
   *
   * @param array $options
   *   An array with the following key/value pairs:
   *   - module: (string) The module name which was used by drupal_mail() to
   *     invoke hook_mail().
   *   - key: (string) A key to identify the email sent. The final message ID
   *     is a string represented as {$module}_{$key}.
   *
   * @return \Drupal\Core\Mail\MailInterface
   *   A mail plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getInstance(array $options) {
    $module = $options['module'];
    $key = $options['key'];
    $message_id = $module . '_' . $key;

    $configuration = $this->mailConfig->get('interface');

    // Look for overrides for the default mail plugin, starting from the most
    // specific message_id, and falling back to the module name.
    if (isset($configuration[$message_id])) {
      $plugin_id = $configuration[$message_id];
    }
    elseif (isset($configuration[$module])) {
      $plugin_id = $configuration[$module];
    }
    else {
      $plugin_id = $configuration['default'];
    }

    if (empty($this->instances[$plugin_id])) {
      $plugin = $this->createInstance($plugin_id);
      if (is_subclass_of($plugin, '\Drupal\Core\Mail\MailInterface')) {
        $this->instances[$plugin_id] = $plugin;
      }
      else {
        throw new InvalidPluginDefinitionException($plugin_id, String::format('Class %class does not implement interface %interface', array('%class' => get_class($plugin), '%interface' => 'Drupal\Core\Mail\MailInterface')));
      }
    }
    return $this->instances[$plugin_id];
  }
}
