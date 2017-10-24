<?php

namespace Drupal\Tests\rest\Kernel\Views;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;

/**
 * Tests the auth option of rest exports.
 *
 * @coversDefaultClass \Drupal\rest\Plugin\views\display\RestExport
 *
 * @group rest
 */
class RestExportAuthTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'rest',
    'views_ui',
    'basic_auth',
    'serialization',
    'rest',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['rest_export_with_authorization_correction'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installConfig(['user']);
  }

  /**
   * Ensures that rest export auth settings are automatically corrected.
   *
   * @see rest_update_8401()
   * @see rest_views_presave()
   * @see \Drupal\rest\Tests\Update\RestExportAuthCorrectionUpdateTest
   */
  public function testAuthCorrection() {
    // Get particular view.
    $view = \Drupal::entityTypeManager()
      ->getStorage('view')
      ->load('rest_export_with_authorization_correction');
    $displays = $view->get('display');
    $this->assertSame($displays['rest_export_1']['display_options']['auth'], ['cookie'], 'Cookie is used for authentication');
  }

}
