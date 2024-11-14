<?php

declare(strict_types=1);

namespace Drupal\mail_html_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for mail_html_test.
 */
class MailHtmlTestHooks {

  /**
   * Implements hook_mail().
   */
  #[Hook('mail')]
  public function mail($key, &$message, $params) {
    switch ($key) {
      case 'render_from_message_param':
        $message['body'][] = \Drupal::service('renderer')->renderInIsolation($params['message']);
        break;
    }
  }

}
