<?php

/**
 * @file
 * Contains \Drupal\Core\Mail\MailManager.
 */

namespace Drupal\Core\Mail;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides a Mail plugin manager.
 *
 * @see \Drupal\Core\Annotation\Mail
 * @see \Drupal\Core\Mail\MailInterface
 * @see plugin_api
 */
class MailManager extends DefaultPluginManager implements MailManagerInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, TranslationInterface $string_translation) {
    parent::__construct('Plugin/Mail', $namespaces, $module_handler, 'Drupal\Core\Mail\MailInterface', 'Drupal\Core\Annotation\Mail');
    $this->alterInfo('mail_backend_info');
    $this->setCacheBackend($cache_backend, 'mail_backend_plugins');
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->stringTranslation = $string_translation;
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
   *   - module: (string) The module name which was used by
   *     \Drupal\Core\Mail\MailManagerInterface->mail() to invoke hook_mail().
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

    $configuration = $this->configFactory->get('system.mail')->get('interface');

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
      $this->instances[$plugin_id] = $this->createInstance($plugin_id);
    }
    return $this->instances[$plugin_id];
  }

  /**
   * {@inheritdoc}
   */
  public function mail($module, $key, $to, $langcode, $params = array(), $reply = NULL, $send = TRUE) {
    $site_config = $this->configFactory->get('system.site');
    $site_mail = $site_config->get('mail');
    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }

    // Bundle up the variables into a structured array for altering.
    $message = array(
      'id' => $module . '_' . $key,
      'module' => $module,
      'key' => $key,
      'to' => $to,
      'from' => $site_mail,
      'reply-to' => $reply,
      'langcode' => $langcode,
      'params' => $params,
      'send' => TRUE,
      'subject' => '',
      'body' => array(),
    );

    // Build the default headers.
    $headers = array(
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
    );
    // To prevent email from looking like spam, the addresses in the Sender and
    // Return-Path headers should have a domain authorized to use the
    // originating SMTP server.
    $headers['Sender'] = $headers['Return-Path'] = $site_mail;
    $headers['From'] = $site_config->get('name') . ' <' . $site_mail . '>';
    if ($reply) {
      $headers['Reply-to'] = $reply;
    }
    $message['headers'] = $headers;

    // Build the email (get subject and body, allow additional headers) by
    // invoking hook_mail() on this module. We cannot use
    // moduleHandler()->invoke() as we need to have $message by reference in
    // hook_mail().
    if (function_exists($function = $module . '_mail')) {
      $function($key, $message, $params);
    }

    // Invoke hook_mail_alter() to allow all modules to alter the resulting
    // email.
    $this->moduleHandler->alter('mail', $message);

    // Retrieve the responsible implementation for this message.
    $system = $this->getInstance(array('module' => $module, 'key' => $key));

    // Format the message body.
    $message = $system->format($message);

    // Optionally send email.
    if ($send) {
      // The original caller requested sending. Sending was canceled by one or
      // more hook_mail_alter() implementations. We set 'result' to NULL,
      // because FALSE indicates an error in sending.
      if (empty($message['send'])) {
        $message['result'] = NULL;
      }
      // Sending was originally requested and was not canceled.
      else {
        $message['result'] = $system->mail($message);
        // Log errors.
        if (!$message['result']) {
          $this->loggerFactory->get('mail')
            ->error('Error sending email (from %from to %to with reply-to %reply).', array(
            '%from' => $message['from'],
            '%to' => $message['to'],
            '%reply' => $message['reply-to'] ? $message['reply-to'] : $this->t('not set'),
          ));
          drupal_set_message($this->t('Unable to send email. Contact the site administrator if the problem persists.'), 'error');
        }
      }
    }

    return $message;
  }

}
