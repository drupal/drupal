<?php

/**
 * @file
 * Definition of Drupal\views\Tests\TokenReplaceTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests core view token replacement.
 */
class TokenReplaceTest extends ViewTestBase {

  public static function getInfo() {
    return array(
      'name' => 'View core token replacement',
      'description' => 'Checks view core token replacements.',
      'group' => 'Views',
    );
  }

  public function setUp() {
    parent::SetUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests core token replacements generated from a view.
   */
  function testTokenReplacement() {
    $view = views_get_view('test_tokens');
    $view->setDisplay('page_1');

    $expected = array(
      '[view:name]' => 'Test tokens',
      '[view:description]' => 'Test view to token replacement tests.',
      '[view:machine-name]' => 'test_tokens',
      '[view:title]' => 'Test token page',
      '[view:url]' => url('test_tokens', array('absolute' => TRUE)),
    );

    foreach ($expected as $token => $expected_output) {
      $output = token_replace($token, array('view' => $view));
      $this->assertIdentical($output, $expected_output, format_string('Token %token replaced correctly.', array('%token' => $token)));
    }
  }

}
