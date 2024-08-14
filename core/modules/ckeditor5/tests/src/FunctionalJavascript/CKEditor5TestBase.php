<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Behat\Mink\Element\TraversableElement;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\user\RoleInterface;

// cspell:ignore esque

/**
 * Base class for testing CKEditor 5.
 *
 * @ingroup testing
 * @internal
 */
abstract class CKEditor5TestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ckeditor5',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page']);

    $this->drupalLogin($this->drupalCreateUser([
      'administer filters',
      'create page content',
      'edit own page content',
    ]));
  }

  /**
   * Add and save a new text format using CKEditor 5.
   */
  protected function addNewTextFormat($name = 'ckeditor5'): void {
    FilterFormat::create([
      'format' => $name,
      'roles' => [RoleInterface::AUTHENTICATED_ID],
      'name' => $name,
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<br> <p> <h2> <h3> <h4> <h5> <h6> <strong> <em>',
          ],
        ],
        'filter_align' => ['status' => TRUE],
        'filter_caption' => ['status' => TRUE],
      ],
    ])->save();

    Editor::create([
      'editor' => $name,
      'format' => $name,
      'image_upload' => [
        'status' => FALSE,
      ],
    ])->save();
  }

  /**
   * Create a new text format using CKEditor 5.
   */
  public function createNewTextFormat($page, $assert_session, $name = 'ckeditor5') {
    $this->drupalGet('admin/config/content/formats/add');
    $page->fillField('name', $name);
    $assert_session->waitForText('Machine name');
    $this->assertNotEmpty($assert_session->waitForText($name));
    $page->checkField('roles[authenticated]');

    if ($name === 'ckeditor5') {
      // Enable the HTML filter, at least one HTML restricting filter is needed
      // before CKEditor 5 can be enabled.
      $this->assertTrue($page->hasUncheckedField('filters[filter_html][status]'));
      $page->checkField('filters[filter_html][status]');

      // Add the tags that must be included in the html filter for CKEditor 5.
      $allowed_html_field = $assert_session->fieldExists('filters[filter_html][settings][allowed_html]');
      $allowed_html_field->setValue('<p> <br>');
    }
    $page->selectFieldOption('editor[editor]', $name);
    $assert_session->assertExpectedAjaxRequest(1);
  }

  /**
   * Save the new text format.
   */
  public function saveNewTextFormat($page, $assert_session) {
    $page->pressButton('Save configuration');
    $this->assertTrue($assert_session->waitForText('Added text format'), "Confirm new text format saved");
  }

  /**
   * Trigger a keyup event on the selected element.
   *
   * @param string $selector
   *   The css selector for the element.
   * @param string $key
   *   The keyCode.
   */
  protected function triggerKeyUp(string $selector, string $key) {

    $script = <<<JS
(function (selector, key) {
  const btn = document.querySelector(selector);
    btn.dispatchEvent(new KeyboardEvent('keydown', { key }));
    btn.dispatchEvent(new KeyboardEvent('keyup', { key }));
})('{$selector}', '{$key}')

JS;

    $options = [
      'script' => $script,
      'args' => [],
    ];

    $this->getSession()->getDriver()->getWebDriverSession()->execute($options);
  }

  /**
   * Decorates ::fieldValueEquals() to force DrupalCI to provide useful errors.
   *
   * @param string $field
   *   Field id|name|label|value.
   * @param string $value
   *   Field value.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   Document to check against.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *
   * @see \Behat\Mink\WebAssert::fieldValueEquals()
   */
  protected function assertHtmlEsqueFieldValueEquals($field, $value, ?TraversableElement $container = NULL) {
    $assert_session = $this->assertSession();

    $node = $assert_session->fieldExists($field, $container);
    $actual = $node->getValue();
    $regex = '/^' . preg_quote($value, '/') . '$/ui';

    $message = sprintf('The field "%s" value is "%s", but "%s" expected.', $field, htmlspecialchars($actual), htmlspecialchars($value));

    $assert_session->assert((bool) preg_match($regex, $actual), $message);
  }

  /**
   * Checks that no real-time validation errors are present.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function assertNoRealtimeValidationErrors(): void {
    $assert_session = $this->assertSession();
    $this->assertSame('', $assert_session->elementExists('css', '[data-drupal-selector="ckeditor5-realtime-validation-messages-container"]')->getHtml());
  }

}
