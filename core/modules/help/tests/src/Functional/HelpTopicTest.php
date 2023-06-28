<?php

namespace Drupal\Tests\help\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;

/**
 * Verifies help topic display and user access to help based on permissions.
 *
 * @group help
 */
class HelpTopicTest extends BrowserTestBase {
  use AssertBreadcrumbTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'help_topics_test',
    'help',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The admin user that will be created.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The user who can see help but not the special route.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $noTestUser;

  /**
   * The anonymous user that will be created.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anyUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // These tests rely on some markup from the 'stark' theme and we test theme
    // provided help topics.
    \Drupal::service('theme_installer')->install(['help_topics_test_theme']);

    // Place various blocks.
    $settings = [
      'theme' => 'stark',
      'region' => 'help',
    ];
    $this->placeBlock('help_block', $settings);
    $this->placeBlock('local_tasks_block', $settings);
    $this->placeBlock('local_actions_block', $settings);
    $this->placeBlock('page_title_block', $settings);
    $this->placeBlock('system_breadcrumb_block', $settings);

    // Create users.
    $this->adminUser = $this->createUser([
      'access administration pages',
      'access help pages',
      'view the administration theme',
      'administer permissions',
      'administer site configuration',
      'access test help',
    ]);

    $this->noTestUser = $this->createUser([
      'access help pages',
      'view the administration theme',
      'administer permissions',
      'administer site configuration',
    ]);

    $this->anyUser = $this->createUser([]);
  }

  /**
   * Tests the main help page and individual pages for topics.
   */
  public function testHelp() {
    $session = $this->assertSession();

    // Log in the regular user.
    $this->drupalLogin($this->anyUser);
    $this->verifyHelp(403);

    // Log in the admin user.
    $this->drupalLogin($this->adminUser);
    $this->verifyHelp();
    $this->verifyBreadCrumb();

    // Verify that help topics text appears on admin/help, and cache tags.
    $this->drupalGet('admin/help');
    $session->responseContains('<h2>Topics</h2>');
    $session->pageTextContains('Topics can be provided by modules or themes');
    $session->responseHeaderContains('X-Drupal-Cache-Tags', 'core.extension');

    // Verify links for help topics and order.
    $page_text = $this->getTextContent();
    $start = strpos($page_text, 'Topics can be provided');
    $pos = $start;
    foreach ($this->getTopicList() as $info) {
      $name = $info['name'];
      $session->linkExists($name);
      $new_pos = strpos($page_text, $name, $start);
      $this->assertGreaterThan($pos, $new_pos, "Order of $name is not correct on page");
      $pos = $new_pos;
    }

    // Ensure the plugin manager alter hook works as expected.
    $session->linkExists('ABC Help Test module');
    \Drupal::state()->set('help_topics_test.test:top_level', FALSE);
    \Drupal::service('plugin.manager.help_topic')->clearCachedDefinitions();
    $this->drupalGet('admin/help');
    $session->linkNotExists('ABC Help Test module');
    \Drupal::state()->set('help_topics_test.test:top_level', TRUE);
    \Drupal::service('plugin.manager.help_topic')->clearCachedDefinitions();
    $this->drupalGet('admin/help');

    // Ensure all the expected links are present before uninstalling.
    $session->linkExists('ABC Help Test module');
    $session->linkExists('ABC Help Test');
    $session->linkExists('XYZ Help Test theme');

    // Uninstall the test module and verify the topics are gone, after
    // reloading page.
    $this->container->get('module_installer')->uninstall(['help_topics_test']);
    $this->drupalGet('admin/help');
    $session->linkNotExists('ABC Help Test module');
    $session->linkNotExists('ABC Help Test');
    $session->linkExists('XYZ Help Test theme');

    // Uninstall the test theme and verify the topic is gone.
    $this->container->get('theme_installer')->uninstall(['help_topics_test_theme']);
    $this->drupalGet('admin/help');
    $session->linkNotExists('XYZ Help Test theme');
  }

