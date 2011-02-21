<?php
// $Id: simplenews.mail.inc,v 1.8 2011/01/28 01:18:18 mirodietiker Exp $

/**
 * @file
 * Simplenews email send and spool handling
 *
 * @ingroup simplenews
 */

 /**
 * Send newsletter node to subscribers.
 *
 * @param integer or object $node Newsletter node to be sent. integer = nid; object = node object
 * @param array $accounts  account objects to send the newsletter to.
 *   account = object (
 *     snid     = subscription id. 0 if no subscription record exists
 *     tid     = newsletter category ID
 *     uid      = user id. 0 if subscriber is anonymous user.
 *     mail     = user email address.
 *     name     = <empty>. Added for compatibility with user account object
 *     language = language object. User preferred of default language
 *   )
 *   NOTE: either snid, mail or uid is required.
 */
function simplenews_send_node($node, $accounts = array()) {
  if (is_numeric($node)) {
    $node = node_load($node);
  }

  if (is_object($node)) {
    $spool_data['nid'] = $node->nid;
    $spool_data['vid'] = $node->vid;
    $spool_data['tid'] = $node->simplenews->tid;

    // Get accounts subscribed to this newsletter.
    // Using hook_simplenews_recipients modules can add recipients.
    $recipients = simplenews_get_subscriptions_by_list($node->simplenews->tid);
    foreach (module_implements('simplenews_recipients_alter') as $module) {
      $function = $module . '_simplenews_recipients_alter';
      $function($recipients, $node->simplenews->tid);
    }

    // Build data array of specified accounts.
    // First we use the recipient data collected by hook_simplenews_recipients().
    // If this fails we get the data from $accounts
    if ($accounts) {
      $temp_recipients = array();
      foreach ($accounts as $account) {
        if (isset($recipients[$account->mail])) {
          $temp_recipients[$account->mail] = $recipients[$account->mail];
        }
        else {
          // Set these accounts to 'subscribed'.
          $account->status = 1;
          $temp_recipients[$account->mail] = $account;
        }
      }
      $recipients = $temp_recipients;
    }
    
    // To send the newsletter, the node id and target email addresses
    // are stored in the spool.
    // Only subscribed recipients are stored in the spool (status = 1).
    
    db_query(
      "INSERT INTO {simplenews_mail_spool} (mail, nid, vid, tid, status, timestamp, snid)
      SELECT s.mail, :nid, :vid, t.tid, :stat, :time, s.snid 
      FROM {simplenews_subscriber} s 
      INNER JOIN {simplenews_subscription} t  
      ON s.snid = t.snid 
      WHERE s.activated = 1
      AND t.tid = :tid 
      AND t.status = 1",
      array(
        ':nid' => $spool_data['nid'],
        ':vid' => $spool_data['vid'],
        ':stat' => SIMPLENEWS_SPOOL_PENDING,
        ':time' => time(),
        ':tid' => $spool_data['tid'],
      )
    );

    // When cron is not used the newsletter is send immediately to the emails
    // in the spool. When cron is used newsletters are send to addresses in the
    // spool during the next (and following) cron run.
    if (variable_get('simplenews_use_cron', TRUE) == FALSE) {
      simplenews_mail_spool($spool_data['nid'], $spool_data['vid'], 999999);
      drupal_set_message(t('Newsletter sent.'));
      simplenews_clear_spool();
    }
    else {
      drupal_set_message(t('Newsletter pending.'));
    }

  }
}

/**
 * Send test version of newsletter.
 *
 * @param integer or object $node Newsletter node to be sent. Integer = nid; Object = node object
 */
