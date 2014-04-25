<?php

/**
 * @file
 * API functions for processing and sending e-mail.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Site\Settings;

/**
 * Composes and optionally sends an e-mail message.
 *
 * Sending an e-mail works with defining an e-mail template (subject, text
 * and possibly e-mail headers) and the replacement values to use in the
 * appropriate places in the template. Processed e-mail templates are
 * requested from hook_mail() from the module sending the e-mail. Any module
 * can modify the composed e-mail message array using hook_mail_alter().
 * Finally drupal_mail_system()->mail() sends the e-mail, which can
 * be reused if the exact same composed e-mail is to be sent to multiple
 * recipients.
 *
 * Finding out what language to send the e-mail with needs some consideration.
 * If you send e-mail to a user, her preferred language should be fine, so
 * use user_preferred_langcode(). If you send email based on form values
 * filled on the page, there are two additional choices if you are not
 * sending the e-mail to a user on the site. You can either use the language
 * used to generate the page or the site default language. See
 * language_default(). The former is good if sending e-mail to the person
 * filling the form, the later is good if you send e-mail to an address
 * previously set up (like contact addresses in a contact form).
 *
 * Taking care of always using the proper language is even more important
 * when sending e-mails in a row to multiple users. Hook_mail() abstracts
 * whether the mail text comes from an administrator setting or is
 * static in the source code. It should also deal with common mail tokens,
 * only receiving $params which are unique to the actual e-mail at hand.
 *
 * An example:
 *
 * @code
 *   function example_notify($accounts) {
 *     foreach ($accounts as $account) {
 *       $params['account'] = $account;
 *       // example_mail() will be called based on the first drupal_mail() parameter.
 *       drupal_mail('example', 'notice', $account->mail, user_preferred_langcode($account), $params);
 *     }
 *   }
 *
 *   function example_mail($key, &$message, $params) {
 *     $data['user'] = $params['account'];
 *     $options['langcode'] = $message['langcode'];
 *     user_mail_tokens($variables, $data, $options);
 *     switch($key) {
 *       case 'notice':
 *         // If the recipient can receive such notices by instant-message, do
 *         // not send by email.
 *         if (example_im_send($key, $message, $params)) {
 *           $message['send'] = FALSE;
 *           break;
 *         }
 *         $message['subject'] = t('Notification from !site', $variables, $options);
 *         $message['body'][] = t("Dear !username\n\nThere is new content available on the site.", $variables, $options);
 *         break;
 *     }
 *   }
 * @endcode
 *
 * Another example, which uses drupal_mail() to format a message for sending
 * later:
 *
 * @code
 *   $params = array('current_conditions' => $data);
 *   $to = 'user@example.com';
 *   $message = drupal_mail('example', 'notice', $to, $langcode, $params, FALSE);
 *   // Only add to the spool if sending was not canceled.
 *   if ($message['send']) {
 *     example_spool_message($message);
 *   }
 * @endcode
 *
 * @param string $module
 *   A module name to invoke hook_mail() on. The {$module}_mail() hook will be
 *   called to complete the $message structure which will already contain common
 *   defaults.
 * @param string $key
 *   A key to identify the e-mail sent. The final message ID for e-mail altering
 *   will be {$module}_{$key}.
 * @param string $to
 *   The e-mail address or addresses where the message will be sent to. The
 *   formatting of this string will be validated with the
 *   @link http://php.net/manual/filter.filters.validate.php PHP e-mail validation filter. @endlink
 *   Some examples are:
 *   - user@example.com
 *   - user@example.com, anotheruser@example.com
 *   - User <user@example.com>
 *   - User <user@example.com>, Another User <anotheruser@example.com>
 * @param string $langcode
 *   Language code to use to compose the e-mail.
 * @param array $params
 *   (optional) Parameters to build the e-mail.
 * @param string|null $reply
 *   Optional e-mail address to be used to answer.
 * @param bool $send
 *   If TRUE, drupal_mail() will call drupal_mail_system()->mail() to deliver
 *   the message, and store the result in $message['result']. Modules
 *   implementing hook_mail_alter() may cancel sending by setting
 *   $message['send'] to FALSE.
 *
 * @return
 *   The $message array structure containing all details of the
 *   message. If already sent ($send = TRUE), then the 'result' element
 *   will contain the success indicator of the e-mail, failure being already
 *   written to the watchdog. (Success means nothing more than the message being
 *   accepted at php-level, which still doesn't guarantee it to be delivered.)
 */