  /**
   * Verifies the logged in user has access to various help links and pages.
   *
   * @param int $response
   *   (optional) The HTTP response code to test for. If it's 200 (default),
   *   the test verifies the user sees the help; if it's not, it verifies they
   *   are denied access.
   */
  protected function verifyHelp($response = 200) {
    // Verify access to help topic pages.
    foreach ($this->getTopicList() as $topic => $info) {
      // View help topic page.
      $this->drupalGet('admin/help/topic/' . $topic);
      $session = $this->assertSession();
      $session->statusCodeEquals($response);
      if ($response == 200) {
        // Verify page information.
        $name = $info['name'];
        $session->titleEquals($name . ' | Drupal');
        $session->responseContains('<h1>' . $name . '</h1>');
        foreach ($info['tags'] as $tag) {
          $session->responseHeaderContains('X-Drupal-Cache-Tags', $tag);
        }
      }
    }
  }

  /**
   * Verifies links on various topic pages.
   */
  public function testHelpLinks() {
    $session = $this->assertSession();
    $this->drupalLogin($this->adminUser);

    // Verify links on the test top-level page.
    $page = 'admin/help/topic/help_topics_test.test';
    // Array element is the page text if you click through.
    $links = [
      'Linked topic' => 'This topic is not supposed to be top-level',
      'Additional topic' => 'This topic should get listed automatically',
      'URL test topic' => 'It is used to test URLs',
    ];
    foreach ($links as $link_text => $page_text) {
      $this->drupalGet($page);
      $this->clickLink($link_text);
      $session->pageTextContains($page_text);
    }

    // Verify theme provided help topics work and can be related.
    $this->drupalGet('admin/help/topic/help_topics_test_theme.test');
    $session->pageTextContains('This is a theme provided topic.');
    $this->assertStringContainsString('This is a theme provided topic.', $session->elementExists('css', 'article')->getText());
    $this->clickLink('Additional topic');
    $session->linkExists('XYZ Help Test theme');

    // Verify that the non-top-level topics do not appear on the Help page.
    $this->drupalGet('admin/help');
    $session->linkNotExists('Linked topic');
    $session->linkNotExists('Additional topic');

    // Verify links and non-links on the URL test page.
    $this->drupalGet('admin/help/topic/help_topics_test.test_urls');
    $links = [
      'not a route' => FALSE,
      'missing params' => FALSE,
      'invalid params' => FALSE,
      'valid link' => TRUE,
      'Additional topic' => TRUE,
      'Missing help topic not_a_topic' => FALSE,
    ];
    foreach ($links as $text => $should_be_link) {
      if ($should_be_link) {
        $session->linkExists($text);
      }
      else {
        // Should be text that is not a link.
        $session->pageTextContains($text);
        $session->linkNotExists($text);
      }
    }

    // Verify that the "no test" user, who should not be able to access
    // the 'valid link' URL, sees it as not a link.
    $this->drupalLogin($this->noTestUser);
    $this->drupalGet('admin/help/topic/help_topics_test.test_urls');
    $session->pageTextContains('valid link');
    $session->linkNotExists('valid link');
  }

  /**
   * Gets a list of topic IDs to test.
   *
   * @return array
   *   A list of topics to test, in the order in which they should appear. The
   *   keys are the machine names of the topics. The values are arrays with the
   *   following elements:
   *   - name: Displayed name.
   *   - tags: Cache tags to test for.
   */
  protected function getTopicList() {
    return [
      'help_topics_test.test' => [
        'name' => 'ABC Help Test module',
        'tags' => ['core.extension'],
      ],
      'help_topics_derivatives:test_derived_topic' => [
        'name' => 'Label for test_derived_topic',
        'tags' => ['foobar'],
      ],
      'help_topics_test_direct_yml' => [
        'name' => 'Test direct yaml topic label',
        'tags' => ['foobar'],
      ],
    ];
  }

  /**
   * Tests breadcrumb on a help topic page.
   */
  public function verifyBreadCrumb() {
    // Verify Help Topics administration breadcrumbs.
    $trail = [
      '' => 'Home',
      'admin' => 'Administration',
      'admin/help' => 'Help',
    ];
    $this->assertBreadcrumb('admin/help/topic/help_topics_test.test', $trail);
    // Ensure we are on the expected help topic page.
    $this->assertSession()->pageTextContains('Also there should be a related topic link below to the Help module topic page and the linked topic.');

    // Verify that another page does not have the help breadcrumb.
    $trail = [
      '' => 'Home',
      'admin' => 'Administration',
      'admin/config' => 'Configuration',
      'admin/config/system' => 'System',
    ];
    $this->assertBreadcrumb('admin/config/system/site-information', $trail);
  }

}
