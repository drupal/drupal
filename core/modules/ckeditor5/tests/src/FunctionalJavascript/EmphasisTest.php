<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Tests emphasis in CKEditor 5.
 *
 * CKEditor's use of <i> is converted to <em> in Drupal, so additional coverage
 * is provided here to verify successful conversion.
 *
 * @group ckeditor5
 * @internal
 */
class EmphasisTest extends WebDriverTestBase {
  use CKEditor5TestTrait;

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A host entity with a body field to use the <em> tag in.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $host;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'node',
    'text',
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

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <br> <em>',
          ],
        ],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'image_upload' => [
        'status' => FALSE,
      ],
      'settings' => [
        'toolbar' => [
          'items' => [
            'italic',
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [],
          ],
        ],
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolationInterface $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));
    $this->adminUser = $this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
    ]);

    $this->drupalCreateContentType(['type' => 'blog']);
    $this->host = $this->createNode([
      'type' => 'blog',
      'title' => 'Animals with strange names',
      'body' => [
        'value' => '<p>This is a <em>test!</em></p>',
        'format' => 'test_format',
      ],
    ]);
    $this->host->save();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Ensures that CKEditor italic model is converted to em.
   */
  public function testEmphasis(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();

    $emphasis_element = $assert_session->waitForElementVisible('css', '.ck-content p em');
    $this->assertEquals('test!', $emphasis_element->getText());

    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $emphasis_source = $xpath->query('//p/em');
    $this->assertNotEmpty($emphasis_source);
    $this->assertEquals('test!', $emphasis_source[0]->textContent);
    $page->pressButton('Save');

    $assert_session->responseContains('<p>This is a <em>test!</em></p>');
  }

  /**
   * Tests that arbitrary attributes are allowed via GHS.
   */
  public function testEmphasisArbitraryHtml(): void {
    $assert_session = $this->assertSession();
    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();

    // Allow the data-foo attribute in img via GHS.
    $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'] = ['<em data-foo>'];
    $editor->setSettings($settings);
    $editor->save();

    // Add data-foo use to an existing em tag.
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace('<em>', '<em data-foo="bar">', $original_value);
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();

    $emphasis_element = $assert_session->waitForElementVisible('css', '.ck-content p em');
    $this->assertEquals('bar', $emphasis_element->getAttribute('data-foo'));

    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query('//em[@data-foo="bar"]'));
  }

}