function drupal_mail($module, $key, $to, $langcode, $params = array(), $reply = NULL, $send = TRUE) {
  $site_config = \Drupal::config('system.site');
  $site_mail = $site_config->get('mail');
  if (empty($site_mail)) {
    $site_mail = ini_get('sendmail_from');
  }

  // Bundle up the variables into a structured array for altering.
  $message = array(
    'id'       => $module . '_' . $key,
    'module'   => $module,
    'key'      => $key,
    'to'       => $to,
    'from'     => $site_mail,
    'reply-to' => $reply,
    'langcode' => $langcode,
    'params'   => $params,
    'send'     => TRUE,
    'subject'  => '',
    'body'     => array()
  );

  // Build the default headers
  $headers = array(
    'MIME-Version'              => '1.0',
    'Content-Type'              => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
    'Content-Transfer-Encoding' => '8Bit',
    'X-Mailer'                  => 'Drupal'
  );
  // To prevent e-mail from looking like spam, the addresses in the Sender and
  // Return-Path headers should have a domain authorized to use the
  // originating SMTP server.
  $headers['Sender'] = $headers['Return-Path'] = $site_mail;
  $headers['From'] = $site_config->get('name') . ' <' . $site_mail . '>';
  if ($reply) {
    $headers['Reply-to'] = $reply;
  }
  $message['headers'] = $headers;

  // Build the e-mail (get subject and body, allow additional headers) by
  // invoking hook_mail() on this module. We cannot use
  // moduleHandler()->invoke() as we need to have $message by reference in
  // hook_mail().
  if (function_exists($function = $module . '_mail')) {
    $function($key, $message, $params);
  }

  // Invoke hook_mail_alter() to allow all modules to alter the resulting e-mail.
  \Drupal::moduleHandler()->alter('mail', $message);

  // Retrieve the responsible implementation for this message.
  $system = drupal_mail_system($module, $key);

  // Format the message body.
  $message = $system->format($message);

  // Optionally send e-mail.
  if ($send) {
    // The original caller requested sending. Sending was canceled by one or
    // more hook_mail_alter() implementations. We set 'result' to NULL, because
    // FALSE indicates an error in sending.
    if (empty($message['send'])) {
      $message['result'] = NULL;
    }
    // Sending was originally requested and was not canceled.
    else {
      $message['result'] = $system->mail($message);
      // Log errors.
      if (!$message['result']) {
        watchdog('mail', 'Error sending e-mail (from %from to %to with reply-to %reply).', array('%from' => $message['from'], '%to' => $message['to'], '%reply' => $message['reply-to'] ? $message['reply-to'] : t('not set')), WATCHDOG_ERROR);
        drupal_set_message(t('Unable to send e-mail. Contact the site administrator if the problem persists.'), 'error');
      }
    }
  }

  return $message;
}

/**
 * Returns an instance of the mail plugin to use for a given message ID.
 *
 * @param string $module
 *   The module name which was used by drupal_mail() to invoke hook_mail().
 * @param string $key
 *   A key to identify the e-mail sent. The final message ID for the e-mail
 *   alter hook in drupal_mail() would have been {$module}_{$key}.
 *
 * @return \Drupal\Core\Mail\MailInterface
 *   A mail plugin instance.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 *
 * @see \Drupal\Core\Mail\MailManager::getInstance()
 */
function drupal_mail_system($module, $key) {
  return \Drupal::service('plugin.manager.mail')->getInstance(array('module' => $module, 'key' => $key));
}

/**
 * Performs format=flowed soft wrapping for mail (RFC 3676).
 *
 * We use delsp=yes wrapping, but only break non-spaced languages when
 * absolutely necessary to avoid compatibility issues.
 *
 * We deliberately use LF rather than CRLF, see drupal_mail().
 *
 * @param $text
 *   The plain text to process.
 * @param $indent (optional)
 *   A string to indent the text with. Only '>' characters are repeated on
 *   subsequent wrapped lines. Others are replaced by spaces.
 *
 * @return
 *   The content of the email as a string with formatting applied.
 */
function drupal_wrap_mail($text, $indent = '') {
  // Convert CRLF into LF.
  $text = str_replace("\r", '', $text);
  // See if soft-wrapping is allowed.
  $clean_indent = _drupal_html_to_text_clean($indent);
  $soft = strpos($clean_indent, ' ') === FALSE;
  // Check if the string has line breaks.
  if (strpos($text, "\n") !== FALSE) {
    // Remove trailing spaces to make existing breaks hard, but leave signature
    // marker untouched (RFC 3676, Section 4.3).
    $text = preg_replace('/(?(?<!^--) +\n|  +\n)/m', "\n", $text);
    // Wrap each line at the needed width.
    $lines = explode("\n", $text);
    array_walk($lines, '_drupal_wrap_mail_line', array('soft' => $soft, 'length' => strlen($indent)));
    $text = implode("\n", $lines);
  }
  else {
    // Wrap this line.
    _drupal_wrap_mail_line($text, 0, array('soft' => $soft, 'length' => strlen($indent)));
  }
  // Empty lines with nothing but spaces.
  $text = preg_replace('/^ +\n/m', "\n", $text);
  // Space-stuff special lines.
  $text = preg_replace('/^(>| |From)/m', ' $1', $text);
  // Apply indentation. We only include non-'>' indentation on the first line.
  $text = $indent . substr(preg_replace('/^/m', $clean_indent, $text), strlen($indent));

  return $text;
}

