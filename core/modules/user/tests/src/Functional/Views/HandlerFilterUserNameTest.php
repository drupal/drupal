<?php

namespace Drupal\Tests\user\Functional\Views;

use Drupal\views\Views;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the handler of the user: name filter.
 *
 * @group user
 * @see Views\user\Plugin\views\filter\Name
 */
class HandlerFilterUserNameTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views_ui', 'user_test_views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_user_name'];

  /**
   * Accounts used by this test.
   *
   * @var array
   */
  protected $accounts = [];

  /**
   * Usernames of $accounts.
   *
   * @var array
   */
  protected $names = [];

  /**
   * Stores the column map for this testCase.
   *
   * @var array
   */
  public $columnMap = [
    'uid' => 'uid',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['user_test_views']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();

    $this->accounts = [];
    $this->names = [];
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
    $view->filter['uid']->value = [$this->accounts[0]->id()];

    $this->executeView($view);
    $this->assertIdenticalResultset($view, [['uid' => $this->accounts[0]->id()]], $this->columnMap);

    $this->assertNull($view->filter['uid']->getValueOptions());
  }

  /**
   * Tests using the user interface.
   */
  public function testAdminUserInterface() {
    $admin_user = $this->drupalCreateUser([
      'administer views',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    $path = 'admin/structure/views/nojs/handler/test_user_name/default/filter/uid';
    $this->drupalGet($path);

    // Pass in an invalid username, the validation should catch it.
    $users = [$this->randomMachineName()];
    $users = array_map('strtolower', $users);
    $edit = [
      'options[value]' => implode(', ', $users),
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('There are no users matching "' . implode(', ', $users) . '".');

    // Pass in an invalid username and a valid username.
    $random_name = $this->randomMachineName();
    $users = [$random_name, $this->names[0]];
    $users = array_map('strtolower', $users);
    $edit = [
      'options[value]' => implode(', ', $users),
    ];
    $users = [$users[0]];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('There are no users matching "' . implode(', ', $users) . '".');

    // Pass in just valid usernames.
    $users = $this->names;
    $users = array_map('strtolower', $users);
    $edit = [
      'options[value]' => implode(', ', $users),
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextNotContains('There are no users matching "' . implode(', ', $users) . '".');
  }

  /**
   * Tests exposed filters.
   */
  public function testExposedFilter() {
    $path = 'test_user_name';

    $options = [];

    // Pass in an invalid username, the validation should catch it.
    $users = [$this->randomMachineName()];
    $users = array_map('strtolower', $users);
    $options['query']['uid'] = implode(', ', $users);
    $this->drupalGet($path, $options);
    $this->assertSession()->pageTextContains('There are no users matching "' . implode(', ', $users) . '".');

    // Pass in an invalid target_id in for the entity_autocomplete value format.
    // There should be no errors, but all results should be returned as the
    // default value for the autocomplete will not match any users so should
    // be empty.
    $options['query']['uid'] = [['target_id' => 9999]];
    $this->drupalGet($path, $options);
    // The actual result should contain all of the user ids.
    foreach ($this->accounts as $account) {
      $this->assertSession()->pageTextContains($account->id());
    }

    // Pass in an invalid username and a valid username.
    $users = [$this->randomMachineName(), $this->names[0]];
    $users = array_map('strtolower', $users);
    $options['query']['uid'] = implode(', ', $users);
    $users = [$users[0]];

    $this->drupalGet($path, $options);
    $this->assertSession()->pageTextContains('There are no users matching "' . implode(', ', $users) . '".');

    // Pass in just valid usernames.
    $users = $this->names;
    $options['query']['uid'] = implode(', ', $users);

    $this->drupalGet($path, $options);
    $this->assertSession()->pageTextNotContains('Unable to find user');
    // The actual result should contain all of the user ids.
    foreach ($this->accounts as $account) {
      $this->assertSession()->pageTextContains($account->id());
    }

    // Pass in just valid user IDs in the entity_autocomplete target_id format.
    $options['query']['uid'] = array_map(function ($account) {
      return ['target_id' => $account->id()];
    }, $this->accounts);

    $this->drupalGet($path, $options);
    $this->assertSession()->pageTextNotContains('Unable to find user');
    // The actual result should contain all of the user ids.
    foreach ($this->accounts as $account) {
      $this->assertSession()->pageTextContains($account->id());
    }
  }

}