function simplenews_send_test($node, $test_addresses) {
  if (is_numeric($node)) {
    $node = node_load($node);
  }
  if (is_object($node)) {
    // Prevent session information from being saved while sending.
    if ($original_session = drupal_save_session()) {
      drupal_save_session(FALSE);
    }

    // Force the current user to anonymous to ensure consistent permissions.
    $original_user = $GLOBALS['user'];
    $GLOBALS['user'] = drupal_anonymous_user();

    // Send the test newsletter to the test address(es) specified in the node.
    // Build array of test email addresses

    // Send newsletter to test addresses.
    // Emails are send direct, not using the spool.
    $recipients = array('anonymous' => array(), 'user' => array());
    foreach ($test_addresses as $mail) {
      $mail = trim($mail);
      if (!empty($mail)) {
        $message = new stdClass();
        $message->nid = $node->nid;
        $message->vid = $node->vid;
        $message->tid = $node->simplenews->tid;
        $message->data = simplenews_get_subscription((object)array('mail' => $mail));
        $account = _simplenews_user_load($mail);
        $subscription = simplenews_get_subscription($account);
        if ($account->uid) {
          $recipients['user'][] = $account->name . ' <'.$mail.'>';
        }
        else {
          $recipients['anonymous'][] = $mail;
        }
        //$tmpres = simplenews_mail_mail($node->nid, $node->vid, $mail, 'test');
        $tmpres = simplenews_mail_mail($message, 'test');
      }
    }
    if (count($recipients['user'])) {
      $recipients_txt = implode(', ', $recipients['user']);
      drupal_set_message(t('Test newsletter sent to user %recipient.', array('%recipient' => $recipients_txt)));
    }
    if (count($recipients['anonymous'])) {
      $recipients_txt = implode(', ', $recipients['anonymous']);
      drupal_set_message(t('Test newsletter sent to anonymous %recipient.', array('%recipient' => $recipients_txt)));
    }

    $GLOBALS['user'] = $original_user;
    if ($original_session) {
      drupal_save_session(TRUE);
    }
  }
}

/**
 * Send a node to an email address.
 *
 * @param object $msgbase
 *   Mail message object as returned by simplenews_load_spool():
 *     $msgbase->nid
 *     $msgbase->vid
 *     $msgbase->tid
 *     $msgbase->data
 * @param $key email key [node|test]
 *
 * @return TRUE if email is successfully delivered by php mail()
 */
function simplenews_mail_mail($msgbase, $key = 'node') {
  static $cache;

  $nid = $msgbase->nid;
  $vid = $msgbase->vid;
  $tid = $msgbase->tid;
  
  $subscriber = $msgbase->data;
  if (!$subscriber) {
    $subscriber = simplenews_get_subscription((object)array('mail' => $msgbase->mail));
    //$account = user_load($msgbase->uid));
  }
  $params['context']['account'] = $subscriber;

  // Get node data for the mail
  // Because node_load() only caches the most recent node revision
  // we cache here based  on nid and vid.
  // TODO Investigate if this caching thing is still applicable to D7
  if (isset($cache[$nid . ':' . $vid])) {
    $node = $cache[$nid . ':' . $vid];
  }
  else {
    $node = node_load($nid, $vid);
    $cache[$nid . ':' . $vid] = $node;
  }

  if (is_object($node)) {
    $params['context']['node'] = $node;
    $params['context']['newsletter'] = simplenews_newsletter_load($tid);
    $params['context']['category'] = simplenews_category_load($tid);
    $params['from'] = _simplenews_set_from($params['context']['category']);

    // Send mail
    if (module_exists('mimemail')) {
      // If mimemail module is installed ALL emails are send via this module.
      // drupal_mail() builds the content of the email but does NOT send.
      $message = drupal_mail('simplenews', $key, $subscriber->mail, $subscriber->language, $params, $params['from']['formatted'], FALSE);
      $to = isset($message['params']['context']['account']) ? $message['params']['context']['account'] : $message['to'];
      $plain = $message['params']['context']['category']->format == 'plain' ? TRUE : NULL;
      $plain_text = $plain ? $message['body'] : simplenews_html_to_text($message['body'], TRUE);
      $files = isset($message['params']['context']['node']->files) ? $message['params']['context']['node']->files : array();
      $message['result'] = mimemail(
        $message['from'], 
        $to, 
        $message['subject'], 
        $message['body'], 
        $plain, 
        $message['headers'], 
        $plain_text, 
        $files, 
        ''
      );
    }
    else {
      $message = drupal_mail('simplenews', $key, $subscriber->mail, $subscriber->language, $params, $params['from']['formatted'], TRUE);
    }

    // Log sent result in watchdog.
    if (variable_get('simplenews_debug', FALSE)) {
      if (module_exists('mimemail')) {
        $via_mimemail = t('Sent via Mime Mail');
      }
      //TODO Add line break before %mimemail.
      if ($message['result']) {
        watchdog('simplenews', 'Outgoing email. Message type: %type<br />Subject: %subject<br />Recipient: %to %mimemail', array('%type' => $key, '%to' => $message['to'], '%subject' => $message['subject'], '%mimemail' => $via_mimemail), WATCHDOG_DEBUG);
      }
      else {
        watchdog('simplenews', 'Outgoing email failed. Message type: %type<br />Subject: %subject<br />Recipient: %to %mimemail', array('%type' => $key, '%to' => $message['to'], '%subject' => $message['subject'], '%mimemail' => $via_mimemail), WATCHDOG_ERROR);
      }
    }

    // Build array of sent results for spool table and reporting.
    if ($message['result']) {
      $message['result'] = array(
        'status' => SIMPLENEWS_SPOOL_DONE,
        'error' => FALSE,
      );
    }
    else {
      // This error may be caused by faulty mailserver configuration or overload.
      // Mark "pending" to keep trying.
      $message['result'] = array(
        'status' => SIMPLENEWS_SPOOL_PENDING,
        'error' => TRUE,
      );
    }

  }
  else {
    // Node could not be loaded. The node is probably deleted while pending to be sent.
    // This error is not recoverable, mark "done".
    $message['result'] = array(
      'status' => SIMPLENEWS_SPOOL_DONE,
      'error' => TRUE,
    );
    watchdog('simplenews', 'Newsletter not send: newsletter issue does not exist (nid = @nid; vid = @vid).', array('@nid' => $nid, '@vid' => $vid), WATCHDOG_ERROR);
  }

  return isset($message['result']) ? $message['result'] : FALSE;
}