/**
 * Transforms an HTML string into plain text, preserving its structure.
 *
 * The output will be suitable for use as 'format=flowed; delsp=yes' text
 * (RFC 3676) and can be passed directly to drupal_mail() for sending.
 *
 * We deliberately use LF rather than CRLF, see drupal_mail().
 *
 * This function provides suitable alternatives for the following tags:
 * <a> <em> <i> <strong> <b> <br> <p> <blockquote> <ul> <ol> <li> <dl> <dt>
 * <dd> <h1> <h2> <h3> <h4> <h5> <h6> <hr>
 *
 * @param $string
 *   The string to be transformed.
 * @param $allowed_tags (optional)
 *   If supplied, a list of tags that will be transformed. If omitted, all
 *   all supported tags are transformed.
 *
 * @return
 *   The transformed string.
 */
function drupal_html_to_text($string, $allowed_tags = NULL) {
  // Cache list of supported tags.
  static $supported_tags;
  if (empty($supported_tags)) {
    $supported_tags = array('a', 'em', 'i', 'strong', 'b', 'br', 'p', 'blockquote', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr');
  }

  // Make sure only supported tags are kept.
  $allowed_tags = isset($allowed_tags) ? array_intersect($supported_tags, $allowed_tags) : $supported_tags;

  // Make sure tags, entities and attributes are well-formed and properly nested.
  $string = Html::normalize(Xss::filter($string, $allowed_tags));

  // Apply inline styles.
  $string = preg_replace('!</?(em|i)((?> +)[^>]*)?>!i', '/', $string);
  $string = preg_replace('!</?(strong|b)((?> +)[^>]*)?>!i', '*', $string);

  // Replace inline <a> tags with the text of link and a footnote.
  // 'See <a href="http://drupal.org">the Drupal site</a>' becomes
  // 'See the Drupal site [1]' with the URL included as a footnote.
  _drupal_html_to_mail_urls(NULL, TRUE);
  $pattern = '@(<a[^>]+?href="([^"]*)"[^>]*?>(.+?)</a>)@i';
  $string = preg_replace_callback($pattern, '_drupal_html_to_mail_urls', $string);
  $urls = _drupal_html_to_mail_urls();
  $footnotes = '';
  if (count($urls)) {
    $footnotes .= "\n";
    for ($i = 0, $max = count($urls); $i < $max; $i++) {
      $footnotes .= '[' . ($i + 1) . '] ' . $urls[$i] . "\n";
    }
  }

  // Split tags from text.
  $split = preg_split('/<([^>]+?)>/', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
  // Note: PHP ensures the array consists of alternating delimiters and literals
  // and begins and ends with a literal (inserting $null as required).

  $tag = FALSE; // Odd/even counter (tag or no tag)
  $casing = NULL; // Case conversion function
  $output = '';
  $indent = array(); // All current indentation string chunks
  $lists = array(); // Array of counters for opened lists
  foreach ($split as $value) {
    $chunk = NULL; // Holds a string ready to be formatted and output.

    // Process HTML tags (but don't output any literally).
    if ($tag) {
      list($tagname) = explode(' ', strtolower($value), 2);
      switch ($tagname) {
        // List counters
        case 'ul':
          array_unshift($lists, '*');
          break;
        case 'ol':
          array_unshift($lists, 1);
          break;
        case '/ul':
        case '/ol':
          array_shift($lists);
          $chunk = ''; // Ensure blank new-line.
          break;

        // Quotation/list markers, non-fancy headers
        case 'blockquote':
          // Format=flowed indentation cannot be mixed with lists.
          $indent[] = count($lists) ? ' "' : '>';
          break;
        case 'li':
          $indent[] = isset($lists[0]) && is_numeric($lists[0]) ? ' ' . $lists[0]++ . ') ' : ' * ';
          break;
        case 'dd':
          $indent[] = '    ';
          break;
        case 'h3':
          $indent[] = '.... ';
          break;
        case 'h4':
          $indent[] = '.. ';
          break;
        case '/blockquote':
          if (count($lists)) {
            // Append closing quote for inline quotes (immediately).
            $output = rtrim($output, "> \n") . "\"\n";
            $chunk = ''; // Ensure blank new-line.
          }
          // Fall-through
        case '/li':
        case '/dd':
          array_pop($indent);
          break;
        case '/h3':
        case '/h4':
          array_pop($indent);
        case '/h5':
        case '/h6':
          $chunk = ''; // Ensure blank new-line.
          break;

        // Fancy headers
        case 'h1':
          $indent[] = '======== ';
          $casing = 'drupal_strtoupper';
          break;
        case 'h2':
          $indent[] = '-------- ';
          $casing = 'drupal_strtoupper';
          break;
        case '/h1':
        case '/h2':
          $casing = NULL;
          // Pad the line with dashes.
          $output = _drupal_html_to_text_pad($output, ($tagname == '/h1') ? '=' : '-', ' ');
          array_pop($indent);
          $chunk = ''; // Ensure blank new-line.
          break;

        // Horizontal rulers
        case 'hr':
          // Insert immediately.
          $output .= drupal_wrap_mail('', implode('', $indent)) . "\n";
          $output = _drupal_html_to_text_pad($output, '-');
          break;

        // Paragraphs and definition lists
        case '/p':
        case '/dl':
          $chunk = ''; // Ensure blank new-line.
          break;
      }
    }
    // Process blocks of text.
    else {
      // Convert inline HTML text to plain text; not removing line-breaks or
      // white-space, since that breaks newlines when sanitizing plain-text.
      $value = trim(decode_entities($value));
      if (drupal_strlen($value)) {
        $chunk = $value;
      }
    }

    // See if there is something waiting to be output.
    if (isset($chunk)) {
      // Apply any necessary case conversion.
      if (isset($casing)) {
        $chunk = $casing($chunk);
      }
      $line_endings = Settings::get('mail_line_endings', PHP_EOL);
      // Format it and apply the current indentation.
      $output .= drupal_wrap_mail($chunk, implode('', $indent)) . $line_endings;
      // Remove non-quotation markers from indentation.
      $indent = array_map('_drupal_html_to_text_clean', $indent);
    }

    $tag = !$tag;
  }

  return $output . $footnotes;
}

/**
 * Wraps words on a single line.
 *
 * Callback for array_walk() winthin drupal_wrap_mail().
 *
 * Note that we are skipping MIME content header lines, because attached files,
 * especially applications, could have long MIME types or long filenames which
 * result in line length longer than the 77 characters limit and wrapping that
 * line will break the e-mail format. E.g., the attached file hello_drupal.docx
 * will produce the following Content-Type:
 * Content-Type:
 * application/vnd.openxmlformats-officedocument.wordprocessingml.document;
 * name="hello_drupal.docx"
 */
function _drupal_wrap_mail_line(&$line, $key, $values) {
  $line_is_mime_header = FALSE;
  $mime_headers = array(
    'Content-Type',
    'Content-Transfer-Encoding',
    'Content-Disposition',
    'Content-Description',
  );

  // Do not break MIME headers which could be longer than 77 characters.
  foreach ($mime_headers as $header) {
    if (strpos($line, $header . ': ') === 0) {
      $line_is_mime_header = TRUE;
      break;
    }
  }
  if (!$line_is_mime_header) {
    // Use soft-breaks only for purely quoted or unindented text.
    $line = wordwrap($line, 77 - $values['length'], $values['soft'] ? " \n" : "\n");
  }
  // Break really long words at the maximum width allowed.
  $line = wordwrap($line, 996 - $values['length'], $values['soft'] ? " \n" : "\n");
}

/**
 * Keeps track of URLs and replaces them with placeholder tokens.
 *
 * Callback for preg_replace_callback() within drupal_html_to_text().
 */
function _drupal_html_to_mail_urls($match = NULL, $reset = FALSE) {
  global $base_url, $base_path;
  static $urls = array(), $regexp;

  if ($reset) {
    // Reset internal URL list.
    $urls = array();
  }
  else {
    if (empty($regexp)) {
      $regexp = '@^' . preg_quote($base_path, '@') . '@';
    }
    if ($match) {
      list(, , $url, $label) = $match;
      // Ensure all URLs are absolute.
      $urls[] = strpos($url, '://') ? $url : preg_replace($regexp, $base_url . '/', $url);
      return $label . ' [' . count($urls) . ']';
    }
  }
  return $urls;
}

/**
 * Replaces non-quotation markers from a given piece of indentation with spaces.
 *
 * Callback for array_map() within drupal_html_to_text().
 */
function _drupal_html_to_text_clean($indent) {
  return preg_replace('/[^>]/', ' ', $indent);
}

/**
 * Pads the last line with the given character.
 *
 * @see drupal_html_to_text()
 */
function _drupal_html_to_text_pad($text, $pad, $prefix = '') {
  // Remove last line break.
  $text = substr($text, 0, -1);
  // Calculate needed padding space and add it.
  if (($p = strrpos($text, "\n")) === FALSE) {
    $p = -1;
  }
  $n = max(0, 79 - (strlen($text) - $p) - strlen($prefix));
  // Add prefix and padding, and restore linebreak.
  return $text . $prefix . str_repeat($pad, $n) . "\n";
}
