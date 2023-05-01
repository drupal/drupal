<?php

namespace Drupal\Tests\dblog\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Database;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\dblog\Controller\DbLogController;
use Drupal\error_test\Controller\ErrorTestController;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;

/**
 * Verifies log entries and user access based on permissions.
 *
 * @group dblog
 */
class DbLogTest extends BrowserTestBase {
  use FakeLogEntries;
  use AssertBreadcrumbTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'dblog',
    'error_test',
    'node',
    'forum',
    'help',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('page_title_block');

    // Create users with specific permissions.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'access site reports',
      'administer users',
    ]);
    $this->webUser = $this->drupalCreateUser([]);
  }

  /**
   * Tests Database Logging module functionality through interfaces.
   *
   * First logs in users, then creates database log events, and finally tests
   * Database Logging module functionality through both the admin and user
   * interfaces.
   */
  public function testDbLog() {
    // Log in the admin user.
    $this->drupalLogin($this->adminUser);

    $row_limit = 100;
    $this->verifyRowLimit($row_limit);
    $this->verifyEvents();
    $this->verifyReports();
    $this->verifyBreadcrumbs();
    $this->verifyLinkEscaping();
    // Verify the overview table sorting.
    $orders = ['Date', 'Type', 'User'];
    $sorts = ['asc', 'desc'];
    foreach ($orders as $order) {
      foreach ($sorts as $sort) {
        $this->verifySort($sort, $order);
      }
    }

    // Log in the regular user.
    $this->drupalLogin($this->webUser);
    $this->verifyReports(403);
  }

  /**
   * Tests individual log event page.
   */
  public function testLogEventPage() {
    // Login the admin user.
    $this->drupalLogin($this->adminUser);

    // Since referrer and location links vary by how the tests are run, inject
    // fake log data to test these.
    $context = [
      'request_uri' => 'http://example.com?dblog=1',
      'referer' => 'http://example.org?dblog=2',
      'uid' => 0,
      'channel' => 'testing',
      'link' => 'foo/bar',
      'ip' => '0.0.1.0',
      'timestamp' => REQUEST_TIME,
    ];
    \Drupal::service('logger.dblog')->log(RfcLogLevel::NOTICE, 'Test message', $context);
    $query = Database::getConnection()->select('watchdog');
    $query->addExpression('MAX([wid])');
    $wid = $query->execute()->fetchField();

    // Verify the links appear correctly.
    $this->drupalGet('admin/reports/dblog/event/' . $wid);
    $this->assertSession()->linkByHrefExists($context['request_uri']);
    $this->assertSession()->linkByHrefExists($context['referer']);

    // Verify hostname.
    $this->assertSession()->pageTextContains($context['ip']);

    // Verify location.
    $this->assertSession()->pageTextContains($context['request_uri']);

    // Verify severity.
    $this->assertSession()->pageTextContains('Notice');
  }

  /**
   * Tests that a 403 event is logged with the exception triggering it.
   */
  public function test403LogEventPage() {
    $assert_session = $this->assertSession();
    $uri = 'admin/reports';

    $this->drupalLogin($this->webUser);
    $this->drupalGet($uri);
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);

    $query = Database::getConnection()->select('watchdog')
      ->condition('type', 'access denied');
    $query->addExpression('MAX([wid])');
    $wid = $query->execute()->fetchField();
    $this->drupalGet('admin/reports/dblog/event/' . $wid);

    $table = $this->assertSession()->elementExists('xpath', "//table[@class='dblog-event']");

    // Verify type, severity and location.
    $type = "//tr/th[contains(text(), 'Type')]/../td";
    $this->assertSession()->elementsCount('xpath', $type, 1, $table);
    $this->assertEquals('access denied', $table->findAll('xpath', $type)[0]->getText());
    $severity = "//tr/th[contains(text(), 'Severity')]/../td";
    $this->assertSession()->elementsCount('xpath', $severity, 1, $table);
    $this->assertEquals('Warning', $table->findAll('xpath', $severity)[0]->getText());
    $location = $table->findAll('xpath', "//tr/th[contains(text(), 'Location')]/../td/a");
    $this->assertCount(1, $location);
    $href = $location[0]->getAttribute('href');
    $this->assertEquals($this->baseUrl . '/' . $uri, $href);

    // Verify message.
    $message = $table->findAll('xpath', "//tr/th[contains(text(), 'Message')]/../td");
    $this->assertCount(1, $message);
    $regex = "@Path: .+admin/reports\. Drupal\\\\Core\\\\Http\\\\Exception\\\\CacheableAccessDeniedHttpException: The 'access site reports' permission is required\. in Drupal\\\\Core\\\\Routing\\\\AccessAwareRouter->checkAccess\(\) \(line \d+ of .+/core/lib/Drupal/Core/Routing/AccessAwareRouter\.php\)\.@";
    $this->assertMatchesRegularExpression($regex, $message[0]->getText());
  }

  /**
   * Tests not-existing log event page.
   */
  public function testLogEventNotFoundPage() {
    // Login the admin user.
    $this->drupalLogin($this->adminUser);

    // Try to read details of non-existent event.
    $this->drupalGet('admin/reports/dblog/event/999999');
    // Verify 404 response.
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests individual log event page with missing log attributes.
   *
   * In some cases few log attributes are missing. For example:
   * - Missing referer: When request is made to a specific url directly and
   *   error occurred. In this case there is no referer.
   * - Incorrect location: When location attribute is incorrect uri which can
   *   not be used to generate a valid link.
   */
  public function testLogEventPageWithMissingInfo() {
    $this->drupalLogin($this->adminUser);
    $connection = Database::getConnection();

    // Test log event page with missing referer.
    $this->generateLogEntries(1, [
      'referer' => NULL,
    ]);
    $query = $connection->select('watchdog');
    $query->addExpression('MAX([wid])');
    $wid = $query->execute()->fetchField();
    $this->drupalGet('admin/reports/dblog/event/' . $wid);

    // Verify table headers are present, even though the referrer is missing.
    $this->assertSession()->pageTextContains('Referrer');

    // Verify severity.
    $this->assertSession()->pageTextContains('Notice');

    // Test log event page with incorrect location.
    $request_uri = '/some/incorrect/url';
    $this->generateLogEntries(1, [
      'request_uri' => $request_uri,
    ]);
    $query = $connection->select('watchdog');
    $query->addExpression('MAX([wid])');
    $wid = $query->execute()->fetchField();
    $this->drupalGet('admin/reports/dblog/event/' . $wid);

    // Verify table headers are present.
    $this->assertSession()->pageTextContains('Location');

    // Verify severity.
    $this->assertSession()->pageTextContains('Notice');

    // Verify location is available as plain text.
    $this->assertEquals($request_uri, $this->cssSelect('table.dblog-event > tbody > tr:nth-child(4) > td')[0]->getHtml());
    $this->assertSession()->linkNotExists($request_uri);
  }

  /**
   * Test that twig errors are displayed correctly.
   */
  public function testMessageParsing() {
    $this->drupalLogin($this->adminUser);
    // Log a common twig error with {{ }} and { } variables.
    \Drupal::service('logger.factory')->get("php")
      ->error('Incorrect parameter {{foo}} in path {path}: {value}',
        ['foo' => 'bar', 'path' => '/baz', 'value' => 'horse']
      );
    // View the log page to verify it's correct.
    $wid = \Drupal::database()->query('SELECT MAX(wid) FROM {watchdog}')->fetchField();
    $this->drupalGet('admin/reports/dblog/event/' . $wid);
    $this->assertSession()
      ->responseContains('Incorrect parameter {bar} in path /baz: horse');
  }

  /**
   * Verifies setting of the database log row limit.
   *
   * @param int $row_limit
   *   The row limit.
   */
  private function verifyRowLimit($row_limit) {
    // Change the database log row limit.
    $edit = [];
    $edit['dblog_row_limit'] = $row_limit;
    $this->drupalGet('admin/config/development/logging');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);

    // Check row limit variable.
    $current_limit = $this->config('dblog.settings')->get('row_limit');
    $this->assertEquals($current_limit, $row_limit, new FormattableMarkup('[Cache] Row limit variable of @count equals row limit of @limit', ['@count' => $current_limit, '@limit' => $row_limit]));
  }

  /**
   * Clear the entry logs by clicking on 'Clear log messages' button.
   */
  protected function clearLogsEntries() {
    $this->drupalGet(Url::fromRoute('dblog.confirm'));
  }

  /**
   * Filters the logs according to the specific severity and log entry type.
   *
   * @param string $type
   *   (optional) The log entry type.
   * @param string $severity
   *   (optional) The log entry severity.
   */
  protected function filterLogsEntries($type = NULL, $severity = NULL) {
    $edit = [];
    if (isset($type)) {
      $edit['type[]'] = $type;
    }
    if (isset($severity)) {
      $edit['severity[]'] = $severity;
    }
    $this->submitForm($edit, 'Filter');
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
    $this->assertSession()->statusCodeEquals($response);
    if ($response == 200) {
      $this->assertSession()->pageTextContains('Database Logging');
    }

    // View the database log report page.
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->statusCodeEquals($response);
    if ($response == 200) {
      $this->assertSession()->pageTextContains('Recent log messages');
    }

    $this->drupalGet('admin/reports/dblog/confirm');
    $this->assertSession()->statusCodeEquals($response);
    if ($response == 200) {
      $this->assertSession()->pageTextContains('Are you sure you want to delete the recent logs?');
    }

    // View the database log page-not-found report page.
    $this->drupalGet('admin/reports/page-not-found');
    $this->assertSession()->statusCodeEquals($response);
    if ($response == 200) {
      $this->assertSession()->pageTextContains("Top 'page not found' errors");
    }

    // View the database log access-denied report page.
    $this->drupalGet('admin/reports/access-denied');
    $this->assertSession()->statusCodeEquals($response);
    if ($response == 200) {
      $this->assertSession()->pageTextContains("Top 'access denied' errors");
    }

    // View the database log event page.
    $query = Database::getConnection()->select('watchdog');
    $query->addExpression('MIN([wid])');
    $wid = $query->execute()->fetchField();
    $this->drupalGet('admin/reports/dblog/event/' . $wid);
    $this->assertSession()->statusCodeEquals($response);
    if ($response == 200) {
      $this->assertSession()->pageTextContains('Details');
    }
  }

  /**
   * Generates and then verifies breadcrumbs.
   */
  private function verifyBreadcrumbs() {
    // View the database log event page.
    $query = Database::getConnection()->select('watchdog');
    $query->addExpression('MIN([wid])');
    $wid = $query->execute()->fetchField();
    $trail = [
      '' => 'Home',
      'admin' => 'Administration',
      'admin/reports' => 'Reports',
      'admin/reports/dblog' => 'Recent log messages',
    ];
    $this->assertBreadcrumb('admin/reports/dblog/event/' . $wid, $trail);
  }

  /**
   * Generates and then verifies various types of events.
   */
  private function verifyEvents() {
    // Invoke events.
    $this->doUser();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
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
    $this->drupalGet('admin/reports/dblog', ['query' => ['sort' => $sort, 'order' => $order]]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Recent log messages');
  }

  /**
   * Tests link escaping in the operation row of a database log detail page.
   */
  private function verifyLinkEscaping() {
    $link = Link::fromTextAndUrl('View', Url::fromRoute('entity.node.canonical', ['node' => 1]))->toString();
    $message = 'Log entry added to do the verifyLinkEscaping test.';
    $this->generateLogEntries(1, [
      'message' => $message,
      'link' => $link,
    ]);

    $result = Database::getConnection()->select('watchdog', 'w')->fields('w', ['wid'])->orderBy('wid', 'DESC')->range(0, 1)->execute();
    $this->drupalGet('admin/reports/dblog/event/' . $result->fetchField());

    // Check if the link exists (unescaped).
    $this->assertSession()->responseContains($link);
  }

  /**
   * Generates and then verifies some user events.
   */
  private function doUser() {
    // Set user variables.
    $name = $this->randomMachineName();
    $pass = \Drupal::service('password_generator')->generate();
    // Add a user using the form to generate an add user event (which is not
    // triggered by drupalCreateUser).
    $edit = [];
    $edit['name'] = $name;
    $edit['mail'] = $name . '@example.com';
    $edit['pass[pass1]'] = $pass;
    $edit['pass[pass2]'] = $pass;
    $edit['status'] = 1;
    $this->drupalGet('admin/people/create');
    $this->submitForm($edit, 'Create new account');
    $this->assertSession()->statusCodeEquals(200);
    // Retrieve the user object.
    $user = user_load_by_name($name);
    $this->assertNotNull($user, new FormattableMarkup('User @name was loaded', ['@name' => $name]));
    // pass_raw property is needed by drupalLogin.
    $user->passRaw = $pass;
    // Log in user.
    $this->drupalLogin($user);
    // Log out user.
    $this->drupalLogout();
    // Fetch the row IDs in watchdog that relate to the user.
    $result = Database::getConnection()->select('watchdog', 'w')->fields('w', ['wid'])->condition('uid', $user->id())->execute();
    foreach ($result as $row) {
      $ids[] = $row->wid;
    }
    $count_before = (isset($ids)) ? count($ids) : 0;
    $this->assertGreaterThan(0, $count_before, new FormattableMarkup('DBLog contains @count records for @name', ['@count' => $count_before, '@name' => $user->getAccountName()]));

    // Log in the admin user.
    $this->drupalLogin($this->adminUser);
    // Delete the user created at the start of this test.
    // We need to POST here to invoke batch_process() in the internal browser.
    $this->drupalGet('user/' . $user->id() . '/cancel');
    $this->submitForm(['user_cancel_method' => 'user_cancel_reassign'], 'Confirm');

    // View the database log report.
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->statusCodeEquals(200);

    // Verify that the expected events were recorded.
    // Add user.
    // Default display includes name and email address; if too long, the email
    // address is replaced by three periods.
    $this->assertLogMessage("New user: $name <{$user->getEmail()}>.", 'DBLog event was recorded: [add user]');
    // Log in user.
    $this->assertLogMessage("Session opened for $name.", 'DBLog event was recorded: [login user]');
    // Log out user.
    $this->assertLogMessage("Session closed for $name.", 'DBLog event was recorded: [logout user]');
    // Delete user.
    $message = "Deleted user: $name <{$user->getEmail()}>.";
    $message_text = Unicode::truncate($message, 56, TRUE, TRUE);
    // Verify that the full message displays on the details page.
    $link = FALSE;
    if ($links = $this->xpath('//a[text()="' . $message_text . '"]')) {
      // Found link with the message text.
      $links = array_shift($links);
      $value = $links->getAttribute('href');

      // Extract link to details page.
      $link = mb_substr($value, strpos($value, 'admin/reports/dblog/event/'));
      $this->drupalGet($link);
      // Check for full message text on the details page.
      $this->assertSession()->pageTextContains($message);
    }
    $this->assertNotEmpty($link, 'DBLog event was recorded: [delete user]');
    // Visit random URL (to generate page not found event).
    $not_found_url = $this->randomMachineName(60);
    $this->drupalGet($not_found_url);
    $this->assertSession()->statusCodeEquals(404);
    // View the database log page-not-found report page.
    $this->drupalGet('admin/reports/page-not-found');
    $this->assertSession()->statusCodeEquals(200);
    // Check that full-length URL displayed.
    $this->assertSession()->pageTextContains($not_found_url);
  }

  /**
   * Generates and then verifies some node events.
   *
   * @param string $type
   *   A node type (e.g., 'article', 'page' or 'forum').
   */
  private function doNode($type) {
    // Create user.
    $perm = ['create ' . $type . ' content', 'edit own ' . $type . ' content', 'delete own ' . $type . ' content'];
    $user = $this->drupalCreateUser($perm);
    // Log in user.
    $this->drupalLogin($user);

    // Create a node using the form in order to generate an add content event
    // (which is not triggered by drupalCreateNode).
    $edit = $this->getContent($type);
    $title = $edit['title[0][value]'];
    $this->drupalGet('node/add/' . $type);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    // Retrieve the node object.
    $node = $this->drupalGetNodeByTitle($title);
    $this->assertNotNull($node, new FormattableMarkup('Node @title was loaded', ['@title' => $title]));
    // Edit the node.
    $edit = $this->getContentUpdate($type);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    // Delete the node.
    $this->drupalGet('node/' . $node->id() . '/delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->statusCodeEquals(200);
    // View the node (to generate page not found event).
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(404);
    // View the database log report (to generate access denied event).
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->statusCodeEquals(403);

    // Log in the admin user.
    $this->drupalLogin($this->adminUser);
    // View the database log report.
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->statusCodeEquals(200);

    // Verify that node events were recorded.
    // Was node content added?
    $this->assertLogMessage("$type: added $title.", 'DBLog event was recorded: [content added]');
    // Was node content updated?
    $this->assertLogMessage("$type: updated $title.", 'DBLog event was recorded: [content updated]');
    // Was node content deleted?
    $this->assertLogMessage("$type: deleted $title.", 'DBLog event was recorded: [content deleted]');

    // View the database log access-denied report page.
    $this->drupalGet('admin/reports/access-denied');
    $this->assertSession()->statusCodeEquals(200);
    // Verify that the 'access denied' event was recorded.
    $this->assertSession()->pageTextContains('admin/reports/dblog');

    // View the database log page-not-found report page.
    $this->drupalGet('admin/reports/page-not-found');
    $this->assertSession()->statusCodeEquals(200);
    // Verify that the 'page not found' event was recorded.
    $this->assertSession()->pageTextContains('node/' . $node->id());
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
        $content = [
          'title[0][value]' => $this->randomMachineName(8),
          'taxonomy_forums' => 1,
          'body[0][value]' => $this->randomMachineName(32),
        ];
        break;

      default:
        $content = [
          'title[0][value]' => $this->randomMachineName(8),
          'body[0][value]' => $this->randomMachineName(32),
        ];
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
    $content = [
      'body[0][value]' => $this->randomMachineName(32),
    ];
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
    $connection = Database::getConnection();
    // Get a count of how many watchdog entries already exist.
    $count = $connection->select('watchdog')->countQuery()->execute()->fetchField();
    $log = [
      'channel'     => 'system',
      'message'     => 'Log entry added to test the doClearTest clear down.',
      'variables'   => [],
      'severity'    => RfcLogLevel::NOTICE,
      'link'        => NULL,
      'uid'         => $this->adminUser->id(),
      'request_uri' => $base_root . \Drupal::request()->getRequestUri(),
      'referer'     => \Drupal::request()->server->get('HTTP_REFERER'),
      'ip'          => '127.0.0.1',
      'timestamp'   => REQUEST_TIME,
    ];
    // Add a watchdog entry.
    $this->container->get('logger.dblog')->log($log['severity'], $log['message'], $log);
    // Make sure the table count has actually been incremented.
    $this->assertEquals($count + 1, (int) $connection->select('watchdog')->countQuery()->execute()->fetchField(), new FormattableMarkup('\Drupal\dblog\Logger\DbLog->log() added an entry to the dblog :count', [':count' => $count]));
    // Log in the admin user.
    $this->drupalLogin($this->adminUser);
    // Post in order to clear the database table.
    $this->clearLogsEntries();
    // Confirm that the logs should be cleared.
    $this->submitForm([], 'Confirm');
    // Count the rows in watchdog that previously related to the deleted user.
    $count = $connection->select('watchdog')->countQuery()->execute()->fetchField();
    $this->assertEquals(0, $count, new FormattableMarkup('DBLog contains :count records after a clear.', [':count' => $count]));
  }

  /**
   * Tests the database log filter functionality at admin/reports/dblog.
   */
  public function testFilter() {
    $this->drupalLogin($this->adminUser);

    // Clear the log to ensure that only generated entries will be found.
    Database::getConnection()->delete('watchdog')->execute();

    // Generate 9 random watchdog entries.
    $type_names = [];
    $types = [];
    for ($i = 0; $i < 3; $i++) {
      $type_names[] = $type_name = $this->randomMachineName();
      $severity = RfcLogLevel::EMERGENCY;
      for ($j = 0; $j < 3; $j++) {
        $types[] = $type = [
          'count' => $j + 1,
          'type' => $type_name,
          'severity' => $severity++,
        ];
        $this->generateLogEntries($type['count'], [
          'channel' => $type['type'],
          'severity' => $type['severity'],
        ]);
      }
    }

    // View the database log page.
    $this->drupalGet('admin/reports/dblog');

    // Confirm that all the entries are displayed.
    $count = $this->getTypeCount($types);
    foreach ($types as $key => $type) {
      $this->assertEquals($type['count'], $count[$key], 'Count matched');
    }

    // Filter by each type and confirm that entries with various severities are
    // displayed.
    foreach ($type_names as $type_name) {
      $this->filterLogsEntries($type_name);

      // Count the number of entries of this type.
      $type_count = 0;
      foreach ($types as $type) {
        if ($type['type'] == $type_name) {
          $type_count += $type['count'];
        }
      }

      $count = $this->getTypeCount($types);
      $this->assertEquals($type_count, array_sum($count), 'Count matched');
    }

    // Set the filter to match each of the two filter-type attributes and
    // confirm the correct number of entries are displayed.
    foreach ($types as $type) {
      $this->filterLogsEntries($type['type'], $type['severity']);

      $count = $this->getTypeCount($types);
      $this->assertEquals($type['count'], array_sum($count), 'Count matched');
    }

    $this->drupalGet('admin/reports/dblog', ['query' => ['order' => 'Type']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Operations');
    $this->assertSession()->fieldExists('edit-type');

    // Clear all logs and make sure the confirmation message is found.
    $this->clearLogsEntries();
    // Confirm that the logs should be cleared.
    $this->submitForm([], 'Confirm');
    $this->assertSession()->pageTextContains('Database log cleared.');
    $this->assertSession()->fieldNotExists('edit-type');
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
    $entries = [];
    if ($table = $this->getLogsEntriesTable()) {
      foreach ($table as $row) {
        $cells = $row->findAll('css', 'td');
        $entries[] = [
          'severity' => $this->getSeverityConstant($row->getAttribute('class')),
          'type' => $cells[1]->getText(),
          'message' => $cells[3]->getText(),
          'user' => $cells[4]->getText(),
        ];
      }
    }
    return $entries;
  }

  /**
   * Find the Logs table in the DOM.
   *
   * @return \SimpleXMLElement[]
   *   The return value of a xpath search.
   */
  protected function getLogsEntriesTable() {
    return $this->xpath('.//table[@id="admin-dblog"]/tbody/tr');
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
   *
   * @internal
   */
  protected function assertLogMessage(string $log_message, string $message): void {
    $message_text = Unicode::truncate($log_message, 56, TRUE, TRUE);
    $this->assertSession()->linkExists($message_text, 0, $message);
  }

  /**
   * Tests that the details page displays correctly for a temporary user.
   */
  public function testTemporaryUser() {
    // Create a temporary user.
    $tempuser = $this->drupalCreateUser();
    $tempuser_uid = $tempuser->id();

    // Log in as the admin user.
    $this->drupalLogin($this->adminUser);

    // Generate a single watchdog entry.
    $this->generateLogEntries(1, ['user' => $tempuser, 'uid' => $tempuser_uid]);
    $query = Database::getConnection()->select('watchdog');
    $query->addExpression('MAX([wid])');
    $wid = $query->execute()->fetchField();

    // Check if the full message displays on the details page.
    $this->drupalGet('admin/reports/dblog/event/' . $wid);
    $this->assertSession()->pageTextContains('Dblog test log message');

    // Delete the user.
    $tempuser->delete();
    $this->drupalGet('user/' . $tempuser_uid);
    $this->assertSession()->statusCodeEquals(404);

    // Check if the full message displays on the details page.
    $this->drupalGet('admin/reports/dblog/event/' . $wid);
    $this->assertSession()->pageTextContains('Dblog test log message');
  }

  /**
   * Make sure HTML tags are filtered out in the log overview links.
   */
  public function testOverviewLinks() {
    $this->drupalLogin($this->adminUser);
    // cSpell:disable-next-line
    $this->generateLogEntries(1, ['message' => "&lt;script&gt;alert('foo');&lt;/script&gt;<strong>Lorem</strong> ipsum dolor sit amet, consectetur adipiscing & elit."]);
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->statusCodeEquals(200);
    // Make sure HTML tags are filtered out.
    $this->assertSession()->responseContains('title="alert(&#039;foo&#039;);Lorem');
    $this->assertSession()->responseNotContains("<script>alert('foo');</script>");

    // Make sure HTML tags are filtered out in admin/reports/dblog/event/ too.
    $this->generateLogEntries(1, ['message' => "<script>alert('foo');</script> <strong>Lorem ipsum</strong>"]);
    $query = Database::getConnection()->select('watchdog');
    $query->addExpression('MAX([wid])');
    $wid = $query->execute()->fetchField();
    $this->drupalGet('admin/reports/dblog/event/' . $wid);
    $this->assertSession()->responseNotContains("<script>alert('foo');</script>");
    $this->assertSession()->responseContains("alert('foo'); <strong>Lorem ipsum</strong>");
  }

  /**
   * Tests sorting for entries with the same timestamp.
   */
  public function testSameTimestampEntries() {
    $this->drupalLogin($this->adminUser);

    $this->generateLogEntries(1, ['timestamp' => 1498062000, 'type' => 'same_time', 'message' => 'First']);
    $this->generateLogEntries(1, ['timestamp' => 1498062000, 'type' => 'same_time', 'message' => 'Second']);
    $this->generateLogEntries(1, ['timestamp' => 1498062000, 'type' => 'same_time', 'message' => 'Third']);

    $this->drupalGet('admin/reports/dblog');

    $entries = $this->getLogEntries();
    $this->assertEquals('Third Entry #0', $entries[0]['message']);
    $this->assertEquals('Second Entry #0', $entries[1]['message']);
    $this->assertEquals('First Entry #0', $entries[2]['message']);
  }

  /**
   * Tests that the details page displays correctly backtrace.
   */
  public function testBacktrace() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/error-test/generate-warnings');

    $query = Database::getConnection()->select('watchdog');
    $query->addExpression('MAX([wid])');
    $wid = $query->execute()->fetchField();
    $this->drupalGet('admin/reports/dblog/event/' . $wid);

    $error_user_notice = [
      '%type' => 'User warning',
      '@message' => 'Drupal & awesome',
      '%function' => ErrorTestController::class . '->generateWarnings()',
      '%file' => $this->getModulePath('error_test') . '/error_test.module',
    ];

    // Check if the full message displays on the details page and backtrace is a
    // pre-formatted text.
    $message = new FormattableMarkup('%type: @message in %function (line', $error_user_notice);
    $this->assertSession()->responseContains($message);
    $this->assertSession()->responseContains('<pre class="backtrace">');
  }

}
