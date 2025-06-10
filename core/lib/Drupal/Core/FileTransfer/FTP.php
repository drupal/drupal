<?php

namespace Drupal\Core\FileTransfer;

/**
 * Defines the base class for FTP implementations.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no
 *   replacement. Use composer to manage the code for your site.
 *
 * @see https://www.drupal.org/node/3512364
 */
// phpcs:ignore Drupal.NamingConventions.ValidClassName.NoUpperAcronyms
abstract class FTP extends FileTransfer {

  /**
   * {@inheritdoc}
   */
  public function __construct($jail, $username, #[\SensitiveParameter] $password, $hostname, $port) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no replacement. Use composer to manage the code for your site. See https://www.drupal.org/node/3512364', E_USER_DEPRECATED);

    $this->username = $username;
    $this->password = $password;
    $this->hostname = $hostname;
    $this->port = $port;
    parent::__construct($jail);
  }

  /**
   * {@inheritdoc}
   */
  public static function factory($jail, $settings) {
    $username = empty($settings['username']) ? '' : $settings['username'];
    $password = empty($settings['password']) ? '' : $settings['password'];
    $hostname = empty($settings['advanced']['hostname']) ? 'localhost' : $settings['advanced']['hostname'];
    $port = empty($settings['advanced']['port']) ? 21 : $settings['advanced']['port'];

    if (function_exists('ftp_connect')) {
      $class = 'Drupal\Core\FileTransfer\FTPExtension';
    }
    else {
      throw new FileTransferException('No FTP backend available.');
    }

    return new $class($jail, $username, $password, $hostname, $port);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm() {
    $form = parent::getSettingsForm();
    $form['advanced']['port']['#default_value'] = 21;
    return $form;
  }

}
