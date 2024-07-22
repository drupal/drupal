<?php

namespace Drupal\Core\Mail\Plugin\Mail;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Mail\Attribute\Mail;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Error;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mime\Email;

/**
 * Defines an experimental mail backend, based on the Symfony mailer component.
 *
 * This mail plugin acts as a drop-in replacement for the current default PHP
 * mail plugin. Mail delivery is based on the Symfony mailer component. Hence,
 * all transports registered by default in the Symfony mailer transport factory
 * are available via configurable DSN.
 *
 * By default, this plugin uses `sendmail://default` as the transport DSN. I.e.,
 * it attempts to use `/usr/sbin/sendmail -bs` in order to submit a message to
 * the MTA. Sites hosted on operating systems without a working MTA (e.g.,
 * Windows) need to configure a suitable DSN.
 *
 * The DSN can be set via the `mailer_dsn` key of the `system.mailer` config.
 *
 * The following example shows how to switch the default mail plugin to the
 * experimental Symfony mailer plugin with a custom DSN using config overrides
 * in `settings.php`:
 *
 * @code
 *   $config['system.mail']['interface'] = [ 'default' => 'symfony_mailer' ];
 *   $config['system.mail']['mailer_dsn'] = [
 *     'scheme' => 'smtp',
 *     'host' => 'smtp.example.com',
 *     'port' => 25,
 *     'user' => 'user',
 *     'password' => 'pass',
 *     'options' => [],
 *   ];
 * @endcode
 *
 * @see https://symfony.com/doc/current/mailer.html#using-built-in-transports
 *
 * @internal
 */
#[Mail(
  id: 'symfony_mailer',
  label: new TranslatableMarkup('Symfony mailer (Experimental)'),
)]
class SymfonyMailer implements MailInterface, ContainerFactoryPluginInterface {

  /**
   * A list of headers that can contain multiple email addresses.
   *
   * @see \Symfony\Component\Mime\Header\Headers::HEADER_CLASS_MAP
   */
  protected const MAILBOX_LIST_HEADERS = ['from', 'to', 'reply-to', 'cc', 'bcc'];

  /**
   * List of headers to skip copying from the message array.
   *
   * Symfony mailer sets Content-Type and Content-Transfer-Encoding according to
   * the actual body content. Note that format=flowed is not supported by
   * Symfony.
   *
   * @see \Symfony\Component\Mime\Part\TextPart
   */
  protected const SKIP_HEADERS = ['content-type', 'content-transfer-encoding'];

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('logger.channel.mail')
    );
  }

  /**
   * Symfony mailer constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Symfony\Component\Mailer\MailerInterface $mailer
   *   The mailer service. Only specify an instance in unit tests, pass NULL in
   *   production.
   */
  public function __construct(
    protected LoggerInterface $logger,
    protected ?MailerInterface $mailer = NULL,
  ) {
  }

  public function format(array $message) {
    foreach ($message['body'] as &$part) {
      // If the message contains HTML, convert it to plain text (which also
      // wraps the mail body).
      if ($part instanceof MarkupInterface) {
        $part = MailFormatHelper::htmlToText($part);
      }
      // If the message does not contain HTML, it still needs to be wrapped
      // properly.
      else {
        $part = MailFormatHelper::wrapMail($part);
      }
    }

    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);

    return $message;
  }

  public function mail(array $message) {
    try {
      $email = new Email();

      $headers = $email->getHeaders();
      foreach ($message['headers'] as $name => $value) {
        if (!in_array(strtolower($name), self::SKIP_HEADERS, TRUE)) {
          if (in_array(strtolower($name), self::MAILBOX_LIST_HEADERS, TRUE)) {
            // Split values by comma, but ignore commas encapsulated in double
            // quotes.
            $value = str_getcsv($value, ',');
          }
          $headers->addHeader($name, $value);
        }
      }

      $email
        ->to($message['to'])
        ->subject($message['subject'])
        ->text($message['body']);

      $mailer = $this->getMailer();
      $mailer->send($email);
      return TRUE;
    }
    catch (\Exception $e) {
      Error::logException($this->logger, $e);
      return FALSE;
    }
  }

  /**
   * Returns a minimalistic Symfony mailer service.
   */
  protected function getMailer(): MailerInterface {
    if (!isset($this->mailer)) {
      $dsn = \Drupal::config('system.mail')->get('mailer_dsn');
      $dsnObject = new Dsn(...$dsn);

      // Symfony Mailer and Transport classes both optionally depend on the
      // event dispatcher. When provided, a MessageEvent is fired whenever an
      // email is prepared before sending.
      //
      // The MessageEvent will likely play an important role in an upcoming mail
      // API. However, emails handled by this plugin already were processed by
      // hook_mail and hook_mail_alter. Firing the MessageEvent would leak those
      // mails into the code path (i.e., event subscribers) of the new API.
      // Therefore, this plugin deliberately refrains from injecting the event
      // dispatcher.
      $factories = Transport::getDefaultFactories(logger: $this->logger);
      $transportFactory = new Transport($factories);
      $transport = $transportFactory->fromDsnObject($dsnObject);
      $this->mailer = new Mailer($transport);
    }

    return $this->mailer;
  }

}