/**
 * Send simplenews newsletters from the spool.
 *
 * Individual newsletter emails are stored in database spool.
 * Sending is triggered by cron or immediately when the node is saved.
 * Mail data is retrieved from the spool, rendered and send one by one
 * If sending is successful the message is marked as send in the spool.
 * @todo Replace time(): http://drupal.org/node/224333#time
 */
function simplenews_mail_spool($nid = NULL, $vid = NULL, $limit = NULL) {
  $check_counter = 0;

  // Send pending messages from database cache
  // A limited number of mails is retrieved from the spool
  $limit = isset($limit) ? $limit : variable_get('simplenews_throttle', 20);
  if ($spool_list = simplenews_get_spool(SIMPLENEWS_SPOOL_PENDING, $nid, $vid, $limit)) {
    // Prevent session information from being saved while sending.
    if ($original_session = drupal_save_session()) {
      drupal_save_session(FALSE);
    }

    // Force the current user to anonymous to ensure consistent permissions.
    $original_user = $GLOBALS['user'];
    $GLOBALS['user'] = drupal_anonymous_user();

    $count_fail = $count_success = 0;

    _simplenews_measure_usec(TRUE);

    foreach ($spool_list as $msid => $spool_data) {
      $result = simplenews_mail_mail($spool_data);

      // Update spool status.
      // This is not optimal for performance but prevents duplicate emails
      // in case of PHP execution time overrun.
      simplenews_update_spool(array($msid), $result);
      if ($result['status'] == SIMPLENEWS_SPOOL_DONE) {
        $count_success++;
      }
      if ($result['error']) {
        $count_fail++;
      }

      // Check every n emails if we exceed the limit.
      // When PHP maximum execution time is almost elapsed we interrupt
      // sending. The remainder will be sent during the next cron run.
      if (++$check_counter >= SIMPLENEWS_SEND_CHECK_INTERVAL) {
        $check_counter = 0;
        // Break the sending if a percentage of max execution time was exceeded.
        $elapsed = _simplenews_measure_usec();
        if ($elapsed > SIMPLENEWS_SEND_TIME_LIMIT * $max_execution_time) {
          watchdog('simplenews', 'Sending interrupted: PHP maximum execution time almost exceeded. Remaining newsletters will be sent during the next cron run. If this warning occurs regularly you should reduce the !cron_throttle_setting.', array('!cron_throttle_setting' => l(t('Cron throttle setting'), 'admin/config/simplenews/mail')), WATCHDOG_WARNING);
          break;
        }
      }
    }

    // Report sent result and elapsed time. On Windows systems getrusage() is
    // not implemented and hence no elapsed time is available.
    if (function_exists('getrusage')) {
      watchdog('simplenews', '%success emails sent in %sec seconds, %fail failed sending.', array('%success' => $count_success, '%sec' => round(_simplenews_measure_usec(), 1), '%fail' => $count_fail));
    }
    else {
      watchdog('simplenews', '%success emails sent, %fail failed.', array('%success' => $count_success, '%fail' => $count_fail));
    }

    variable_set('simplenews_last_cron', REQUEST_TIME);
    variable_set('simplenews_last_sent', $count_success);

    // Restore the user.
    $GLOBALS['user'] = $original_user;
    if ($original_session) {
      drupal_save_session(TRUE);
    }
  }

}

