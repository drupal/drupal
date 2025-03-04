<?php

namespace Drupal\file\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Requirements for the File module.
 */
class FileRequirements {

  use StringTranslationTrait;

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    $server_software = \Drupal::request()->server->get('SERVER_SOFTWARE', '');

    // Get the web server identity.
    $is_nginx = preg_match("/Nginx/i", $server_software);
    $is_apache = preg_match("/Apache/i", $server_software);
    $fastcgi = $is_apache && ((str_contains($server_software, 'mod_fastcgi') || str_contains($server_software, 'mod_fcgi')));

    // Check the uploadprogress extension is loaded.
    if (extension_loaded('uploadprogress')) {
      $value = $this->t('Enabled (<a href="https://github.com/php/pecl-php-uploadprogress#uploadprogress">PECL uploadprogress</a>)');
      $description = NULL;
    }
    else {
      $value = $this->t('Not enabled');
      $description = $this->t('Your server is capable of displaying file upload progress, but does not have the required libraries. It is recommended to install the <a href="https://github.com/php/pecl-php-uploadprogress#installation">PECL uploadprogress library</a>.');
    }

    // Adjust the requirement depending on what the server supports.
    if (!$is_apache && !$is_nginx) {
      $value = $this->t('Not enabled');
      $description = $this->t('Your server is not capable of displaying file upload progress. File upload progress requires an Apache server running PHP with mod_php or Nginx with PHP-FPM.');
    }
    elseif ($fastcgi) {
      $value = $this->t('Not enabled');
      $description = $this->t('Your server is not capable of displaying file upload progress. File upload progress requires PHP be run with mod_php or PHP-FPM and not as FastCGI.');
    }

    $requirements['file_progress'] = [
      'title' => $this->t('Upload progress'),
      'value' => $value,
      'description' => $description,
    ];

    return $requirements;
  }

}
