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
 * For testing the table plugin.
 *
 * @group ckeditor5
 * @internal
 */
class TableTest extends WebDriverTestBase {

  use CKEditor5TestTrait;

  /**
   * A host entity with a body field to embed images in.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $host;

  /**
   * Text added to captions.
   *
   * @var string
   */
  protected $captionText = 'some caption';

  /**
   * Text added to table cells.
   *
   * @var string
   */
  protected $tableCellText = 'table cell';

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

    $this->drupalCreateContentType(['type' => 'page']);

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<br> <p> <table> <tr> <td rowspan colspan> <th rowspan colspan> <thead> <tbody> <tfoot> <caption>',
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
            'insertTable',
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

    // Create a sample host entity.
    $this->host = $this->createNode([
      'type' => 'page',
      'title' => 'Animals with strange names',
      'body' => [
        'value' => '<p>some content that will likely change</p>',
        'format' => 'test_format',
      ],
    ]);
    $this->host->save();

    $this->drupalLogin($this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
    ]));
  }

  /**
   * Confirms tables convert to the expected markup.
   */
  public function testTableConversion(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // This is CKEditor 5's default table markup, but uses elements that are
    // not allowed by the text format.
    $this->host->body->value = '<figure class="table"><table><tbody><tr><td>table cell</td></tr></tbody></table> <figcaption>some caption</figcaption></figure>';
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));

    $this->captionText = 'some caption';
    $this->tableCellText = 'table cell';

    $table_container = $assert_session->waitForElementVisible('css', 'figure.table');
    $this->assertNotNull($table_container);
    $caption = $page->find('css', 'figure.table > figcaption');
    $this->assertEquals($this->captionText, $caption->getText());
    $table = $page->find('css', 'figure.table > table');
    $this->assertEquals($this->tableCellText, $table->getText());

    $this->assertTableStructureInEditorData();
    $this->assertTableStructureInRenderedPage();
  }

  /**
   * Tests creating a table with caption in the UI.
   */
  public function testTableCaptionUi(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();

    // Add a table via the editor buttons.
    $table_button = $page->find('css', '.ck-dropdown button');
    $table_button->click();

    // Add a single table cell.
    $grid_button = $assert_session->waitForElementVisible('css', '.ck-insert-table-dropdown-grid-box[data-row="1"][data-column="1"]');
    $grid_button->click();

    // Confirm the table has been added and no caption is present.
    $this->assertNotNull($table_figure = $assert_session->waitForElementVisible('css', 'figure.table'));
    $assert_session->elementNotExists('css', 'figure.table > figcaption');

    // Enable captions and update caption content.
    $caption_button = $this->getBalloonButton('Toggle caption on');
    $caption_button->click();
    $caption = $assert_session->waitForElementVisible('css', 'figure.table > figcaption');
    $this->assertEmpty($caption->getText());
    $caption->setValue($this->captionText);
    $this->assertEquals($this->captionText, $caption->getText());

    // Update table cell content.
    $table_cell = $assert_session->waitForElement('css', '.ck-editor__nested-editable .ck-table-bogus-paragraph');
    $this->assertNotEmpty($table_cell);
    $table_cell->click();
    $table_cell->setValue($this->tableCellText);
    $table_cell = $page->find('css', 'figure.table > table > tbody > tr > td');
    $this->assertEquals($this->tableCellText, $table_cell->getText());

    $this->assertTableStructureInEditorData();

    // Disable caption, confirm the caption is no longer present.
    $table_figure->click();
    $caption_off_button = $this->getBalloonButton('Toggle caption off');
    $caption_off_button->click();
    $assert_session->assertNoElementAfterWait('css', 'figure.table > figcaption');

    // Re-enable caption and confirm the value was retained.
    $table_figure->click();
    $caption_on_button = $this->getBalloonButton('Toggle caption on');
    $caption_on_button->click();
    $caption = $assert_session->waitForElementVisible('css', 'figure.table > figcaption');
    $this->assertEquals($this->captionText, $caption->getText());

    $this->assertTableStructureInRenderedPage();
  }

  /**
   * Confirms the structure of the table within the editor data.
   */
  public function assertTableStructureInEditorData(): void {
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertEmpty($xpath->query('//figure'), 'There should be no figure tag in editor data');
    $this->assertNotEmpty($xpath->query('//table/caption'), 'A caption should be the immediate child of <table>');
    $this->assertEquals($this->captionText, (string) $xpath->query('//table/caption')[0]->nodeValue, "The caption should say {$this->captionText}");
    $this->assertNotEmpty($xpath->query('//table/tbody/tr/td'), 'There is an expected table structure.');
    $this->assertEquals($this->tableCellText, (string) $xpath->query('//table/tbody/tr/td')[0]->nodeValue, "The table cell should say {$this->tableCellText}");
  }

  /**
   * Confirms the saved page has the expected table structure.
   */
  public function assertTableStructureInRenderedPage(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $page->pressButton('Save');
    $assert_session->waitForText('has been updated');

    $assert_session->pageTextContains($this->tableCellText);
    $assert_session->pageTextContains($this->captionText);

    $assert_session->elementNotExists('css', 'figure');
    $this->assertNotNull($table_cell = $page->find('css', 'table > tbody > tr > td'), 'Table on rendered page has expected structure');
    $this->assertEquals($this->tableCellText, $table_cell->getText(), 'Table on rendered page has expected content');
    $this->assertNotNull($table_caption = $page->find('css', 'table > caption '), 'Table caption is in expected structure.');
    $this->assertEquals($this->captionText, $table_caption->getText(), 'Table caption has expected text');
  }

}
