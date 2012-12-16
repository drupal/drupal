<?php

/**
 * @file
 * Definition of Drupal\openid\Tests\Upgrade\OpenIDUpgradePathTest.
 */

namespace Drupal\openid\Tests\Upgrade;

use Drupal\system\Tests\Upgrade\UpgradePathTestBase;

/**
 * Performs major version release upgrade tests on a populated database.
 *
 * Loads an installation of Drupal 7.x and runs the upgrade process on it.
 *
 * The install contains the minimal profile (plus openid module) modules
 * with generated users in authmap so that the upgrade path can be tested.
 */
class OpenIDAuthmapUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name'  => 'OpenID upgrade path',
      'description'  => 'Identities migration from the authmap upgrade tests.',
      'group' => 'OpenID',
    );
  }

  public function setUp() {
    // Path to the database dump files.
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.minimal.database.php.gz',
      drupal_get_path('module', 'openid') . '/tests/upgrade/drupal-7.openid.database.php',
      drupal_get_path('module', 'openid') . '/tests/upgrade/drupal-7.openid.authmap.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests expected openid identities conversion after a successful upgrade.
   */
  public function testIdentitiesUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Verify that user identities was properly upgraded.
    $expected_identities = array(
      1 => (object) array(
        'aid' => 1,
        'uid' => 1,
        'identifier' => 'userA@providerA',
      ),
      2 => (object) array(
        'aid' => 2,
        'uid' => 1,
        'identifier' => 'userB@providerA',
      ));

    $db_identities = db_select('openid_identities', 'oi')
      ->fields('oi')
      ->execute()
      ->fetchAllAssoc('aid');

    foreach ($expected_identities as $aid => $expected_identity) {
      $this->assertEqual($expected_identity, $db_identities[$aid]);
    }

    $this->assertEqual(count($expected_identities), count($db_identities));
  }
}