/**
 * Save mail message in mail cache table.
 *
 * @param array $spool
 *   Data array to be stored in the spool table.
 *  $spool['mail']
 *  $spool['nid']
 *  $spool['vid']
 *  $spool['tid']
 *  $spool['status']  (Default: 1 = pending)
 *  $spool['time']    (default: current unix timestamp)

 * @param array $spool Mail message array
 * @todo Replace time(): http://drupal.org/node/224333#time
 */
function simplenews_save_spool($spool) {
  $status = isset($spool['status']) ? $spool['status'] : SIMPLENEWS_SPOOL_PENDING;
  $time = isset($spool['time']) ? $spool['time'] : REQUEST_TIME;

  db_insert('simplenews_mail_spool')
    ->fields(array(
      'mail' => $spool['mail'],
      'nid' => $spool['nid'],
      'vid' => $spool['vid'],
      'tid' => $spool['tid'],
      'snid' => $spool['snid'],
      'status' => $status,
      'timestamp' => $time,
      'data' => serialize($spool['data']),
    ))
    ->execute();
}

/**
 * Retrieve data from mail spool
 *
 * @param string  $status Status of data to be retrieved (0 = hold, 1 = pending, 2 = send)
 * @param integer $nid node id
 * @param integer $vid node version id
 * @param integer $limit The maximum number of mails to load from the spool
 *
 * @return array Spool data
 *  $spool['msid']
 *  $spool['mail']
 *  $spool['nid']
 *  $spool['tid']
 *  $spool['status']
 *  $spool['time']
 * @todo Convert output to array of objects.
 */
function simplenews_get_spool($status, $nid = NULL, $vid = NULL, $limit = 0) {
  $spool = array();

  $query = db_select('simplenews_mail_spool', 's')
    ->fields('s')
    ->condition('s.status', $status)
    ->orderBy('s.timestamp', 'ASC');
  if ($limit) {
    $query->range(0, $limit);
  }
  foreach ($query->execute() as $row) {
    if (strlen($row->data)) {
      $row->data = unserialize($row->data);
    }
    else {
      $row->data = simplenews_get_subscription((object)array('mail' => $row->mail));
    }
    $spool[$row->msid] = $row;
  }
  return $spool;
}

/**
 * Update status of mail data in spool table.
 *
 * Time stamp is set to current time.
 *
 * @param array $msids
 *   Array of Mail spool ids to be updated
 * @param array $data
 *   Array containing email sent results
 *    'status' => (0 = hold, 1 = pending, 2 = send)
 *    'error' => error id (optional; defaults to '')
 */
function simplenews_update_spool($msids, $data) {
  db_update('simplenews_mail_spool')
    ->condition('msid', $msids)
    ->fields(array(
      'status' => $data['status'],
      'error' => isset($result['error']) ? (int)$data['error'] : 0,
      'timestamp' => REQUEST_TIME,
    ))
    ->execute();
}

/**
 * Count data in mail spool table.
 *
 * @param integer $nid newsletter node id
 * @param integer $vid newsletter revision id
 * @param string $status email sent status
 *
 * @return array Mail message array
 */
function simplenews_count_spool($nid, $vid, $status = SIMPLENEWS_SPOOL_PENDING) {
  $query = db_query("SELECT msid FROM {simplenews_mail_spool} WHERE nid = :nid AND vid = :vid AND status = :status", array(':nid' => $nid, ':vid' => $vid, ':status' => $status));
  return $query->rowCount();
}

/**
 * Remove records from mail spool table.
 *
 * All records with status 'send' and time stamp before the expiration date
 * are removed from the spool.
 * @todo Replace time(): http://drupal.org/node/224333#time
 */
function simplenews_clear_spool() {
  $expiration_time = REQUEST_TIME - variable_get('simplenews_spool_expire', 0) * 86400;
  db_delete('simplenews_mail_spool')
    ->condition('status', SIMPLENEWS_SPOOL_DONE)
    ->condition('timestamp', $expiration_time, '<=')
    ->execute();
}

/**
 * Update newsletter sent status.
 *
 * Set newsletter sent status based on email sent status in spool table.
 * Translated and untranslated nodes get a different treatment.
 *
 * The spool table holds data for emails to be sent and (optionally)
 * already send emails. The simplenews_newsletter table contains the overall
 * sent status of each newsletter issue (node).
 * Newsletter issues get the status pending when sending is initiated. As
 * long as unsend emails exist in the spool, the status of the newsletter remains
 * unsend. When no pending emails are found the newsletter status is set 'send'.
 *
 * Translated newsletters are a group of nodes that share the same tnid ({node}.tnid).
 * Only one node of the group is found in the spool, but all nodes should share
 * the same state. Therefore they are checked for the combined number of emails
 * in the spool.
 */
