<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Provides a base class for testing the Views UI.
 */
abstract class UITestBase extends ViewTestBase {

  /**
   * An admin user with the 'administer views' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * An admin user with administrative permissions for views, blocks, and nodes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $fullAdminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'views_ui', 'block', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->enableViewsTestModule();

    $this->adminUser = $this->drupalCreateUser(['administer views']);

    $this->fullAdminUser = $this->drupalCreateUser(['administer views',
      'administer blocks',
      'bypass node access',
      'access user profiles',
      'view all revisions',
      'administer permissions',
    ]);
    $this->drupalLogin($this->fullAdminUser);
  }

  /**
   * A helper method which creates a random view.
   */
  public function randomView(array $view = []) {
    // Create a new view in the UI.
    $default = [];
    $default['label'] = $this->randomMachineName(16);
    $default['id'] = strtolower($this->randomMachineName(16));
    $default['description'] = $this->randomMachineName(16);
    $default['page[create]'] = TRUE;
    $default['page[path]'] = $default['id'];

    $view += $default;

    $this->drupalPostForm('admin/structure/views/add', $view, 'Save and edit');

    return $default;
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalGet($path, array $options = [], array $headers = []) {
    $url = $this->buildUrl($path, $options);

    // Ensure that each nojs page is accessible via ajax as well.
    if (strpos($url, '/nojs/') !== FALSE) {
      $url = preg_replace('|/nojs/|', '/ajax/', $url, 1);
      $result = $this->drupalGet($url, $options);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->responseHeaderEquals('Content-Type', 'application/json');
      $this->assertNotEmpty(json_decode($result), 'Ensure that the AJAX request returned valid content.');
    }

    return parent::drupalGet($path, $options, $headers);
  }

}
