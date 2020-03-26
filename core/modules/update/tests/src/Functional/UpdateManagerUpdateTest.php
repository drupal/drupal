<?php

namespace Drupal\Tests\update\Functional;

/**
 * Tests the Update Manager module's 'Update' form and functionality.
 *
 * @todo In https://www.drupal.org/project/drupal/issues/3117229 expand this.
 *
 * @group update
 */
class UpdateManagerUpdateTest extends UpdateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'update',
    'update_test',
    'aaa_update_test',
    'bbb_update_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'administer software updates',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    // The installed state of the system is the same for all test cases. What
    // varies for each test scenario is which release history fixture we fetch,
    // which in turn changes the expected state of the UpdateManagerUpdateForm.
    $system_info = [
      '#all' => [
        'version' => '8.0.0',
      ],
      'aaa_update_test' => [
        'project' => 'aaa_update_test',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ],
      'bbb_update_test' => [
        'project' => 'bbb_update_test',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ],
    ];
    $this->config('update_test.settings')->set('system_info', $system_info)->save();
  }

  /**
   * Provides data for test scenarios involving incompatible updates.
   *
   * These test cases rely on the following fixtures containing the following
   * releases:
   * - aaa_update_test.8.x-1.2.xml
   *   - 8.x-1.2 Compatible with 8.0.0 core.
   * - aaa_update_test.core_compatibility.8.x-1.2_8.x-2.2.xml
   *   - 8.x-1.2 Requires 8.1.0 and above.
   * - bbb_update_test.1_0.xml
   *   - 8.x-1.0 is the only available release.
   * - bbb_update_test.1_1.xml
   *   - 8.x-1.1 is available and compatible with everything (does not define
   *     <core_compatibility> at all).
   * - bbb_update_test.1_2.xml
   *   - 8.x-1.1 is available and compatible with everything (does not define
   *     <core_compatibility> at all).
   *   - 8.x-1.2 is available and requires Drupal 8.1.0 and above.
   *
   * @todo In https://www.drupal.org/project/drupal/issues/3112962:
   *   Change the 'core_fixture' values here to use:
   *   - '1.1' instead of '1.1-core_compatibility'.
   *   - '1.1-alpha1' instead of '1.1-alpha1-core_compatibility'.
   *   Delete the files:
   *   - core/modules/update/tests/modules/update_test/drupal.1.1-alpha1-core_compatibility.xml
   *   - core/modules/update/tests/modules/update_test/drupal.1.1-core_compatibility.xml
   *
   * @return array[]
   *   Test data.
   */
  public function incompatibleUpdatesTableProvider() {
    return [
      'only one compatible' => [
        'core_fixture' => '1.1-core_compatibility',
        // aaa_update_test.8.x-1.2.xml has core compatibility set and will test
        // the case where $recommended_release['core_compatible'] === TRUE in
        // \Drupal\update\Form\UpdateManagerUpdate.
        'a_fixture' => '8.x-1.2',
        // Use a fixture with only a 8.x-1.0 release so BBB is up to date.
        'b_fixture' => '1_0',
        'compatible' => [
          'AAA' => '8.x-1.2',
        ],
        'incompatible' => [],
      ],
      'only one incompatible' => [
        'core_fixture' => '1.1-core_compatibility',
        'a_fixture' => 'core_compatibility.8.x-1.2_8.x-2.2',
        // Use a fixture with only a 8.x-1.0 release so BBB is up to date.
        'b_fixture' => '1_0',
        'compatible' => [],
        'incompatible' => [
          'AAA' => [
            'recommended' => '8.x-1.2',
            'range' => '8.1.0 to 8.1.1',
          ],
        ],
      ],
      'two compatible, no incompatible' => [
        'core_fixture' => '1.1-core_compatibility',
        'a_fixture' => '8.x-1.2',
        // bbb_update_test.1_1.xml does not have core compatibility set and will
        // test the case where $recommended_release['core_compatible'] === NULL
        // in \Drupal\update\Form\UpdateManagerUpdate.
        'b_fixture' => '1_1',
        'compatible' => [
          'AAA' => '8.x-1.2',
          'BBB' => '8.x-1.1',
        ],
        'incompatible' => [],
      ],
      'two incompatible, no compatible' => [
        'core_fixture' => '1.1-core_compatibility',
        'a_fixture' => 'core_compatibility.8.x-1.2_8.x-2.2',
        // bbb_update_test.1_2.xml has core compatibility set and will test the
        // case where $recommended_release['core_compatible'] === FALSE in
        // \Drupal\update\Form\UpdateManagerUpdate.
        'b_fixture' => '1_2',
        'compatible' => [],
        'incompatible' => [
          'AAA' => [
            'recommended' => '8.x-1.2',
            'range' => '8.1.0 to 8.1.1',
          ],
          'BBB' => [
            'recommended' => '8.x-1.2',
            'range' => '8.1.0 to 8.1.1',
          ],
        ],
      ],
      'one compatible, one incompatible' => [
        'core_fixture' => '1.1-core_compatibility',
        'a_fixture' => 'core_compatibility.8.x-1.2_8.x-2.2',
        'b_fixture' => '1_1',
        'compatible' => [
          'BBB' => '8.x-1.1',
        ],
        'incompatible' => [
          'AAA' => [
            'recommended' => '8.x-1.2',
            'range' => '8.1.0 to 8.1.1',
          ],
        ],
      ],
    ];
  }

  /**
   * Tests the Update form for a single test scenario of incompatible updates.
   *
   * @dataProvider incompatibleUpdatesTableProvider
   *
   * @param string $core_fixture
   *   The fixture file to use for Drupal core.
   * @param string $a_fixture
   *   The fixture file to use for the aaa_update_test module.
   * @param string $b_fixture
   *   The fixture file to use for the bbb_update_test module.
   * @param string[] $compatible
   *   Compatible recommended updates (if any). Keys are module identifier
   *   ('AAA' or 'BBB') and values are the expected recommended release.
   * @param string[][] $incompatible
   *   Incompatible recommended updates (if any). Keys are module identifier
   *   ('AAA' or 'BBB') and values are subarrays with the following keys:
   *   - 'recommended': The recommended version.
   *   - 'range': The versions of Drupal core required for that version.
   */
  public function testIncompatibleUpdatesTable($core_fixture, $a_fixture, $b_fixture, array $compatible, array $incompatible) {

    $assert_session = $this->assertSession();
    $compatible_table_locator = '[data-drupal-selector="edit-projects"]';
    $incompatible_table_locator = '[data-drupal-selector="edit-not-compatible"]';

    $this->refreshUpdateStatus(['drupal' => $core_fixture, 'aaa_update_test' => $a_fixture, 'bbb_update_test' => $b_fixture]);
    $this->drupalGet('admin/reports/updates/update');

    if ($compatible) {
      // Verify the number of rows in the table.
      $assert_session->elementsCount('css', "$compatible_table_locator tbody tr", count($compatible));
      // We never want to see a compatibility range in the compatible table.
      $assert_session->elementTextNotContains('css', $compatible_table_locator, 'Requires Drupal core');
      foreach ($compatible as $module => $version) {
        $compatible_row = "$compatible_table_locator tbody tr:contains('$module Update test')";
        // First <td> is the checkbox, so start with td #2.
        $assert_session->elementTextContains('css', "$compatible_row td:nth-of-type(2)", "$module Update test");
        // Both contrib modules use 8.x-1.0 as the currently installed version.
        $assert_session->elementTextContains('css', "$compatible_row td:nth-of-type(3)", '8.x-1.0');
        $assert_session->elementTextContains('css', "$compatible_row td:nth-of-type(4)", $version);
      }
    }
    else {
      // Verify there is no compatible updates table.
      $assert_session->elementNotExists('css', $compatible_table_locator);
    }

    if ($incompatible) {
      // Verify the number of rows in the table.
      $assert_session->elementsCount('css', "$incompatible_table_locator tbody tr", count($incompatible));
      foreach ($incompatible as $module => $data) {
        $incompatible_row = "$incompatible_table_locator tbody tr:contains('$module Update test')";
        $assert_session->elementTextContains('css', "$incompatible_row td:nth-of-type(1)", "$module Update test");
        // Both contrib modules use 8.x-1.0 as the currently installed version.
        $assert_session->elementTextContains('css', "$incompatible_row td:nth-of-type(2)", '8.x-1.0');
        $assert_session->elementTextContains('css', "$incompatible_row td:nth-of-type(3)", $data['recommended']);
        $assert_session->elementTextContains('css', "$incompatible_row td:nth-of-type(3)", 'Requires Drupal core: ' . $data['range']);
      }
    }
    else {
      // Verify there is no incompatible updates table.
      $assert_session->elementNotExists('css', $incompatible_table_locator);
    }
  }

}
