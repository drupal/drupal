<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the form API Response element.
 *
 * @group Form
 */
class ResponseTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that enforced responses propagate through subscribers and middleware.
   */
  public function testFormResponse() {
    $edit = [
      'content' => $this->randomString(),
      'status' => 200,
    ];
    $this->drupalPostForm('form-test/response', $edit, 'Submit');
    $content = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertResponse(200);
    $this->assertIdentical($edit['content'], $content, 'Response content matches');
    $this->assertIdentical('invoked', $this->drupalGetHeader('X-Form-Test-Response-Event'), 'Response handled by kernel response subscriber');
    $this->assertIdentical('invoked', $this->drupalGetHeader('X-Form-Test-Stack-Middleware'), 'Response handled by kernel middleware');

    $edit = [
      'content' => $this->randomString(),
      'status' => 418,
    ];
    $this->drupalPostForm('form-test/response', $edit, 'Submit');
    $content = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertResponse(418);
    $this->assertIdentical($edit['content'], $content, 'Response content matches');
    $this->assertIdentical('invoked', $this->drupalGetHeader('X-Form-Test-Response-Event'), 'Response handled by kernel response subscriber');
    $this->assertIdentical('invoked', $this->drupalGetHeader('X-Form-Test-Stack-Middleware'), 'Response handled by kernel middleware');
  }

}
