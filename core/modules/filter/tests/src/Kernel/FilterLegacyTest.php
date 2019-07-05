<?php

namespace Drupal\Tests\filter\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Filter module's legacy code.
 *
 * @group filter
 * @group legacy
 */
class FilterLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter'];

  /**
   * Tests filter_form_access_denied() deprecation.
   *
   * @expectedDeprecation filter_form_access_denied() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Use \Drupal\filter\Element\TextFormat::accessDeniedCallback() instead. See https://www.drupal.org/node/2966725
   */
  public function testFilterFormAccessDenied() {
    $element = filter_form_access_denied([]);
    $this->assertEquals('This field has been disabled because you do not have sufficient permissions to edit it.', $element['#value']);
  }

}
