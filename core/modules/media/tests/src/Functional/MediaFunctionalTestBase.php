<?php

namespace Drupal\Tests\media\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for Media functional tests.
 */
abstract class MediaFunctionalTestBase extends BrowserTestBase {

  use MediaFunctionalTestTrait;
  use MediaFunctionalTestCreateMediaTypeTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'field_ui',
    'views_ui',
    'media',
    'media_test_source',
  ];

}
