<?php

namespace Drupal\system\Tests\Form;

use Drupal\Component\Serialization\Json;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the form API Response element.
 *
 * @group Form
 */
class ResponseTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['form_test'];

  /**
   * Tests that enforced responses propagate through subscribers and middleware.
   */
  public function testFormResponse() {
    $edit = [
      'content' => $this->randomString(),
      'status' => 200,
    ];
    $content = Json::decode($this->drupalPostForm('form-test/response', $edit, 'Submit'));
    $this->assertResponse(200);
    $this->assertIdentical($edit['content'], $content, 'Response content matches');
    $this->assertIdentical('invoked', $this->drupalGetHeader('X-Form-Test-Response-Event'), 'Response handled by kernel response subscriber');
    $this->assertIdentical('invoked', $this->drupalGetHeader('X-Form-Test-Stack-Middleware'), 'Response handled by kernel middleware');

    $edit = [
      'content' => $this->randomString(),
      'status' => 418,
    ];
    $content = Json::decode($this->drupalPostForm('form-test/response', $edit, 'Submit'));
    $this->assertResponse(418);
    $this->assertIdentical($edit['content'], $content, 'Response content matches');
    $this->assertIdentical('invoked', $this->drupalGetHeader('X-Form-Test-Response-Event'), 'Response handled by kernel response subscriber');
    $this->assertIdentical('invoked', $this->drupalGetHeader('X-Form-Test-Stack-Middleware'), 'Response handled by kernel middleware');
  }

}
