<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\HandlerFilterUserNameTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\views\Views;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the handler of the user: name filter.
 *
 * @see Views\user\Plugin\views\filter\Name
 */
class HandlerFilterUserNameTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui', 'user_test_views');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_user_name');

  /**
   * Accounts used by this test.
   *
   * @var array
   */
  protected $accounts = array();

  /**
   * Usernames of $accounts.
   *
   * @var array
   */
  protected $names = array();

  /**
   * Stores the column map for this testCase.
   *
   * @var array
   */
  public $columnMap = array(
    'uid' => 'uid',
  );

  public static function getInfo() {
    return array(
      'name' => 'User: Name Filter',
      'description' => 'Tests the handler of the user: name filter',
      'group' => 'Views module integration',
    );
  }

  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), array('user_test_views'));

    $this->enableViewsTestModule();

    $this->accounts = array();
    $this->names = array();
    for ($i = 0; $i < 3; $i++) {
      $this->accounts[] = $account = $this->drupalCreateUser();
      $this->names[] = $account->label();
    }
  }


  /**
   * Tests just using the filter.
   */
  public function testUserNameApi() {
    $view = Views::getView('test_user_name');

    $view->initHandlers();
    $view->filter['uid']->value = array($this->accounts[0]->id());

    $this->executeView($view);
    $this->assertIdenticalResultset($view, array(array('uid' => $this->accounts[0]->id())), $this->columnMap);
  }

  /**
   * Tests using the user interface.
   */
  public function testAdminUserInterface() {
    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration'));
    $this->drupalLogin($admin_user);

    $path = 'admin/structure/views/nojs/handler/test_user_name/default/filter/uid';
    $this->drupalGet($path);

    // Pass in an invalid username, the validation should catch it.
    $users = array($this->randomName());
    $users = array_map('strtolower', $users);
    $edit = array(
      'options[value]' => implode(', ', $users)
    );
    $this->drupalPostForm($path, $edit, t('Apply'));
    $message = format_plural(count($users), 'Unable to find user: @users', 'Unable to find users: @users', array('@users' => implode(', ', $users)));
    $this->assertText($message);

    // Pass in an invalid username and a valid username.
    $random_name = $this->randomName();
    $users = array($random_name, $this->names[0]);
    $users = array_map('strtolower', $users);
    $edit = array(
      'options[value]' => implode(', ', $users)
    );
    $users = array($users[0]);
    $this->drupalPostForm($path, $edit, t('Apply'));
    $message = format_plural(count($users), 'Unable to find user: @users', 'Unable to find users: @users', array('@users' => implode(', ', $users)));
    $this->assertRaw($message);

    // Pass in just valid usernames.
    $users = $this->names;
    $users = array_map('strtolower', $users);
    $edit = array(
      'options[value]' => implode(', ', $users)
    );
    $this->drupalPostForm($path, $edit, t('Apply'));
    $message = format_plural(count($users), 'Unable to find user: @users', 'Unable to find users: @users', array('@users' => implode(', ', $users)));
    $this->assertNoRaw($message);
  }

  /**
   * Tests exposed filters.
   */
  public function testExposedFilter() {
    $path = 'test_user_name';

    $options = array();

    // Pass in an invalid username, the validation should catch it.
    $users = array($this->randomName());
    $users = array_map('strtolower', $users);
    $options['query']['uid'] = implode(', ', $users);
    $this->drupalGet($path, $options);
    $message = format_plural(count($users), 'Unable to find user: @users', 'Unable to find users: @users', array('@users' => implode(', ', $users)));
    $this->assertRaw($message);

    // Pass in an invalid username and a valid username.
    $users = array($this->randomName(), $this->names[0]);
    $options['query']['uid'] = implode(', ', $users);
    $users = array_map('strtolower', $users);
    $users = array($users[0]);

    $this->drupalGet($path, $options);
    $message = format_plural(count($users), 'Unable to find user: @users', 'Unable to find users: @users', array('@users' => implode(', ', $users)));
    $this->assertRaw($message);

    // Pass in just valid usernames.
    $users = $this->names;
    $options['query']['uid'] = implode(', ', $users);

    $this->drupalGet($path, $options);
    $this->assertNoRaw('Unable to find user');
    // The actual result should contain all of the user ids.
    foreach ($this->accounts as $account) {
      $this->assertRaw($account->id());
    }
  }

}
