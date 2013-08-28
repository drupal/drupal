<?php

/**
 * @file
 * Contains \Drupal\Core\Mail\MailFactory.
 */

namespace Drupal\Core\Mail;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Component\Utility\String;

/**
 * Factory for creating mail system objects.
 */
class MailFactory {

  /**
   * Config object for mail system configurations.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $mailConfig;

  /**
   * List of already instantiated mail system objects.
   *
   * @var array
   */
  protected $instances = array();

  /**
   * Constructs a MailFActory object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The configuration factory.
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->mailConfig = $configFactory->get('system.mail');
  }


  /**
   * Returns an object that implements \Drupal\Core\Mail\MailInterface.
   *
   * Allows for one or more custom mail backends to format and send mail messages
   * composed using drupal_mail().
   *
   * The selection of a particular implementation is controlled via the config
   * 'system.mail.interface', which is a keyed array.  The default
   * implementation is the class whose name is the value of 'default' key. A
   * more specific match first to key and then to module will be used in
   * preference to the default. To specify a different class for all mail sent
   * by one module, set the class name as the value for the key corresponding to
   * the module name. To specify a class for a particular message sent by one
   * module, set the class name as the value for the array key that is the
   * message id, which is "${module}_${key}".
   *
   * For example to debug all mail sent by the user module by logging it to a
   * file, you might set the variable as something like:
   *
   * @code
   * array(
   *   'default' => 'Drupal\Core\Mail\PhpMail',
   *   'user' => 'DevelMailLog',
   * );
   * @endcode
   *
   * Finally, a different system can be specified for a specific e-mail ID (see
   * the $key param), such as one of the keys used by the contact module:
   *
   * @code
   * array(
   *   'default' => 'Drupal\Core\Mail\PhpMail',
   *   'user' => 'DevelMailLog',
   *   'contact_page_autoreply' => 'DrupalDevNullMailSend',
   * );
   * @endcode
   *
   * Other possible uses for system include a mail-sending class that actually
   * sends (or duplicates) each message to SMS, Twitter, instant message, etc,
   * or a class that queues up a large number of messages for more efficient
   * bulk sending or for sending via a remote gateway so as to reduce the load
   * on the local server.
   *
   * @param string $module
   *   The module name which was used by drupal_mail() to invoke hook_mail().
   * @param string $key
   *   A key to identify the e-mail sent. The final e-mail ID for the e-mail
   *   alter hook in drupal_mail() would have been {$module}_{$key}.
   *
   * @return \Drupal\Core\Mail\MailInterface
   *   An object that implements Drupal\Core\Mail\MailInterface.
   *
   * @throws \Exception
   */
  public function get($module, $key) {
    $id = $module . '_' . $key;

    $configuration = $this->mailConfig->get('interface');

    // Look for overrides for the default class, starting from the most specific
    // id, and falling back to the module name.
    if (isset($configuration[$id])) {
      $class = $configuration[$id];
    }
    elseif (isset($configuration[$module])) {
      $class = $configuration[$module];
    }
    else {
      $class = $configuration['default'];
    }

    if (empty($this->instances[$class])) {
      $interfaces = class_implements($class);
      if (isset($interfaces['Drupal\Core\Mail\MailInterface'])) {
        $this->instances[$class] = new $class();
      }
      else {
        throw new \Exception(String::format('Class %class does not implement interface %interface', array('%class' => $class, '%interface' => 'Drupal\Core\Mail\MailInterface')));
      }
    }
    return $this->instances[$class];
  }

}
