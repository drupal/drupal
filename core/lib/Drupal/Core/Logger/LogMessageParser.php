<?php

namespace Drupal\Core\Logger;

/**
 * Parses log messages and their placeholders.
 */
class LogMessageParser implements LogMessageParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parseMessagePlaceholders(&$message, array &$context) {
    $variables = [];
    $has_psr3 = FALSE;
    if (($start = strpos($message, '{')) !== FALSE && strpos($message, '}') > $start) {
      $has_psr3 = TRUE;
      // Transform PSR3 style messages containing placeholders to
      // \Drupal\Component\Render\FormattableMarkup style.
      $message = preg_replace('/\{([^\{}]*)\}/U', '@$1', $message);
    }
    foreach ($context as $key => $variable) {
      // PSR3 style placeholders.
      if ($has_psr3) {
        // Keys are not prefixed with anything according to PSR3 specs.
        // If the message is "User {username} created" the variable key will be
        // just "username".
        if (strpos($message, '@' . $key) !== FALSE) {
          $key = '@' . $key;
        }
      }
      if (!empty($key) && ($key[0] === '@' || $key[0] === '%' || $key[0] === ':')) {
        // The key is now in \Drupal\Component\Render\FormattableMarkup style.
        $variables[$key] = $variable;
      }
    }

    return $variables;
  }

}
