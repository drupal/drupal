<?php

declare(strict_types=1);

namespace Drupal\Tests\user\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript functionality of the permission filter.
 *
 * @group user
 */
class PermissionFilterTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer permissions',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that filter results announcement has correct pluralization.
   */
  public function testPermissionFilter(): void {
    // Find the permission filter field.
    $this->drupalGet('admin/people/permissions');
    $assertSession = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    $filter = $page->findField('edit-text');

    // Get all permission rows, for assertions later.
    $permission_rows = $page->findAll('css', 'tbody tr td .permission');

    // Administer filter reduces the number of visible rows.
    $filter->setValue('Administer');
    $session->wait(1000, "jQuery('tr[data-drupal-selector=\"edit-permissions-access-content\"]').length == 0");
    $visible_rows = $this->filterVisibleElements($permission_rows);
    // Test Drupal.announce() message when multiple matches are expected.
    $expected_message = count($visible_rows) . ' permissions are available in the modified list.';
    $assertSession->elementTextContains('css', '#drupal-live-announce', $expected_message);
    self::assertGreaterThan(count($visible_rows), count($permission_rows));
    self::assertGreaterThan(1, count($visible_rows));

    // Test Drupal.announce() message when one match is expected.
    // Using a very specific permission name, we expect only one row.
    $filter->setValue('Administer site configuration');
    $session->wait(1000, "jQuery('tr[data-drupal-selector=\"edit-permissions-access-content\"]').length == 0");
    $visible_rows = $this->filterVisibleElements($permission_rows);
    self::assertEquals(1, count($visible_rows));
    $expected_message = '1 permission is available in the modified list.';
    $assertSession->elementTextContains('css', '#drupal-live-announce', $expected_message);

    // Test Drupal.announce() message when no matches are expected.
    $filter->setValue('Pan-Galactic Gargle Blaster');
    $session->wait(1000, "jQuery('tr[data-drupal-selector=\"edit-permissions-access-content\"]').length == 0");
    $visible_rows = $this->filterVisibleElements($permission_rows);
    self::assertEquals(0, count($visible_rows));

    $expected_message = '0 permissions are available in the modified list.';
    $assertSession->elementTextContains('css', '#drupal-live-announce', $expected_message);
  }

  /**
   * Removes any non-visible elements from the passed array.
   *
   * @param \Behat\Mink\Element\NodeElement[] $elements
   *   An array of node elements.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   An array of node elements.
   */
  protected function filterVisibleElements(array $elements): array {
    $elements = array_filter($elements, function ($element) {
      return $element->isVisible();
    });
    return $elements;
  }

}
