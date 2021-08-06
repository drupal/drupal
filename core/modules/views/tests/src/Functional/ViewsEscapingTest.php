<?php

namespace Drupal\Tests\views\Functional;

/**
 * Tests output of Views.
 *
 * @group views
 */
class ViewsEscapingTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_page_display', 'test_field_header'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   *
   * We need theme_test for testing against test_basetheme and test_subtheme.
   *
   * @var array
   *
   * {@inheritdoc}
   */
  protected static $modules = ['views', 'theme_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(TRUE);

    $this->enableViewsTestModule();
  }

  /**
   * Tests for incorrectly escaped markup in the views-view-fields.html.twig.
   */
  public function testViewsViewFieldsEscaping() {
    // Test with system theme using theme function.
    $this->drupalGet('test_page_display_200');

    // Assert that there are no escaped '<'s characters.
    $this->assertSession()->assertNoEscaped('<');

    // Install theme to test with template system.
    \Drupal::service('theme_installer')->install(['views_test_theme']);

    // Make base theme default then test for hook invocations.
    $this->config('system.theme')
      ->set('default', 'views_test_theme')
      ->save();
    $this->assertEquals('views_test_theme', $this->config('system.theme')->get('default'));

    $this->drupalGet('test_page_display_200');

    // Assert that we are using the correct template.
    $this->assertSession()->pageTextContains('force');

    // Assert that there are no escaped '<'s characters.
    $this->assertSession()->assertNoEscaped('<');
  }

  /**
   * Tests for incorrectly escaped markup in a header label on a display table.
   */
  public function testViewsFieldHeaderEscaping() {
    // Test with a field header label having an html element wrapper.
    $this->drupalGet('test_field_header');

    // Assert that there are no escaped '<'s characters.
    $this->assertSession()->assertNoEscaped('<');

    // Test with a field header label having a XSS test as a wrapper.
    $this->drupalGet('test_field_header_xss');

    // Assert that harmful tags are escaped in header label.
    $this->assertSession()->responseNotContains('<script>alert("XSS")</script>');
  }

}
