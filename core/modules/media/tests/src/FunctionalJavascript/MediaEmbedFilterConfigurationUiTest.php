<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\filter\Entity\FilterFormat;

/**
 * @covers ::media_filter_format_edit_form_validate
 * @group media
 */
class MediaEmbedFilterConfigurationUiTest extends MediaJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   *
   * @todo Remove this class property in https://www.drupal.org/node/3091878/.
   */
  protected $failOnJavascriptConsoleErrors = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    // Necessary for @covers to work.
    require_once __DIR__ . '/../../../media.module';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $format = FilterFormat::create([
      'format' => 'media_embed_test',
      'name' => 'Test format',
      'filters' => [],
    ]);
    $format->save();

    $this->drupalLogin($this->drupalCreateUser([
      'administer filters',
      $format->getPermissionName(),
    ]));
  }

  /**
   * @covers ::media_form_filter_format_add_form_alter
   * @dataProvider providerTestValidations
   */
  public function testValidationWhenAdding($filter_html_status, $filter_align_status, $filter_caption_status, $filter_html_image_secure_status, $media_embed, $allowed_html, $expected_error_message) {
    $this->drupalGet('admin/config/content/formats/add');

    // Enable the `filter_html` and `media_embed` filters.
    $page = $this->getSession()->getPage();
    $page->fillField('name', 'Another test format');
    $this->showHiddenFields();
    $page->findField('format')->setValue('another_media_embed_test');
    if ($filter_html_status) {
      $page->checkField('filters[filter_html][status]');
    }
    if ($filter_align_status) {
      $page->checkField('filters[filter_align][status]');
    }
    if ($filter_caption_status) {
      $page->checkField('filters[filter_caption][status]');
    }
    if ($filter_html_image_secure_status) {
      $page->checkField('filters[filter_html_image_secure][status]');
    }
    if ($media_embed === TRUE || is_numeric($media_embed)) {
      $page->checkField('filters[media_embed][status]');
      // Set a non-default weight.
      if (is_numeric($media_embed)) {
        $this->click('.tabledrag-toggle-weight');
        $page->selectFieldOption('filters[media_embed][weight]', $media_embed);
      }
    }
    if (!empty($allowed_html)) {
      $page->clickLink('Limit allowed HTML tags and correct faulty HTML');
      $page->fillField('filters[filter_html][settings][allowed_html]', $allowed_html);
    }
    $page->pressButton('Save configuration');

    if ($expected_error_message) {
      $this->assertSession()->pageTextNotContains('Added text format Another test format.');
      $this->assertSession()->pageTextContains($expected_error_message);
    }
    else {
      $this->assertSession()->pageTextContains('Added text format Another test format.');
    }
  }

  /**
   * @covers ::media_form_filter_format_edit_form_alter
   * @dataProvider providerTestValidations
   */
  public function testValidationWhenEditing($filter_html_status, $filter_align_status, $filter_caption_status, $filter_html_image_secure_status, $media_embed, $allowed_html, $expected_error_message) {
    $this->drupalGet('admin/config/content/formats/manage/media_embed_test');

    // Enable the `filter_html` and `media_embed` filters.
    $page = $this->getSession()->getPage();
    if ($filter_html_status) {
      $page->checkField('filters[filter_html][status]');
    }
    if ($filter_align_status) {
      $page->checkField('filters[filter_align][status]');
    }
    if ($filter_caption_status) {
      $page->checkField('filters[filter_caption][status]');
    }
    if ($filter_html_image_secure_status) {
      $page->checkField('filters[filter_html_image_secure][status]');
    }
    if ($media_embed === TRUE || is_numeric($media_embed)) {
      $page->checkField('filters[media_embed][status]');
      // Set a non-default weight.
      if (is_numeric($media_embed)) {
        $this->click('.tabledrag-toggle-weight');
        $page->selectFieldOption('filters[media_embed][weight]', $media_embed);
      }
    }
    if (!empty($allowed_html)) {
      $page->clickLink('Limit allowed HTML tags and correct faulty HTML');
      $page->fillField('filters[filter_html][settings][allowed_html]', $allowed_html);
    }
    $page->pressButton('Save configuration');

    if ($expected_error_message) {
      $this->assertSession()->pageTextNotContains('The text format Test format has been updated.');
      $this->assertSession()->pageTextContains($expected_error_message);
    }
    else {
      $this->assertSession()->pageTextContains('The text format Test format has been updated.');
    }
  }

  /**
   * Data provider for testing validation when adding and editing media embeds.
   */
  public function providerTestValidations() {
    return [
      'Tests that no filter_html occurs when filter_html not enabled.' => [
        'filters[filter_html][status]' => FALSE,
        'filters[filter_align][status]' => FALSE,
        'filters[filter_caption][status]' => FALSE,
        'filters[filter_html_image_secure][status]' => FALSE,
        'media_embed' => TRUE,
        'allowed_html' => FALSE,
        'expected_error_message' => FALSE,
      ],
      'Tests validation when both filter_html and media_embed are disabled.' => [
        'filters[filter_html][status]' => FALSE,
        'filters[filter_align][status]' => FALSE,
        'filters[filter_caption][status]' => FALSE,
        'filters[filter_html_image_secure][status]' => FALSE,
        'media_embed' => FALSE,
        'allowed_html' => FALSE,
        'expected_error_message' => FALSE,
      ],
      'Tests validation when media_embed filter not enabled and filter_html is enabled.' => [
        'filters[filter_html][status]' => TRUE,
        'filters[filter_align][status]' => FALSE,
        'filters[filter_caption][status]' => FALSE,
        'filters[filter_html_image_secure][status]' => FALSE,
        'media_embed' => FALSE,
        'allowed_html' => 'default',
        'expected_error_message' => FALSE,
      ],
      'Tests validation when drupal-media element has no attributes.' => [
        'filters[filter_html][status]' => TRUE,
        'filters[filter_align][status]' => FALSE,
        'filters[filter_caption][status]' => FALSE,
        'filters[filter_html_image_secure][status]' => FALSE,
        'media_embed' => TRUE,
        'allowed_html' => "<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li> <dl> <dt> <dd> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id> <drupal-media>",
        'expected_error_message' => 'The <drupal-media> tag in the allowed HTML tags is missing the following attributes: data-entity-type, data-entity-uuid.',
      ],
      'Tests validation when drupal-media element lacks some required attributes.' => [
        'filters[filter_html][status]' => TRUE,
        'filters[filter_align][status]' => FALSE,
        'filters[filter_caption][status]' => FALSE,
        'filters[filter_html_image_secure][status]' => FALSE,
        'media_embed' => TRUE,
        'allowed_html' => "<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li> <dl> <dt> <dd> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id> <drupal-media data-entity-uuid data-align>",
        'expected_error_message' => 'The <drupal-media> tag in the allowed HTML tags is missing the following attributes: data-entity-type.',
      ],
      'Tests validation when both filter_html and media_embed are enabled and configured correctly' => [
        'filters[filter_html][status]' => TRUE,
        'filters[filter_align][status]' => FALSE,
        'filters[filter_caption][status]' => FALSE,
        'filters[filter_html_image_secure][status]' => FALSE,
        'media_embed' => TRUE,
        'allowed_html' => "<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li> <dl> <dt> <dd> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id> <drupal-media data-entity-type data-entity-uuid data-view-mode>",
        'expected_error_message' => FALSE,
      ],
      'Order validation: media_embed before all filters' => [
        'filters[filter_html][status]' => TRUE,
        'filters[filter_align][status]' => TRUE,
        'filters[filter_caption][status]' => TRUE,
        'filters[filter_html_image_secure][status]' => TRUE,
        'media_embed' => -5,
        'allowed_html' => "<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li> <dl> <dt> <dd> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id> <drupal-media data-entity-type data-entity-uuid data-view-mode>",
        'expected_error_message' => 'The Embed media filter needs to be placed after the following filters: Align images, Caption images, Restrict images to this site.',
      ],
      'Order validation: media_embed before filter_align' => [
        'filters[filter_html][status]' => FALSE,
        'filters[filter_align][status]' => TRUE,
        'filters[filter_caption][status]' => FALSE,
        'filters[filter_html_image_secure][status]' => FALSE,
        'media_embed' => -5,
        'allowed_html' => '',
        'expected_error_message' => 'The Embed media filter needs to be placed after the Align images filter.',
      ],
      'Order validation: media_embed before filter_caption' => [
        'filters[filter_html][status]' => FALSE,
        'filters[filter_align][status]' => FALSE,
        'filters[filter_caption][status]' => TRUE,
        'filters[filter_html_image_secure][status]' => FALSE,
        'media_embed' => -5,
        'allowed_html' => '',
        'expected_error_message' => 'The Embed media filter needs to be placed after the Caption images filter.',
      ],
      'Order validation: media_embed before filter_html_image_secure' => [
        'filters[filter_html][status]' => FALSE,
        'filters[filter_align][status]' => FALSE,
        'filters[filter_caption][status]' => FALSE,
        'filters[filter_html_image_secure][status]' => TRUE,
        'media_embed' => -5,
        'allowed_html' => '',
        'expected_error_message' => 'The Embed media filter needs to be placed after the Restrict images to this site filter.',
      ],
      'Order validation: media_embed after filter_align and filter_caption but before filter_html_image_secure' => [
        'filters[filter_html][status]' => TRUE,
        'filters[filter_align][status]' => TRUE,
        'filters[filter_caption][status]' => TRUE,
        'filters[filter_html_image_secure][status]' => TRUE,
        'media_embed' => 5,
        'allowed_html' => "<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li> <dl> <dt> <dd> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id> <drupal-media data-entity-type data-entity-uuid data-view-mode>",
        'expected_error_message' => 'The Embed media filter needs to be placed after the Restrict images to this site filter.',
      ],
    ];
  }

  /**
   * Show visually hidden fields.
   */
  protected function showHiddenFields() {
    $script = <<<JS
      var hidden_fields = document.querySelectorAll(".hidden");

      [].forEach.call(hidden_fields, function(el) {
        el.classList.remove("hidden");
      });
JS;

    $this->getSession()->executeScript($script);
  }

}
