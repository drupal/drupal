<?php

namespace Drupal\Tests\views\Functional\Handler;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the placeholder text on the appropriate filter handlers.
 *
 * @group views
 */
class FilterPlaceholderTextTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_filter_placeholder_text'];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that HTML placeholders are added, when appropriate.
   */
  public function testPlaceholderText() {
    $this->drupalGet('placeholder-text-test');

    // String filter that has no placeholder configured.
    $results = $this->cssSelect('input[name=title]');
    $this->assertFalse($results[0]->hasAttribute('placeholder'));

    // String filter that has placeholder configured.
    $results = $this->cssSelect('input[name=title_with_placeholder]');
    $this->assertTrue($results[0]->hasAttribute('placeholder'));
    $this->assertEquals('title placeholder', $results[0]->getAttribute('placeholder'));

    // Numeric filter that has no placeholders configured.
    $results = $this->cssSelect('input[name="created[value]"]');
    $this->assertFalse($results[0]->hasAttribute('placeholder'));
    $results = $this->cssSelect('input[name="created[min]"]');
    $this->assertFalse($results[0]->hasAttribute('placeholder'));
    $results = $this->cssSelect('input[name="created[max]"]');
    $this->assertFalse($results[0]->hasAttribute('placeholder'));

    // Numeric filter that has all placeholders configured.
    $results = $this->cssSelect('input[name="created_with_placeholders[value]"]');
    $this->assertTrue($results[0]->hasAttribute('placeholder'));
    $this->assertEquals('created placeholder', $results[0]->getAttribute('placeholder'));
    $results = $this->cssSelect('input[name="created_with_placeholders[min]"]');
    $this->assertTrue($results[0]->hasAttribute('placeholder'));
    $this->assertEquals('min placeholder', $results[0]->getAttribute('placeholder'));
    $results = $this->cssSelect('input[name="created_with_placeholders[max]"]');
    $this->assertTrue($results[0]->hasAttribute('placeholder'));
    $this->assertEquals('max placeholder', $results[0]->getAttribute('placeholder'));
  }

}
