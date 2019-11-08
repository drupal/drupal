<?php

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests access to Layout Builder.
 *
 * @group layout_builder
 */
class LayoutBuilderAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'block_test',
    'node',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable Layout Builder for one content type.
    $this->createContentType(['type' => 'bundle_with_section_field']);
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_section_field.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    // Enable Layout Builder for user profiles.
    $values = [
      'targetEntityType' => 'user',
      'bundle' => 'user',
      'mode' => 'default',
      'status' => TRUE,
    ];
    LayoutBuilderEntityViewDisplay::create($values)
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
  }

  /**
   * Tests Layout Builder access for an entity type that has bundles.
   *
   * @dataProvider providerTestAccessWithBundles
   *
   * @param array $permissions
   *   An array of permissions to grant to the user.
   * @param bool $default_access
   *   Whether access is expected for the defaults.
   * @param bool $non_editable_access
   *   Whether access is expected for a non-editable override.
   * @param bool $editable_access
   *   Whether access is expected for an editable override.
   */
  public function testAccessWithBundles(array $permissions, $default_access, $non_editable_access, $editable_access) {
    $permissions[] = 'edit own bundle_with_section_field content';
    $permissions[] = 'access content';
    $user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($user);

    $editable_node = $this->createNode([
      'uid' => $user->id(),
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'The first node body',
        ],
      ],
    ]);
    $non_editable_node = $this->createNode([
      'uid' => 1,
      'type' => 'bundle_with_section_field',
      'title' => 'The second node title',
      'body' => [
        [
          'value' => 'The second node body',
        ],
      ],
    ]);

    $non_viewable_node = $this->createNode([
      'uid' => $user->id(),
      'status' => 0,
      'type' => 'bundle_with_section_field',
      'title' => 'Nobody can see this node.',
      'body' => [
        [
          'value' => 'Does it really exist?',
        ],
      ],
    ]);

    $this->drupalGet($editable_node->toUrl('edit-form'));
    $this->assertExpectedAccess(TRUE);

    $this->drupalGet($non_editable_node->toUrl('edit-form'));
    $this->assertExpectedAccess(FALSE);

    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default/layout');
    $this->assertExpectedAccess($default_access);

    $this->drupalGet('node/' . $editable_node->id() . '/layout');
    $this->assertExpectedAccess($editable_access);

    $this->drupalGet('node/' . $non_editable_node->id() . '/layout');
    $this->assertExpectedAccess($non_editable_access);

    $this->drupalGet($non_viewable_node->toUrl());
    $this->assertExpectedAccess(FALSE);

    $this->drupalGet('node/' . $non_viewable_node->id() . '/layout');
    $this->assertExpectedAccess(FALSE);
  }

  /**
   * Provides test data for ::testAccessWithBundles().
   */
  public function providerTestAccessWithBundles() {
    // Data provider values are:
    // - the permissions to grant to the user
    // - whether access is expected for the defaults
    // - whether access is expected for a non-editable override
    // - whether access is expected for an editable override.
    $data = [];
    $data['configure any layout'] = [
      ['configure any layout', 'administer node display'],
      TRUE,
      TRUE,
      TRUE,
    ];
    $data['override permissions'] = [
      ['configure all bundle_with_section_field node layout overrides'],
      FALSE,
      TRUE,
      TRUE,
    ];
    $data['editable override permissions'] = [
      ['configure editable bundle_with_section_field node layout overrides'],
      FALSE,
      FALSE,
      TRUE,
    ];
    return $data;
  }

  /**
   * Tests Layout Builder access for an entity type that does not have bundles.
   *
   * @dataProvider providerTestAccessWithoutBundles
   */
  public function testAccessWithoutBundles(array $permissions, $default_access, $non_editable_access, $editable_access) {
    $permissions[] = 'access user profiles';
    $user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($user);

    $this->drupalGet('admin/config/people/accounts/display/default/layout');
    $this->assertExpectedAccess($default_access);

    $this->drupalGet($user->toUrl());
    $this->assertExpectedAccess(TRUE);
    $this->drupalGet($user->toUrl('edit-form'));
    $this->assertExpectedAccess(TRUE);

    $this->drupalGet('user/' . $user->id() . '/layout');
    $this->assertExpectedAccess($editable_access);

    $non_editable_user = $this->drupalCreateUser();
    $this->drupalGet($non_editable_user->toUrl());
    $this->assertExpectedAccess(TRUE);
    $this->drupalGet($non_editable_user->toUrl('edit-form'));
    $this->assertExpectedAccess(FALSE);

    $this->drupalGet('user/' . $non_editable_user->id() . '/layout');
    $this->assertExpectedAccess($non_editable_access);

    $non_viewable_user = $this->drupalCreateUser([], 'bad person', FALSE, ['status' => 0]);
    $this->drupalGet($non_viewable_user->toUrl());
    $this->assertExpectedAccess(FALSE);
    $this->drupalGet($non_viewable_user->toUrl('edit-form'));
    $this->assertExpectedAccess(FALSE);

    $this->drupalGet('user/' . $non_viewable_user->id() . '/layout');
    $this->assertExpectedAccess(FALSE);
  }

  /**
   * Provides test data for ::testAccessWithoutBundles().
   */
  public function providerTestAccessWithoutBundles() {
    // Data provider values are:
    // - the permissions to grant to the user
    // - whether access is expected for the defaults
    // - whether access is expected for a non-editable override
    // - whether access is expected for an editable override.
    $data = [];
    $data['configure any layout'] = [
      ['configure any layout', 'administer user display'],
      TRUE,
      TRUE,
      TRUE,
    ];
    $data['override permissions'] = [
      ['configure all user user layout overrides'],
      FALSE,
      TRUE,
      TRUE,
    ];
    $data['editable override permissions'] = [
      ['configure editable user user layout overrides'],
      FALSE,
      FALSE,
      TRUE,
    ];
    return $data;
  }

  /**
   * Asserts the correct response code is returned based on expected access.
   *
   * @param bool $expected_access
   *   The expected access.
   */
  private function assertExpectedAccess($expected_access) {
    $expected_status_code = $expected_access ? 200 : 403;
    $this->assertSession()->statusCodeEquals($expected_status_code);
  }

}