function simplenews_send_status_update() {
  $counts = array(); // number pending of emails in the spool
  $sum = array(); // sum of emails in the spool per tnid (translation id)
  $send = array(); // nodes with the status 'send'

  // For each pending newsletter count the number of pending emails in the spool.
  $query = db_select('simplenews_newsletter', 's'); 
  $query->innerJoin('node', 'n', 's.nid = n.nid AND s.vid = n.vid');
  $query->fields('s', array('nid', 'vid', 'tid'))
    ->fields('n', array('tnid'))
    ->condition('s.status', SIMPLENEWS_STATUS_SEND_PENDING);
  foreach ($query->execute() as $newsletter) {
    // nid-vid are combined in one unique key.
    $counts[$newsletter->tnid][$newsletter->nid . '-' . $newsletter->vid] = simplenews_count_spool($newsletter->nid, $newsletter->vid);
  }

  // Determine which nodes are send per translation group and per individual node.
  foreach ($counts as $tnid => $node_count) {
    // The sum of emails per tnid is the combined status result for the group of translated nodes.
    // Untranslated nodes have tnid == 0 which will be ignored later.
    $sum[$tnid] = array_sum($node_count);
    foreach ($node_count as $nidvid => $count) {
      // Translated nodes (tnid != 0)
      if ($tnid != '0' && $sum[$tnid] == '0') {
        $send[] = $nidvid;
      }
      // Untranslated nodes (tnid == 0)
      elseif ($tnid == '0' && $count == '0') {
        $send[] = $nidvid;
      }
    }
  }

  // Update overall newsletter status
  if (!empty($send)) {
    foreach ($send as $nidvid) {
      // Split the combined key 'nid-vid'
      $nid = strtok($nidvid, '-');
      $vid = strtok('-');
      db_update('simplenews_newsletter')
        ->condition('nid', $nid)
        ->condition('vid', $vid)
        ->fields(array('status' => SIMPLENEWS_STATUS_SEND_READY))
        ->execute();
    }
  }
}

/**
 * Build header array with priority and receipt confirmation settings.
 *
 * @param $node
 *   Newsletter category object.
 * @param $from
 *   Newsletter from email address
 *
 * @return Header array with priority and receipt confirmation info
 */
function _simplenews_headers($category, $from) {
  $headers = array();

  // If receipt is requested, add headers.
  if ($category->receipt) {
    $headers['Disposition-Notification-To'] = $from;
    $headers['X-Confirm-Reading-To'] = $from;
  }

  // Add priority if set.
  switch ($category->priority) {
    case SIMPLENEWS_PRIORITY_HIGHEST:
      $headers['Priority'] = 'High';
      $headers['X-Priority'] = '1';
      $headers['X-MSMail-Priority'] = 'Highest';
      break;
    case SIMPLENEWS_PRIORITY_HIGH:
      $headers['Priority'] = 'urgent';
      $headers['X-Priority'] = '2';
      $headers['X-MSMail-Priority'] = 'High';
      break;
    case SIMPLENEWS_PRIORITY_NORMAL:
      $headers['Priority'] = 'normal';
      $headers['X-Priority'] = '3';
      $headers['X-MSMail-Priority'] = 'Normal';
      break;
    case SIMPLENEWS_PRIORITY_LOW:
      $headers['Priority'] = 'non-urgent';
      $headers['X-Priority'] = '4';
      $headers['X-MSMail-Priority'] = 'Low';
      break;
    case SIMPLENEWS_PRIORITY_LOWEST:
      $headers['Priority'] = 'non-urgent';
      $headers['X-Priority'] = '5';
      $headers['X-MSMail-Priority'] = 'Lowest';
      break;
  }

  // Add general headers
  $headers['Precedence'] = 'bulk';

  return $headers;
}

/**
 * Build formatted from-name and email for a mail object.
 *
 * Each newsletter category can have a different from address.
 *
 * @param $category
 *   Newsletter category object.
 *
 * @return Associative array with (un)formatted from address
 *  'address'   => From address
 *  'formatted' => Formatted, mime encoded, from name and address
 */
