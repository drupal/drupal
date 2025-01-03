<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Ensures that the 'api_json' format is not supported by the REST module.
 *
 * @group jsonapi
 *
 * @internal
 */
class RestExportJsonApiUnsupportedTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_serializer_display_entity'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['jsonapi', 'rest_test_views', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['rest_test_views']): void {
    parent::setUp($import_test_views, $modules);

    $this->drupalLogin($this->drupalCreateUser(['administer views']));
  }

  /**
   * Tests that 'api_json' is not a RestExport format option.
   */
  public function testFormatOptions(): void {
    $this->assertSame(['json' => 'serialization', 'xml' => 'serialization'], $this->container->getParameter('serializer.format_providers'));

    $this->drupalGet('admin/structure/views/nojs/display/test_serializer_display_entity/rest_export_1/style_options');
    $this->assertSession()->fieldExists('style_options[formats][json]');
    $this->assertSession()->fieldExists('style_options[formats][xml]');
    $this->assertSession()->fieldNotExists('style_options[formats][api_json]');
  }

}
