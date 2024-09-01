<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\RoleInterface;
use Symfony\Component\Validator\ConstraintViolation;

// cspell:ignore esque māori sourceediting splitbutton upcasted

/**
 * Tests for CKEditor 5.
 *
 * @group ckeditor5
 * @group #slow
 * @internal
 */
class CKEditor5MarkupTest extends CKEditor5TestBase {

  use TestFileCreationTrait;
  use CKEditor5TestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media_library',
    'language',
  ];

  /**
   * Ensures that attribute values are encoded.
   */
  public function testAttributeEncoding(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    FilterFormat::create([
      'format' => 'ckeditor5',
      'name' => 'CKEditor 5 with image upload',
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ])->save();
    Editor::create([
      'format' => 'ckeditor5',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => ['drupalInsertImage'],
        ],
        'plugins' => ['ckeditor5_imageResize' => ['allow_resize' => FALSE]],
      ],
      'image_upload' => [
        'status' => TRUE,
        'scheme' => 'public',
        'directory' => 'inline-images',
        'max_size' => NULL,
        'max_dimensions' => [
          'width' => NULL,
          'height' => NULL,
        ],
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('ckeditor5'),
        FilterFormat::load('ckeditor5')
      ))
    ));

    $this->drupalGet('node/add/page');
    $this->waitForEditor();
    $page->fillField('title[0][value]', 'My test content');

    // Ensure that CKEditor 5 is focused.
    $this->click('.ck-content');

    $this->assertNotEmpty($image_upload_field = $page->find('css', '.ck-file-dialog-button input[type="file"]'));
    $image = $this->getTestFiles('image')[0];
    $image_upload_field->attachFile($this->container->get('file_system')->realpath($image->uri));
    $assert_session->waitForElementVisible('css', '.ck-widget.image');

    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-balloon-panel .ck-text-alternative-form'));
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-text-alternative-form input[type=text]');
    $this->assertSame('', $alt_override_input->getValue());
    $alt_override_input->setValue('</em> Kittens & llamas are cute');
    $this->getBalloonButton('Save')->click();
    $page->pressButton('Save');

    $uploaded_image = File::load(1);
    $image_uuid = $uploaded_image->uuid();
    $image_url = $this->container->get('file_url_generator')->generateString($uploaded_image->getFileUri());
    $this->drupalGet('node/1');
    $this->assertNotEmpty($assert_session->waitForElement('xpath', sprintf('//img[@alt="</em> Kittens & llamas are cute" and @data-entity-uuid="%s" and @data-entity-type="file"]', $image_uuid)));

    // Drupal CKEditor 5 integrations overrides the CKEditor 5 HTML writer to
    // escape ampersand characters (&) and the angle brackets (< and >). This is
    // required because \Drupal\Component\Utility\Xss::filter fails to parse
    // element attributes with unescaped entities in value.
    // @see https://www.drupal.org/project/drupal/issues/3227831
    $this->assertEquals(sprintf('<img data-entity-uuid="%s" data-entity-type="file" src="%s" width="40" height="20" alt="&lt;/em&gt; Kittens &amp; llamas are cute">', $image_uuid, $image_url), Node::load(1)->get('body')->value);
  }

  /**
   * Ensures that CKEditor 5 retains filter_html's allowed global attributes.
   *
   * FilterHtml always forbids the `style` and `on*` attributes, and always
   * allows the `lang` attribute (with any value) and the `dir` attribute (with
   * either `ltr` or `rtl` as value). It's important that those last two
   * attributes are guaranteed to be retained.
   *
   * @see \Drupal\filter\Plugin\Filter\FilterHtml::getHTMLRestrictions()
   * @see ckeditor5_globalAttributeDir
   * @see ckeditor5_globalAttributeLang
   * @see https://html.spec.whatwg.org/multipage/dom.html#global-attributes
   */
  public function testFilterHtmlAllowedGlobalAttributes(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add a node with text rendered via the Plain Text format.
    $this->drupalGet('node/add/page');
    $page->fillField('title[0][value]', 'Multilingual Hello World');
    // cSpell:disable-next-line
    $page->fillField('body[0][value]', '<p dir="ltr" lang="en">Hello World</p><p dir="rtl" lang="ar">مرحبا بالعالم</p>');
    $page->pressButton('Save');

    $this->addNewTextFormat();

    $this->drupalGet('node/1/edit');
    $page->selectFieldOption('body[0][format]', 'ckeditor5');
    $this->assertNotEmpty($assert_session->waitForText('Change text format?'));
    $page->pressButton('Continue');

    $this->waitForEditor();
    $page->pressButton('Save');

    // cSpell:disable-next-line
    $assert_session->responseContains('<p dir="ltr" lang="en">Hello World</p><p dir="rtl" lang="ar">مرحبا بالعالم</p>');
  }

  /**
   * Ensures that HTML comments are preserved in CKEditor 5.
   */
  public function testComments(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add a node with text rendered via the Plain Text format.
    $this->drupalGet('node/add');
    $page->fillField('title[0][value]', 'My test content');
    $page->fillField('body[0][value]', '<!-- Hamsters, alpacas, llamas, and kittens are cute! --><p>This is a <em>test!</em></p>');
    $page->pressButton('Save');

    FilterFormat::create([
      'format' => 'ckeditor5',
      'name' => 'CKEditor 5 HTML comments test',
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ])->save();
    Editor::create([
      'format' => 'ckeditor5',
      'editor' => 'ckeditor5',
      'image_upload' => [
        'status' => FALSE,
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('ckeditor5'),
        FilterFormat::load('ckeditor5')
      ))
    ));

    $this->drupalGet('node/1/edit');
    $page->selectFieldOption('body[0][format]', 'ckeditor5');
    $this->assertNotEmpty($assert_session->waitForText('Change text format?'));
    $page->pressButton('Continue');

    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
    $page->pressButton('Save');

    $assert_session->responseContains('<!-- Hamsters, alpacas, llamas, and kittens are cute! --><p>This is a <em>test!</em></p>');
  }

  /**
   * Ensures that HTML scripts and styles are properly preserved in CKEditor 5.
   */
  public function testStylesAndScripts(): void {
    $test_cases = [
      // Test cases taken from the HTML documentation.
      // @see https://html.spec.whatwg.org/multipage/scripting.html#restrictions-for-contents-of-script-elements
      'script' => [
        '<script>(function() { let x = 10, y = 5; if( y <--x ) { console.log("run me!"); }})()</script>',
        '<script>(function() { let x = 10, y = 5; if( y <--x ) { console.log("run me!"); }})()</script>',
      ],
      'script like tag' => [
        '<script>(function() { let player = 5, script = 10; if (player<script) { console.log("run me!"); }})()</script>',
        '<script>(function() { let player = 5, script = 10; if (player<script) { console.log("run me!"); }})()</script>',
      ],
      'script to escape' => [
        "<script>const example = 'Consider this string: <!-- <script>';</script>",
        "<script>const example = 'Consider this string: <!-- <script>';</script>",
      ],
      'unescaped script tag' => [
        <<<HTML
        <script>
          const example = 'Consider this string: <!-- <script>';
          console.log(example);
        </script>
        <!-- despite appearances, this is actually part of the script still! -->
        <script>
          let a = 1 + 2; // this is the same script block still...
        </script>
        HTML,
        <<<HTML
        <script>
          const example = 'Consider this string: <!-- <script>';
          console.log(example);
        </script>
        <!-- despite appearances, this is actually part of the script still! -->
        <script>
          let a = 1 + 2; // this is the same script block still...
        </script>
        HTML,
      ],
      'style' => [
        <<<HTML
        <style>
        a > span {
          /* Important comment. */
          color: red !important;
        }
        </style>
        HTML,
        <<<HTML
        <style>
        a > span {
          /* Important comment. */
          color: red !important;
        }
        </style>
        HTML,
      ],
      'script and style' => [
        <<<HTML
        <script type="text/javascript">
        let x = 10;
        let y = 5;
        if(y < x){
        console.log('is smaller')
        }
        </script>
        <style type="text/css">
        :root {
          --main-bg-color: brown;
        }
        .sections > .section {
          background: var(--main-bg-color);
        }
        </style>
        HTML,
        <<<HTML
        <script type="text/javascript">
        let x = 10;
        let y = 5;
        if(y < x){
        console.log('is smaller')
        }
        </script><style type="text/css">
        :root {
          --main-bg-color: brown;
        }
        .sections > .section {
          background: var(--main-bg-color);
        }
        </style>
        HTML,
      ],
    ];

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Create filter.
    FilterFormat::create([
      'format' => 'ckeditor5',
      'name' => 'CKEditor 5 HTML',
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ])->save();
    Editor::create([
      'format' => 'ckeditor5',
      'editor' => 'ckeditor5',
      'image_upload' => [
        'status' => FALSE,
      ],
      'settings' => [
        'toolbar' => [
          'items' => [
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
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('ckeditor5'),
        FilterFormat::load('ckeditor5')
      ))
    ));

    // Add a node with text rendered via the CKEditor 5 HTML format.
    foreach ($test_cases as $test_case_name => $test_case) {
      [$markup, $expected_content] = $test_case;
      $this->drupalGet('node/add');
      $page->fillField('title[0][value]', "Style and script test - $test_case_name");
      $this->waitForEditor();
      $this->pressEditorButton('Source');
      $editor = $page->find('css', '.ck-source-editing-area textarea');
      $editor->setValue($markup);
      $page->pressButton('Save');

      $assert_session->responseContains($expected_content);
    }
  }

}
