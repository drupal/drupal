<?php

namespace Drupal\Tests\filter\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\user\RoleInterface;

/**
 * Tests the behavior of check_markup() when a filter or text format vanishes,
 * or when check_markup() is called in such a way that it is instructed to skip
 * all filters of the "FilterInterface::TYPE_HTML_RESTRICTOR" type.
 *
 * @group filter
 */
class FilterSecurityTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'filter_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp(): void {
    parent::setUp();

    // Create Basic page node type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    /** @var \Drupal\filter\Entity\FilterFormat $filtered_html_format */
    $filtered_html_format = FilterFormat::load('filtered_html');
    $filtered_html_permission = $filtered_html_format->getPermissionName();
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, [$filtered_html_permission]);

    $this->adminUser = $this->drupalCreateUser([
      'administer modules',
      'administer filters',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests removal of filtered content when an active filter is disabled.
   *
   * Tests that filtered content is emptied when an actively used filter module
   * is disabled.
   */
  public function testDisableFilterModule() {
    // Create a new node.
    $node = $this->drupalCreateNode(['promote' => 1]);
    $body_raw = $node->body->value;
    $format_id = $node->body->format;
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains($body_raw);

    // Enable the filter_test_replace filter.
    $edit = [
      'filters[filter_test_replace][status]' => 1,
    ];
    $this->drupalGet('admin/config/content/formats/manage/' . $format_id);
    $this->submitForm($edit, 'Save configuration');

    // Verify that filter_test_replace filter replaced the content.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextNotContains($body_raw);
    $this->assertSession()->pageTextContains('Filter: Testing filter');

    // Disable the text format entirely.
    $this->drupalGet('admin/config/content/formats/manage/' . $format_id . '/disable');
    $this->submitForm([], 'Disable');

    // Verify that the content is empty, because the text format does not exist.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextNotContains($body_raw);
  }

  /**
   * Tests that security filters are enforced even when marked to be skipped.
   */
  public function testSkipSecurityFilters() {
    $text = "Text with some disallowed tags: <script />, <p><object>unicorn</object></p>, <i><table></i>.";
    $expected_filtered_text = "Text with some disallowed tags: , <p>unicorn</p>, .";
    $this->assertEquals($expected_filtered_text, check_markup($text, 'filtered_html', '', []), 'Expected filter result.');
    $this->assertEquals($expected_filtered_text, check_markup($text, 'filtered_html', '', [FilterInterface::TYPE_HTML_RESTRICTOR]), 'Expected filter result, even when trying to disable filters of the FilterInterface::TYPE_HTML_RESTRICTOR type.');
  }

}
