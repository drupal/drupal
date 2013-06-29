<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleUninstallFrenchTest.
 */

namespace Drupal\locale\Tests;

/**
 * Locale uninstall with French UI functional test.
 *
 * Because this class extends LocaleUninstallFunctionalTest, it doesn't require a new
 * test of its own. Rather, it switches the default UI language in setUp and then
 * runs the testUninstallProcess (which it inherits from LocaleUninstallFunctionalTest)
 * to test with this new language.
 */
class LocaleUninstallFrenchTest extends LocaleUninstallTest {
  public static function getInfo() {
    return array(
      'name' => 'Locale uninstall (FR)',
      'description' => 'Tests the uninstall process using French as interface language.',
      'group' => 'Locale',
    );
  }

  function setUp() {
    parent::setUp();
    $this->id = 'fr';
  }
}