function _simplenews_set_from($category = NULL) {
  $address_default = variable_get('site_mail', ini_get('sendmail_from'));
  $name_default = variable_get('site_name', 'Drupal');

  if ($category) {
    $address = $category->from_address;
    $name = $category->from_name;
  }
  else {
    $address = variable_get('simplenews_from_address', $address_default);
    $name = variable_get('simplenews_from_name', $name_default);
  }

  // Windows based PHP systems don't accept formatted emails.
  $formatted_address = substr(PHP_OS, 0, 3) == 'WIN' ? $address : '"' . mime_header_encode($name) . '" <' . $address . '>';

  return array(
    'address' => $address,
    'formatted' => $formatted_address,
  );
}

/**
 * HTML to text conversion for HTML and special characters.
 *
 * Converts some special HTML characters in addition to drupal_html_to_text()
 *
 * @param string $text Source text with HTML and special characters
 * @param boolean $inline_hyperlinks
 *   TRUE: URLs will be placed inline.
 *   FALSE: URLs will be converted to numbered reference list.
 * @return string Target text with HTML and special characters replaced
 */
function simplenews_html_to_text($text, $inline_hyperlinks = TRUE) {
  // By replacing <a> tag by only its URL the URLs will be placed inline
  // in the email body and are not converted to a numbered reference list
  // by drupal_html_to_text().
  // URL are converted to absolute URL as drupal_html_to_text() would have.
  if ($inline_hyperlinks) {
    $pattern = '@<a[^>]+?href="([^"]*)"[^>]*?>(.+?)</a>@is';
    $text = preg_replace_callback($pattern, '_simplenews_absolute_mail_urls', $text);
  }

  // Replace some special characters before performing the drupal standard conversion.
  $preg = _simplenews_html_replace();
  $text = preg_replace(array_keys($preg), array_values($preg), $text);

  // Perform standard drupal html to text conversion.
  return drupal_html_to_text($text);
}

/**
 * Helper function for simplenews_html_to_text().
 *
 * Replaces URLs with absolute URLs.
 */
function _simplenews_absolute_mail_urls($match) {
  global $base_url, $base_path;
  static $regexp;
  $url = $label = '';

  if ($match) {
    if (empty($regexp)) {
      $regexp = '@^' . preg_quote($base_path, '@') . '@';
    }
    list(, $url, $label) = $match;
    $url = strpos($url, '://') ? $url : preg_replace($regexp, $base_url . '/', $url);

    // If the link is formed by Drupal's URL filter, we only return the URL.
    // The URL filter generates a label out of the original URL.
    if (strpos($label, '...') === strlen($label) - 3) {
      // Remove ellipsis from end of label.
      $label = substr($label, 0, strlen($label) - 3);
    }
    if (strpos($url, $label) !== FALSE) {
      return $url;
    }
    return $label . ' ' . $url;
  }
}

/**
 * Helper function for simplenews_html_to_text().
 *
 * List of preg* regular expression patterns to search for and replace with
 */
function _simplenews_html_replace() {
  return array(
    '/&quot;/i'  => '"',
    '/&gt;/i'    => '>',
    '/&lt;/i'    => '<',
    '/&amp;/i'   => '&',
    '/&copy;/i'  => '(c)',
    '/&trade;/i' => '(tm)',
    '/&#8220;/'  => '"',
    '/&#8221;/'  => '"',
    '/&#8211;/'  => '-',
    '/&#8217;/'  => "'",
    '/&#38;/'    => '&',
    '/&#169;/'   => '(c)',
    '/&#8482;/'  => '(tm)',
    '/&#151;/'   => '--',
    '/&#147;/'   => '"',
    '/&#148;/'   => '"',
    '/&#149;/'   => '*',
    '/&reg;/i'   => '(R)',
    '/&bull;/i'  => '*',
    '/&euro;/i'  => 'Euro ',
  );
}

/**
 * Helper function to measure PHP execution time in microseconds.
 *
 * @param bool $start TRUE reset the time and start counting.
 * @return float: elapsed PHP execution time since start.
 */
function _simplenews_measure_usec($start = FALSE) {
  // Windows systems don't implement getrusage(). There is no alternative.
  if (!function_exists('getrusage')) {
    return;
  }

  static $start_time;
  $usage = getrusage();
  $now = (float)($usage['ru_stime.tv_sec'] . '.' . $usage['ru_stime.tv_usec']) + (float)($usage['ru_utime.tv_sec'] . '.' . $usage['ru_utime.tv_usec']); 

  if ($start) {
    $start_time = $now;
    return;
  }
  return $now - $start_time;
}
