<?php

/**
 * @file
 * Contains \Drupal\dblog\Tests\DbLogTest.
 */

namespace Drupal\dblog\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\dblog\Controller\DbLogController;
use Drupal\simpletest\WebTestBase;

/**
 * Generate events and verify dblog entries; verify user access to log reports
 * based on persmissions.
 *
 * @group dblog
 */
class DbLogTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('dblog', 'node', 'forum', 'help', 'block');

  /**
   * A user with some relevant administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user without any permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');

    // Create users with specific permissions.
    $this->adminUser = $this->drupalCreateUser(array('administer site configuration', 'access administration pages', 'access site reports', 'administer users'));
    $this->webUser = $this->drupalCreateUser(array());
  }

  /**
   * Tests Database Logging module functionality through interfaces.
   *
   * First logs in users, then creates database log events, and finally tests
   * Database Logging module functionality through both the admin and user
   * interfaces.
   */
  function testDbLog() {
    // Login the admin user.
    $this->drupalLogin($this->adminUser);

    $row_limit = 100;
    $this->verifyRowLimit($row_limit);
    $this->verifyCron($row_limit);
    $this->verifyEvents();
    $this->verifyReports();
    $this->verifyBreadcrumbs();
    // Verify the overview table sorting.
    $orders = array('Date', 'Type', 'User');
    $sorts = array('asc', 'desc');
    foreach ($orders as $order) {
      foreach ($sorts as $sort) {
        $this->verifySort($sort, $order);
      }
    }

    // Login the regular user.
    $this->drupalLogin($this->webUser);
    $this->verifyReports(403);
  }

  /**
   * Verifies setting of the database log row limit.
   *
   * @param int $row_limit
   *   The row limit.
   */
  private function verifyRowLimit($row_limit) {
    // Change the database log row limit.
    $edit = array();
    $edit['dblog_row_limit'] = $row_limit;
    $this->drupalPostForm('admin/config/development/logging', $edit, t('Save configuration'));
    $this->assertResponse(200);

    // Check row limit variable.
    $current_limit = $this->config('dblog.settings')->get('row_limit');
    $this->assertTrue($current_limit == $row_limit, format_string('[Cache] Row limit variable of @count equals row limit of @limit', array('@count' => $current_limit, '@limit' => $row_limit)));
  }

  /**
   * Verifies that cron correctly applies the database log row limit.
   *
   * @param int $row_limit
   *   The row limit.
   */
  private function verifyCron($row_limit) {
    // Generate additional log entries.
    $this->generateLogEntries($row_limit + 10);
    // Verify that the database log row count exceeds the row limit.
    $count = db_query('SELECT COUNT(wid) FROM {watchdog}')->fetchField();
    $this->assertTrue($count > $row_limit, format_string('Dblog row count of @count exceeds row limit of @limit', array('@count' => $count, '@limit' => $row_limit)));

    // Run a cron job.
    $this->cronRun();
    // Verify that the database log row count equals the row limit plus one
    // because cron adds a record after it runs.
    $count = db_query('SELECT COUNT(wid) FROM {watchdog}')->fetchField();
    $this->assertTrue($count == $row_limit + 1, format_string('Dblog row count of @count equals row limit of @limit plus one', array('@count' => $count, '@limit' => $row_limit)));
  }

  /**
   * Generates a number of random database log events.
   *
   * @param int $count
   *   Number of watchdog entries to generate.
   * @param string $type
   *   (optional) The type of watchdog entry. Defaults to 'custom'.
   * @param int $severity
   *   (optional) The severity of the watchdog entry. Defaults to
   *   \Drupal\Core\Logger\RfcLogLevel::NOTICE.
   */
  private function generateLogEntries($count, $type = 'custom', $severity = RfcLogLevel::NOTICE) {
    global $base_root;

    // Prepare the fields to be logged
    $log = array(
      'channel'     => $type,
      'message'     => 'Log entry added to test the dblog row limit.',
      'variables'   => array(),
      'severity'    => $severity,
      'link'        => NULL,
      'user'        => $this->adminUser,
      'uid'         => $this->adminUser->id(),
      'request_uri' => $base_root . request_uri(),
      'referer'     => \Drupal::request()->server->get('HTTP_REFERER'),
      'ip'          => '127.0.0.1',
      'timestamp'   => REQUEST_TIME,
      );
    $message = 'Log entry added to test the dblog row limit. Entry #';
    for ($i = 0; $i < $count; $i++) {
      $log['message'] = $message . $i;
      $this->container->get('logger.dblog')->log($severity, $log['message'], $log);
    }
  }

  /**
   * Confirms that database log reports are displayed at the correct paths.
   *
   * @param int $response
   *   (optional) HTTP response code. Defaults to 200.
   */
  private function verifyReports($response = 200) {
    // View the database log help page.
    $this->drupalGet('admin/help/dblog');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText(t('Database Logging'), 'DBLog help was displayed');
    }

    // View the database log report page.
    $this->drupalGet('admin/reports/dblog');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText(t('Recent log messages'), 'DBLog report was displayed');
    }

    // View the database log page-not-found report page.
    $this->drupalGet('admin/reports/page-not-found');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText("Top 'page not found' errors", 'DBLog page-not-found report was displayed');
    }

    // View the database log access-denied report page.
    $this->drupalGet('admin/reports/access-denied');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText("Top 'access denied' errors", 'DBLog access-denied report was displayed');
    }

    // View the database log event page.
    $wid = db_query('SELECT MIN(wid) FROM {watchdog}')->fetchField();
    $this->drupalGet('admin/reports/dblog/event/' . $wid);
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText(t('Details'), 'DBLog event node was displayed');
    }

  }

  /**
   * Generates and then verifies breadcrumbs.
   */
  private function verifyBreadcrumbs() {
    // View the database log event page.
    $wid = db_query('SELECT MIN(wid) FROM {watchdog}')->fetchField();
    $this->drupalGet('admin/reports/dblog/event/' . $wid);
    $xpath = '//nav[@class="breadcrumb"]/ol/li[last()]/a';
    $this->assertEqual(current($this->xpath($xpath)), 'Recent log messages', 'DBLogs link displayed at breadcrumb in event page.');
  }

  /**
   * Generates and then verifies various types of events.
   */
  private function verifyEvents() {
    // Invoke events.
    $this->doUser();
    $this->drupalCreateContentType(array('type' => 'article', 'name' => t('Article')));
    $this->drupalCreateContentType(array('type' => 'page', 'name' => t('Basic page')));
    $this->doNode('article');
    $this->doNode('page');
    $this->doNode('forum');

    // When a user account is canceled, any content they created remains but the
    // uid = 0. Records in the watchdog table related to that user have the uid
    // set to zero.
  }

  /**
   * Verifies the sorting functionality of the database logging reports table.
   *
   * @param string $sort
   *   The sort direction.
   * @param string $order
   *   The order by which the table should be sorted.
   */
  public function verifySort($sort = 'asc', $order = 'Date') {
    $this->drupalGet('admin/reports/dblog', array('query' => array('sort' => $sort, 'order' => $order)));
    $this->assertResponse(200);
    $this->assertText(t('Recent log messages'), 'DBLog report was displayed correctly and sorting went fine.');
  }

  /**
   * Generates and then verifies some user events.
   */
  private function doUser() {
    // Set user variables.
    $name = $this->randomMachineName();
    $pass = user_password();
    // Add a user using the form to generate an add user event (which is not
    // triggered by drupalCreateUser).
    $edit = array();
    $edit['name'] = $name;
    $edit['mail'] = $name . '@example.com';
    $edit['pass[pass1]'] = $pass;
    $edit['pass[pass2]'] = $pass;
    $edit['status'] = 1;
    $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));
    $this->assertResponse(200);
    // Retrieve the user object.
    $user = user_load_by_name($name);
    $this->assertTrue($user != NULL, format_string('User @name was loaded', array('@name' => $name)));
    // pass_raw property is needed by drupalLogin.
    $user->pass_raw = $pass;
    // Login user.
    $this->drupalLogin($user);
    // Logout user.
    $this->drupalLogout();
    // Fetch the row IDs in watchdog that relate to the user.
    $result = db_query('SELECT wid FROM {watchdog} WHERE uid = :uid', array(':uid' => $user->id()));
    foreach ($result as $row) {
      $ids[] = $row->wid;
    }
    $count_before = (isset($ids)) ? count($ids) : 0;
    $this->assertTrue($count_before > 0, format_string('DBLog contains @count records for @name', array('@count' => $count_before, '@name' => $user->getUsername())));

    // Login the admin user.
    $this->drupalLogin($this->adminUser);
    // Delete the user created at the start of this test.
    // We need to POST here to invoke batch_process() in the internal browser.
    $this->drupalPostForm('user/' . $user->id() . '/cancel', array('user_cancel_method' => 'user_cancel_reassign'), t('Cancel account'));

    // View the database log report.
    $this->drupalGet('admin/reports/dblog');
    $this->assertResponse(200);

    // Verify that the expected events were recorded.
    // Add user.
    // Default display includes name and email address; if too long, the email
    // address is replaced by three periods.
    $this->assertLogMessage(t('New user: %name %email.', array('%name' => $name, '%email' => '<' . $user->getEmail() . '>')), 'DBLog event was recorded: [add user]');
    // Login user.
    $this->assertLogMessage(t('Session opened for %name.', array('%name' => $name)), 'DBLog event was recorded: [login user]');
    // Logout user.
    $this->assertLogMessage(t('Session closed for %name.', array('%name' => $name)), 'DBLog event was recorded: [logout user]');
    // Delete user.
    $message = t('Deleted user: %name %email.', array('%name' => $name, '%email' => '<' . $user->getEmail() . '>'));
    $message_text = Unicode::truncate(Xss::filter($message, array()), 56, TRUE, TRUE);
    // Verify that the full message displays on the details page.
    $link = FALSE;
    if ($links = $this->xpath('//a[text()="' . html_entity_decode($message_text) . '"]')) {
      // Found link with the message text.
      $links = array_shift($links);
      foreach ($links->attributes() as $attr => $value) {
        if ($attr == 'href') {
          // Extract link to details page.
          $link = Unicode::substr($value, strpos($value, 'admin/reports/dblog/event/'));
          $this->drupalGet($link);
          // Check for full message text on the details page.
          $this->assertRaw($message, 'DBLog event details was found: [delete user]');
          break;
        }
      }
    }
    $this->assertTrue($link, 'DBLog event was recorded: [delete user]');
    // Visit random URL (to generate page not found event).
    $not_found_url = $this->randomMachineName(60);
    $this->drupalGet($not_found_url);
    $this->assertResponse(404);
    // View the database log page-not-found report page.
    $this->drupalGet('admin/reports/page-not-found');
    $this->assertResponse(200);
    // Check that full-length URL displayed.
    $this->assertText($not_found_url, 'DBLog event was recorded: [page not found]');
  }

  /**
   * Generates and then verifies some node events.
   *
   * @param string $type
   *   A node type (e.g., 'article', 'page' or 'forum').
   */
  private function doNode($type) {
    // Create user.
    $perm = array('create ' . $type . ' content', 'edit own ' . $type . ' content', 'delete own ' . $type . ' content');
    $user = $this->drupalCreateUser($perm);
    // Login user.
    $this->drupalLogin($user);

    // Create a node using the form in order to generate an add content event
    // (which is not triggered by drupalCreateNode).
    $edit = $this->getContent($type);
    $title = $edit['title[0][value]'];
    $this->drupalPostForm('node/add/' . $type, $edit, t('Save'));
    $this->assertResponse(200);
    // Retrieve the node object.
    $node = $this->drupalGetNodeByTitle($title);
    $this->assertTrue($node != NULL, format_string('Node @title was loaded', array('@title' => $title)));
    // Edit the node.
    $edit = $this->getContentUpdate($type);
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertResponse(200);
    // Delete the node.
    $this->drupalPostForm('node/' . $node->id() . '/delete', array(), t('Delete'));
    $this->assertResponse(200);
    // View the node (to generate page not found event).
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(404);
    // View the database log report (to generate access denied event).
    $this->drupalGet('admin/reports/dblog');
    $this->assertResponse(403);

    // Login the admin user.
    $this->drupalLogin($this->adminUser);
    // View the database log report.
    $this->drupalGet('admin/reports/dblog');
    $this->assertResponse(200);

    // Verify that node events were recorded.
    // Was node content added?
    $this->assertLogMessage(t('@type: added %title.', array('@type' => $type, '%title' => $title)), 'DBLog event was recorded: [content added]');
    // Was node content updated?
    $this->assertLogMessage(t('@type: updated %title.', array('@type' => $type, '%title' => $title)), 'DBLog event was recorded: [content updated]');
    // Was node content deleted?
    $this->assertLogMessage(t('@type: deleted %title.', array('@type' => $type, '%title' => $title)), 'DBLog event was recorded: [content deleted]');

    // View the database log access-denied report page.
    $this->drupalGet('admin/reports/access-denied');
    $this->assertResponse(200);
    // Verify that the 'access denied' event was recorded.
    $this->assertText('admin/reports/dblog', 'DBLog event was recorded: [access denied]');

    // View the database log page-not-found report page.
    $this->drupalGet('admin/reports/page-not-found');
    $this->assertResponse(200);
    // Verify that the 'page not found' event was recorded.
    $this->assertText('node/' . $node->id(), 'DBLog event was recorded: [page not found]');
  }

  /**
   * Creates random content based on node content type.
   *
   * @param string $type
   *   Node content type (e.g., 'article').
   *
   * @return array
   *   Random content needed by various node types.
   */
  private function getContent($type) {
    switch ($type) {
      case 'forum':
        $content = array(
          'title[0][value]' => $this->randomMachineName(8),
          'taxonomy_forums' => array(1),
          'body[0][value]' => $this->randomMachineName(32),
        );
        break;

      default:
        $content = array(
          'title[0][value]' => $this->randomMachineName(8),
          'body[0][value]' => $this->randomMachineName(32),
        );
        break;
    }
    return $content;
  }

  /**
   * Creates random content as an update based on node content type.
   *
   * @param string $type
   *   Node content type (e.g., 'article').
   *
   * @return array
   *   Random content needed by various node types.
   */
  private function getContentUpdate($type) {
    $content = array(
      'body[0][value]' => $this->randomMachineName(32),
    );
    return $content;
  }

  /**
   * Tests the addition and clearing of log events through the admin interface.
   *
   * Logs in the admin user, creates a database log event, and tests the
   * functionality of clearing the database log through the admin interface.
   */
  public function testDBLogAddAndClear() {
    global $base_root;
    // Get a count of how many watchdog entries already exist.
    $count = db_query('SELECT COUNT(*) FROM {watchdog}')->fetchField();
    $log = array(
      'channel'     => 'system',
      'message'     => 'Log entry added to test the doClearTest clear down.',
      'variables'   => array(),
      'severity'    => RfcLogLevel::NOTICE,
      'link'        => NULL,
      'user'        => $this->adminUser,
      'uid'         => $this->adminUser->id(),
      'request_uri' => $base_root . request_uri(),
      'referer'     => \Drupal::request()->server->get('HTTP_REFERER'),
      'ip'          => '127.0.0.1',
      'timestamp'   => REQUEST_TIME,
    );
    // Add a watchdog entry.
    $this->container->get('logger.dblog')->log($log['severity'], $log['message'], $log);
    // Make sure the table count has actually been incremented.
    $this->assertEqual($count + 1, db_query('SELECT COUNT(*) FROM {watchdog}')->fetchField(), format_string('\Drupal\dblog\Logger\DbLog->log() added an entry to the dblog :count', array(':count' => $count)));
    // Login the admin user.
    $this->drupalLogin($this->adminUser);
    // Post in order to clear the database table.
    $this->drupalPostForm('admin/reports/dblog', array(), t('Clear log messages'));
    // Confirm that the logs should be cleared.
    $this->drupalPostForm(NULL, array(), 'Confirm');
    // Count the rows in watchdog that previously related to the deleted user.
    $count = db_query('SELECT COUNT(*) FROM {watchdog}')->fetchField();
    $this->assertEqual($count, 0, format_string('DBLog contains :count records after a clear.', array(':count' => $count)));
  }

  /**
   * Tests the database log filter functionality at admin/reports/dblog.
   */
  public function testFilter() {
    $this->drupalLogin($this->adminUser);

    // Clear the log to ensure that only generated entries will be found.
    db_delete('watchdog')->execute();

    // Generate 9 random watchdog entries.
    $type_names = array();
    $types = array();
    for ($i = 0; $i < 3; $i++) {
      $type_names[] = $type_name = $this->randomMachineName();
      $severity = RfcLogLevel::EMERGENCY;
      for ($j = 0; $j < 3; $j++) {
        $types[] = $type = array(
          'count' => $j + 1,
          'type' => $type_name,
          'severity' => $severity++,
        );
        $this->generateLogEntries($type['count'], $type['type'], $type['severity']);
      }
    }

    // View the database log page.
    $this->drupalGet('admin/reports/dblog');

    // Confirm that all the entries are displayed.
    $count = $this->getTypeCount($types);
    foreach ($types as $key => $type) {
      $this->assertEqual($count[$key], $type['count'], 'Count matched');
    }

    // Filter by each type and confirm that entries with various severities are
    // displayed.
    foreach ($type_names as $type_name) {
      $edit = array(
        'type[]' => array($type_name),
      );
      $this->drupalPostForm(NULL, $edit, t('Filter'));

      // Count the number of entries of this type.
      $type_count = 0;
      foreach ($types as $type) {
        if ($type['type'] == $type_name) {
          $type_count += $type['count'];
        }
      }

      $count = $this->getTypeCount($types);
      $this->assertEqual(array_sum($count), $type_count, 'Count matched');
    }

    // Set the filter to match each of the two filter-type attributes and
    // confirm the correct number of entries are displayed.
    foreach ($types as $type) {
      $edit = array(
        'type[]' => array($type['type']),
        'severity[]' => array($type['severity']),
      );
      $this->drupalPostForm(NULL, $edit, t('Filter'));

      $count = $this->getTypeCount($types);
      $this->assertEqual(array_sum($count), $type['count'], 'Count matched');
    }

    $this->drupalGet('admin/reports/dblog', array('query' => array('order' => 'Type')));
    $this->assertResponse(200);
    $this->assertText(t('Operations'), 'Operations text found');

    // Clear all logs and make sure the confirmation message is found.
    $this->drupalPostForm('admin/reports/dblog', array(), t('Clear log messages'));
    // Confirm that the logs should be cleared.
    $this->drupalPostForm(NULL, array(), 'Confirm');
    $this->assertText(t('Database log cleared.'), 'Confirmation message found');
  }

  /**
   * Gets the database log event information from the browser page.
   *
   * @return array
   *   List of log events where each event is an array with following keys:
   *   - severity: (int) A database log severity constant.
   *   - type: (string) The type of database log event.
   *   - message: (string) The message for this database log event.
   *   - user: (string) The user associated with this database log event.
   */
  protected function getLogEntries() {
    $entries = array();
    if ($table = $this->xpath('.//table[@id="admin-dblog"]')) {
      $table = array_shift($table);
      foreach ($table->tbody->tr as $row) {
        $entries[] = array(
          'severity' => $this->getSeverityConstant($row['class']),
          'type' => $this->asText($row->td[1]),
          'message' => $this->asText($row->td[3]),
          'user' => $this->asText($row->td[4]),
        );
      }
    }
    return $entries;
  }

  /**
   * Gets the count of database log entries by database log event type.
   *
   * @param array $types
   *   The type information to compare against.
   *
   * @return array
   *   The count of each type keyed by the key of the $types array.
   */
  protected function getTypeCount(array $types) {
    $entries = $this->getLogEntries();
    $count = array_fill(0, count($types), 0);
    foreach ($entries as $entry) {
      foreach ($types as $key => $type) {
        if ($entry['type'] == $type['type'] && $entry['severity'] == $type['severity']) {
          $count[$key]++;
          break;
        }
      }
    }
    return $count;
  }

  /**
   * Gets the watchdog severity constant corresponding to the CSS class.
   *
   * @param string $class
   *   CSS class attribute.
   *
   * @return int|null
   *   The watchdog severity constant or NULL if not found.
   */
  protected function getSeverityConstant($class) {
    $map = array_flip(DbLogController::getLogLevelClassMap());

    // Find the class that contains the severity.
    $classes = explode(' ', $class);
    foreach ($classes as $class) {
      if (isset($map[$class])) {
        return $map[$class];
      }
    }
    return NULL;
  }

  /**
   * Extracts the text contained by the XHTML element.
   *
   * @param \SimpleXMLElement $element
   *   Element to extract text from.
   *
   * @return string
   *   Extracted text.
   */
  protected function asText(\SimpleXMLElement $element) {
    if (!is_object($element)) {
      return $this->fail('The element is not an element.');
    }
    return trim(html_entity_decode(strip_tags($element->asXML())));
  }

  /**
   * Confirms that a log message appears on the database log overview screen.
   *
   * This function should only be used for the admin/reports/dblog page, because
   * it checks for the message link text truncated to 56 characters. Other log
   * pages have no detail links so they contain the full message text.
   *
   * @param string $log_message
   *   The database log message to check.
   * @param string $message
   *   The message to pass to simpletest.
   */
  protected function assertLogMessage($log_message, $message) {
    $message_text = Unicode::truncate(Xss::filter($log_message, array()), 56, TRUE, TRUE);
    // After \Drupal\Component\Utility\Xss::filter(), HTML entities should be
    // converted to their character equivalents because assertLink() uses this
    // string in xpath() to query the Document Object Model (DOM).
    $this->assertLink(html_entity_decode($message_text), 0, $message);
  }
}
