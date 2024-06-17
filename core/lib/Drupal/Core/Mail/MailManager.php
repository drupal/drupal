<?php

namespace Drupal\Core\Mail;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Html;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\Attribute\Mail;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Header\MailboxHeader;

/**
 * Provides a Mail plugin manager.
 *
 * @see \Drupal\Core\Annotation\Mail
 * @see \Drupal\Core\Mail\MailInterface
 * @see plugin_api
 */
class MailManager extends DefaultPluginManager implements MailManagerInterface {

  use MessengerTrait;
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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * List of already instantiated mail plugins.
   *
   * @var array
   */
  protected $instances = [];

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
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, TranslationInterface $string_translation, RendererInterface $renderer) {
    parent::__construct('Plugin/Mail', $namespaces, $module_handler, 'Drupal\Core\Mail\MailInterface', Mail::class, 'Drupal\Core\Annotation\Mail');
    $this->alterInfo('mail_backend_info');
    $this->setCacheBackend($cache_backend, 'mail_backend_plugins');
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->stringTranslation = $string_translation;
    $this->renderer = $renderer;
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
   * message ID, which is "{$module}_{$key}".
   *
   * For example to debug all mail sent by the user module by logging it to a
   * file, you might set the variable as something like:
   *
   * @code
   * [
   *   'default' => 'php_mail',
   *   'user' => 'devel_mail_log',
   * ];
   * @endcode
   *
   * Finally, a different system can be specified for a specific message ID (see
   * the $key param), such as one of the keys used by the contact module:
   *
   * @code
   * [
   *   'default' => 'php_mail',
   *   'user' => 'devel_mail_log',
   *   'contact_page_autoreply' => 'null_mail',
   * ];
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
  public function mail($module, $key, $to, $langcode, $params = [], $reply = NULL, $send = TRUE) {
    // Mailing can invoke rendering (e.g., generating URLs, replacing tokens),
    // but emails are not HTTP responses: they're not cached, they don't have
    // attachments. Therefore we perform mailing inside its own render context,
    // to ensure it doesn't leak into the render context for the HTTP response
    // to the current request.
    return $this->renderer->executeInRenderContext(new RenderContext(), function () use ($module, $key, $to, $langcode, $params, $reply, $send) {
      return $this->doMail($module, $key, $to, $langcode, $params, $reply, $send);
    });
  }

  /**
   * Composes and optionally sends an email message.
   *
   * @param string $module
   *   A module name to invoke hook_mail() on. The {$module}_mail() hook will be
   *   called to complete the $message structure which will already contain
   *   common defaults.
   * @param string $key
   *   A key to identify the email sent. The final message ID for email altering
   *   will be {$module}_{$key}.
   * @param string $to
   *   The email address or addresses where the message will be sent to. The
   *   formatting of this string will be validated with the
   *   @link http://php.net/manual/filter.filters.validate.php PHP email validation filter. @endlink
   *   Some examples are:
   *   - user@example.com
   *   - user@example.com, anotheruser@example.com
   *   - User <user@example.com>
   *   - User <user@example.com>, Another User <anotheruser@example.com>
   * @param string $langcode
   *   Language code to use to compose the email.
   * @param array $params
   *   (optional) Parameters to build the email. Use the key '_error_message'
   *   to provide translatable markup to display as a message if an error
   *   occurs, or set this to false to disable error display.
   * @param string|null $reply
   *   Optional email address to be used to answer.
   * @param bool $send
   *   If TRUE, call an implementation of
   *   \Drupal\Core\Mail\MailInterface->mail() to deliver the message, and
   *   store the result in $message['result']. Modules implementing
   *   hook_mail_alter() may cancel sending by setting $message['send'] to
   *   FALSE.
   *
   * @return array
   *   The $message array structure containing all details of the message. If
   *   already sent ($send = TRUE), then the 'result' element will contain the
   *   success indicator of the email, failure being already written to the
   *   watchdog. (Success means nothing more than the message being accepted at
   *   php-level, which still doesn't guarantee it to be delivered.)
   *
   * @see \Drupal\Core\Mail\MailManagerInterface::mail()
   */
  public function doMail($module, $key, $to, $langcode, $params = [], $reply = NULL, $send = TRUE) {
    $site_config = $this->configFactory->get('system.site');
    $site_mail = $site_config->get('mail');
    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }

    // Bundle up the variables into a structured array for altering.
    $message = [
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
      'body' => [],
    ];

    // Build the default headers.
    $headers = [
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
    ];
    // To prevent email from looking like spam, the addresses in the Sender and
    // Return-Path headers should have a domain authorized to use the
    // originating SMTP server.
    $headers['From'] = $headers['Sender'] = $headers['Return-Path'] = $site_mail;
    // Make sure the site-name is a RFC-2822 compliant 'display-name'.
    if ($site_mail) {
      $mailbox = new MailboxHeader('From', new Address($site_mail, $site_config->get('name') ?: ''));
      $headers['From'] = $mailbox->getBodyAsString();
    }
    if ($reply) {
      $headers['Reply-to'] = $reply;
    }
    $message['headers'] = $headers;

    // Build the email (get subject and body, allow additional headers) by
    // invoking hook_mail() on this module.
    $this->moduleHandler->invoke($module, 'mail', [$key, &$message, $params]);

    // Invoke hook_mail_alter() to allow all modules to alter the resulting
    // email.
    $this->moduleHandler->alter('mail', $message);

    // Retrieve the responsible implementation for this message.
    $system = $this->getInstance(['module' => $module, 'key' => $key]);

    // Attempt to convert relative URLs to absolute.
    foreach ($message['body'] as &$body_part) {
      if ($body_part instanceof MarkupInterface) {
        $body_part = Markup::create(Html::transformRootRelativeUrlsToAbsolute((string) $body_part, \Drupal::request()->getSchemeAndHttpHost()));
      }
    }

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
        // Ensure that subject is plain text. By default translated and
        // formatted strings are prepared for the HTML context and email
        // subjects are plain strings.
        if ($message['subject']) {
          $message['subject'] = PlainTextOutput::renderFromHtml($message['subject']);
        }
        $message['result'] = $system->mail($message);
        // Log errors.
        if (!$message['result']) {
          $this->loggerFactory->get('mail')
            ->error('Error sending email (from %from to %to with reply-to %reply).', [
              '%from' => $message['from'],
              '%to' => $message['to'],
              '%reply' => $message['reply-to'] ? $message['reply-to'] : $this->t('not set'),
            ]);
          $error_message = $params['_error_message'] ?? $this->t('Unable to send email. Contact the site administrator if the problem persists.');
          if ($error_message) {
            $this->messenger()->addError($error_message);
          }
        }
      }
    }

    return $message;
  }

}
