<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\media\Entity\Media;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Symfony\Component\Validator\ConstraintViolation;

// cspell:ignore hurlant layercake tatou

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Media
 * @group ckeditor5
 * @group #slow
 * @internal
 */
class MediaTest extends MediaTestBase {

  /**
   * Tests that `<drupal-media>` is converted into a block element.
   */
  public function testConversion() {
    // Wrap the `<drupal-media>` markup in a `<p>`.
    $original_value = $this->host->body->value;
    $this->host->body->value = '<p>foo' . $original_value . '</p>';
    $this->host->save();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]', 1000);
    $editor_html = $this->getEditorDataAsHtmlString();
    // Observe that `<drupal-media>` was moved into its own block element.
    $this->assertEquals('<p>foo</p>' . $original_value, str_replace('&nbsp;', '', $editor_html));
  }

  /**
   * Tests that only <drupal-media> tags are processed.
   *
   * @see \Drupal\Tests\media\Kernel\MediaEmbedFilterTest::testOnlyDrupalMediaTagProcessed()
   */
  public function testOnlyDrupalMediaTagProcessed() {
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace('drupal-media', 'p', $original_value);
    $this->host->save();

    // Assert that `<p data-* …>` is not upcast into a CKEditor Widget.
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $this->assertEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]', 1000));
    $assert_session->elementNotExists('css', '.ck-widget.drupal-media');

    $this->host->body->value = $original_value;
    $this->host->save();

    // Assert that `<drupal-media data-* …>` is upcast into a CKEditor Widget.
    $this->getSession()->reload();
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]'));
    $assert_session->elementExists('css', '.ck-widget.drupal-media');
  }

  /**
   * Tests adding media to a list does not split the list.
   */
  public function testMediaSplitList() {
    $assert_session = $this->assertSession();

    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();

    // Add lists to the editor.
    $settings['plugins']['ckeditor5_list'] = [
      'properties' => [
        'reversed' => FALSE,
        'startIndex' => FALSE,
      ],
      'multiBlock' => TRUE,
    ];
    $settings['toolbar']['items'] = array_merge($settings['toolbar']['items'], ['bulletedList', 'numberedList']);
    $editor->setSettings($settings);
    $editor->save();

    // Add lists to the filter.
    $filter_format = $editor->getFilterFormat();
    $filter_format->setFilterConfig('filter_html', [
      'status' => TRUE,
      'settings' => [
        'allowed_html' => '<p> <br> <strong> <em> <a href> <drupal-media data-entity-type data-entity-uuid data-align data-caption alt data-view-mode> <ol> <ul> <li>',
      ],
    ]);
    $filter_format->save();

    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    // Wrap the media with a list item.
    $original_value = $this->host->body->value;
    $this->host->body->value = '<ol><li>' . $original_value . '</li></ol>';
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));

    $this->assertNotEmpty($upcasted_media = $assert_session->waitForElementVisible('css', '.ck-widget.drupal-media'));

    // Confirm the media is wrapped by the list item on the editing view.
    $assert_session->elementExists('css', 'li > .drupal-media');
    // Confirm the media is not adjacent to the list on the editing view.
    $assert_session->elementNotExists('css', 'ol + .drupal-media');

    $editor_dom = new \DOMXPath($this->getEditorDataAsDom());
    // Confirm drupal-media is wrapped by the list item.
    $this->assertNotEmpty($editor_dom->query('//li/drupal-media'));
    // Confirm the media is not adjacent to the list.
    $this->assertEmpty($editor_dom->query('//ol/following-sibling::drupal-media'));
  }

  /**
   * Tests that arbitrary attributes are allowed via GHS.
   */
  public function testMediaArbitraryHtml() {
    $assert_session = $this->assertSession();

    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();

    // Allow the data-foo attribute in drupal-media via GHS. Also, add support
    // for div's with data-foo attribute to ensure that drupal-media elements
    // can be wrapped with other block elements.
    $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'] = ['<drupal-media data-foo>', '<div data-bar>'];
    $editor->setSettings($settings);
    $editor->save();

    $filter_format = $editor->getFilterFormat();
    $filter_format->setFilterConfig('filter_html', [
      'status' => TRUE,
      'settings' => [
        'allowed_html' => '<p> <br> <strong> <em> <a href> <drupal-media data-entity-type data-entity-uuid data-align data-caption alt data-foo data-view-mode> <div data-bar>',
      ],
    ]);
    $filter_format->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    // Add data-foo use to an existing drupal-media tag.
    $original_value = $this->host->body->value;
    $this->host->body->value = '<div data-bar="baz">' . str_replace('drupal-media', 'drupal-media data-foo="bar" ', $original_value) . '</div>';
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));

    // Confirm data-foo is present in the drupal-media preview.
    $this->assertNotEmpty($upcasted_media = $assert_session->waitForElementVisible('css', '.ck-widget.drupal-media'));
    $this->assertFalse($upcasted_media->hasAttribute('data-foo'));
    $this->assertNotEmpty($preview = $assert_session->waitForElementVisible('css', '.ck-widget.drupal-media > [data-drupal-media-preview="ready"] > .media', 30000));
    $this->assertEquals('bar', $preview->getAttribute('data-foo'));

    // Confirm that the media is wrapped by the div on the editing view.
    $assert_session->elementExists('css', 'div[data-bar="baz"] > .drupal-media');

    // Confirm data-foo is not stripped from source.
    $this->assertSourceAttributeSame('data-foo', 'bar');

    // Confirm that drupal-media is wrapped by the div.
    $editor_dom = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($editor_dom->query('//div[@data-bar="baz"]/drupal-media'));
  }

  /**
   * Tests caption editing in the CKEditor widget.
   */
  public function testEditableCaption() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    // Test that setting caption to blank string doesn't break 'Edit media'
    // button.
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace('data-caption="baz"', 'data-caption=""', $original_value);
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    $assert_session->elementExists('css', '[data-drupal-media-preview][aria-label="Screaming hairy armadillo"]');
    $assert_session->elementContains('css', 'figcaption', '');
    $assert_session->elementAttributeContains('css', 'figcaption', 'data-placeholder', 'Enter media caption');

    // Test if you leave the caption blank, but change another attribute,
    // such as the alt text, the editable caption is still there and the edit
    // button still exists.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Click the "Override media image alternative text" button.
    $this->getBalloonButton('Override media image alternative text')->click();
    $this->assertVisibleBalloon('.ck-media-alternative-text-form');
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-media-alternative-text-form input[type=text]');

    // Fill in the alt field and submit.
    $alt_override_input->setValue('Gold star for robot boy.');
    $this->getBalloonButton('Save')->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.drupal-media img[alt*="Gold star for robot boy."]'));
    $this->assertEquals('', $assert_session->waitForElement('css', '.drupal-media figcaption')->getText());
    $assert_session->elementAttributeContains('css', '.drupal-media figcaption', 'data-placeholder', 'Enter media caption');

    // Restore caption in saved body value.
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace('data-caption=""', 'data-caption="baz"', $original_value);
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    $this->assertNotEmpty($figcaption = $assert_session->waitForElement('css', '.drupal-media figcaption'));
    $this->assertSame('baz', $figcaption->getHtml());

    // Ensure that the media contextual toolbar is visible when figcaption is
    // selected.
    $this->selectTextInsideElement('.drupal-media figcaption');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $expected_buttons = [
      'Toggle caption off',
      'Link media',
      'Override media image alternative text',
      // Check only one of the element style buttons since that is sufficient
      // for confirming that element style buttons are visible in the toolbar.
      'Break text',
    ];
    foreach ($expected_buttons as $expected_button) {
      $this->assertNotEmpty($this->getBalloonButton($expected_button));
    }

    // Ensure that caption can be toggled off from the toolbar.
    $this->getBalloonButton('Toggle caption off')->click();
    $assert_session->assertNoElementAfterWait('css', 'figcaption');

    // Ensure that caption can be toggled on from the toolbar.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('Toggle caption on')->click();
    $this->assertNotEmpty($figcaption = $assert_session->waitForElementVisible('css', '.drupal-media figcaption'));

    // Ensure that the media contextual toolbar is visible after toggling
    // caption on.
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');

    // Type into the widget's caption element.
    $this->selectTextInsideElement('.drupal-media figcaption');
    $figcaption->setValue('Llamas are the most awesome ever');
    $editor_dom = $this->getEditorDataAsDom();
    $this->assertEquals('Llamas are the most awesome ever', $editor_dom->getElementsByTagName('drupal-media')->item(0)->getAttribute('data-caption'));

    // Ensure that the caption can be changed to bold.
    $this->assertNotEmpty($figcaption = $assert_session->waitForElement('css', '.drupal-media figcaption'));
    $this->selectTextInsideElement('.drupal-media figcaption');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.drupal-media figcaption.ck-editor__nested-editable'));
    $this->pressEditorButton('Bold');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.drupal-media figcaption > strong'));
    $this->assertEquals('<strong>Llamas are the most awesome ever</strong>', $figcaption->getHtml());
    $editor_dom = $this->getEditorDataAsDom();
    $this->assertEquals('<strong>Llamas are the most awesome ever</strong>', $editor_dom->getElementsByTagName('drupal-media')->item(0)->getAttribute('data-caption'));

    // Ensure that bold can be removed from the caption.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.drupal-media figcaption > strong'));
    $this->selectTextInsideElement('.drupal-media figcaption > strong');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.drupal-media figcaption.ck-editor__nested-editable'));
    $this->pressEditorButton('Bold');
    $this->assertTrue($assert_session->waitForElementRemoved('css', '.drupal-media figcaption > strong'));
    $this->assertNotEmpty($figcaption = $assert_session->waitForElement('css', '.drupal-media figcaption'));
    $this->assertEquals('Llamas are the most awesome ever', $figcaption->getHtml());
    $editor_dom = $this->getEditorDataAsDom();
    $this->assertEquals('Llamas are the most awesome ever', $editor_dom->getElementsByTagName('drupal-media')->item(0)->getAttribute('data-caption'));

    // Ensure that caption can contain elements such as <br>.
    $this->pressEditorButton('Source');
    $source_text_area = $assert_session->waitForElement('css', '.ck-source-editing-area textarea');
    $source_text = $source_text_area->getValue();
    $source_text_area->setValue(str_replace('data-caption="Llamas are the most awesome ever"', 'data-caption="Llamas are the most<br>awesome ever"', $source_text));
    // Click source again to make source inactive.
    $this->pressEditorButton('Source');
    // Check that the source mode is toggled off.
    $assert_session->elementNotExists('css', '.ck-source-editing-area textarea');
    // Put back the caption as it was before.
    $this->pressEditorButton('Source');
    $source_text_area = $assert_session->waitForElement('css', '.ck-source-editing-area textarea');
    $source_text = $source_text_area->getValue();
    $source_text_area->setValue(str_replace('data-caption="Llamas are the most&lt;br&gt;awesome ever"', 'data-caption="Llamas are the most awesome ever"', $source_text));
    // Click source again to make source inactive.
    $this->pressEditorButton('Source');
    // Check that the source mode is toggled off.
    $assert_session->elementNotExists('css', '.ck-source-editing-area textarea');

    // Ensure that caption can be linked.
    $this->assertNotEmpty($figcaption = $assert_session->waitForElement('css', '.drupal-media figcaption'));
    $figcaption->click();
    $this->selectTextInsideElement('.drupal-media figcaption');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.drupal-media figcaption.ck-editor__nested-editable'));
    $this->pressEditorButton('Link');
    $this->assertVisibleBalloon('.ck-link-form');
    $link_input = $page->find('css', '.ck-balloon-panel .ck-link-form input[type=text]');
    $link_input->setValue('https://example.com');
    $page->find('css', '.ck-balloon-panel .ck-link-form button[type=submit]')->click();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.drupal-media figcaption > a'));
    $this->assertEquals('<a class="ck-link_selected" href="https://example.com">Llamas are the most awesome ever</a>', $figcaption->getHtml());
    $editor_dom = $this->getEditorDataAsDom();
    $this->assertEquals('<a href="https://example.com">Llamas are the most awesome ever</a>', $editor_dom->getElementsByTagName('drupal-media')->item(0)->getAttribute('data-caption'));
  }

  /**
   * Tests that the image media source's alt_field being disabled is respected.
   *
   * @see \Drupal\Tests\ckeditor5\Functional\MediaEntityMetadataApiTest::testApi()
   */
  public function testAltDisabled(): void {
    // Disable the alt field for image media.
    FieldConfig::loadByName('media', 'image', 'field_media_image')
      ->setSetting('alt_field', FALSE)
      ->save();

    $assert_session = $this->assertSession();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    // Test that by default no alt attribute is present on the drupal-media
    // element.
    $this->assertSourceAttributeSame('alt', NULL);
    // Test that the preview shows the alt value from the media field's
    // alt text.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img[alt*="default alt"]'));
    // Test that clicking the media widget triggers a CKEditor balloon panel
    // with a single button to override the alt text.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Assert that no "Override media image alternative text" button is visible.
    $override_alt_button = $this->getBalloonButton('Override media image alternative text');
    $this->assertFalse($override_alt_button->isVisible());
  }

  /**
   * Tests the CKEditor 5 media plugin can override image media's alt attribute.
   */
  public function testAlt() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    // Test that by default no alt attribute is present on the drupal-media
    // element.
    $this->assertSourceAttributeSame('alt', NULL);
    // Test that the preview shows the alt value from the media field's
    // alt text.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img[alt*="default alt"]'));
    // Test that clicking the media widget triggers a CKEditor balloon panel
    // with a single button to override the alt text.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Click the "Override media image alternative text" button.
    $this->getBalloonButton('Override media image alternative text')->click();
    $this->assertVisibleBalloon('.ck-media-alternative-text-form');
    // Assert that the default alt text is visible in the UI.
    $assert_session->elementTextEquals('css', '.ck-media-alternative-text-form__default-alt-text-value', 'default alt');
    // Assert that the value is currently empty.
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-media-alternative-text-form input[type=text]');
    $this->assertSame('', $alt_override_input->getValue());

    // Fill in the alt field and submit.
    // cSpell:disable-next-line
    $who_is_zartan = 'Zartan is the leader of the Dreadnoks.';
    $alt_override_input->setValue($who_is_zartan);
    $this->getBalloonButton('Save')->click();

    // Assert that the img within the media embed within the CKEditor contains
    // the overridden alt text set in the dialog.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img[alt*="' . $who_is_zartan . '"]'));
    // Ensure that the Drupal Media widget doesn't have alt attribute.
    // @see https://www.drupal.org/project/drupal/issues/3248440
    $assert_session->elementNotExists('css', '.ck-widget.drupal-media[alt]');
    // Test `aria-label` attribute appears on the widget wrapper.
    $assert_session->elementExists('css', '.ck-widget.drupal-media [aria-label="Screaming hairy armadillo"]');

    // Test that the downcast drupal-media element now has the alt attribute
    // entered in the balloon.
    $this->assertSourceAttributeSame('alt', $who_is_zartan);

    // The alt field should now display the override instead of the default.
    $this->getBalloonButton('Override media image alternative text')->click();
    $this->assertVisibleBalloon('.ck-media-alternative-text-form');
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-media-alternative-text-form input[type=text]');
    $this->assertSame($who_is_zartan, $alt_override_input->getValue());
    // Assert that the default alt text is still visible in the UI.
    $assert_session->elementTextEquals('css', '.ck-media-alternative-text-form__default-alt-text-value', 'default alt');

    // Test the process again with a different alt text to make sure it works
    // the second time around.
    $cobra_commander_bio = 'The supreme leader of the terrorist organization Cobra';
    // Set the alt field to the new alt text.
    $alt_override_input->setValue($cobra_commander_bio);
    $this->getBalloonButton('Save')->click();
    // Assert that the img within the media embed preview inside CKEditor 5
    // contains the overridden alt text set in the balloon.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img[alt*="' . $cobra_commander_bio . '"]'));

    // Test that the downcast drupal-media element now has the alt attribute
    // entered in the dialog.
    $this->assertSourceAttributeSame('alt', $cobra_commander_bio);

    // The default value of the alt field should now display the override
    // instead of the value on the media image field.
    $this->getBalloonButton('Override media image alternative text')->click();
    $this->assertVisibleBalloon('.ck-media-alternative-text-form');
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-media-alternative-text-form input[type=text]');
    $this->assertSame($cobra_commander_bio, $alt_override_input->getValue());

    // Test that setting alt value to two double quotes will signal to the
    // MediaEmbed filter to unset the attribute on the media image field.
    // We intentionally add a space after the two double quotes to test that the
    // string is trimmed to two quotes.
    $alt_override_input->setValue('"" ');
    $this->getBalloonButton('Save')->click();
    // Verify that the two double quote empty alt indicator ('""') set in
    // the alt text form balloon has successfully resulted in a media image
    // field with the alt attribute present but without a value.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '[data-media-embed-test-view-mode] img[alt=""]'));

    // Test that the downcast drupal-media element's alt attribute now has the
    // empty string indicator.
    $this->assertSourceAttributeSame('alt', '""');

    // Test that setting alt to back to an empty string within the balloon will
    // restore the default alt value saved in to the media image field of the
    // media item.
    $this->getBalloonButton('Override media image alternative text')->click();
    $this->assertVisibleBalloon('.ck-media-alternative-text-form');
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-media-alternative-text-form input[type=text]');
    $alt_override_input->setValue('');
    $this->getBalloonButton('Save')->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img[alt*="default alt"]'));

    // Test that the downcast drupal-media element no longer has an alt
    // attribute.
    $this->assertSourceAttributeSame('alt', NULL);
  }

  /**
   * Tests the CKEditor 5 media plugin loads the translated alt attribute.
   */
  public function testTranslationAlt() {
    \Drupal::service('module_installer')->install(['language', 'content_translation']);
    $this->resetAll();
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ContentLanguageSettings::loadByEntityTypeBundle('media', 'image')
      ->setDefaultLangcode('en')
      ->setLanguageAlterable(TRUE)
      ->save();
    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Screaming hairy armadillo',
      'field_media_image' => [
        [
          'target_id' => 1,
          'alt' => 'default alt',
          'title' => 'default title',
        ],
      ],
    ]);
    $media->save();
    $media_fr = $media->addTranslation('fr');
    $media_fr->name = "Tatou poilu hurlant";
    $media_fr->field_media_image->setValue([
      [
        'target_id' => '1',
        'alt' => "texte alternatif par dÃ©faut",
        'title' => "titre alternatif par dÃ©faut",
      ],
    ]);
    $media_fr->save();

    ContentLanguageSettings::loadByEntityTypeBundle('node', 'blog')
      ->setDefaultLangcode('en')
      ->setLanguageAlterable(TRUE)
      ->save();

    $host = $this->createNode([
      'type' => 'blog',
      'title' => 'Animals with strange names',
      'body' => [
        'value' => '<drupal-media data-caption="baz" data-entity-type="media" data-entity-uuid="' . $media->uuid() . '"></drupal-media>',
        'format' => 'test_format',
      ],
    ]);
    $host->save();

    $translation = $host->addTranslation('fr');
    // cSpell:disable-next-line
    $translation->title = 'Animaux avec des noms Ã©tranges';
    $translation->body->value = $host->body->value;
    $translation->body->format = $host->body->format;
    $translation->save();

    Role::load(RoleInterface::AUTHENTICATED_ID)
      ->grantPermission('translate any entity')
      ->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet('/fr/node/' . $host->id() . '/edit');
    $this->waitForEditor();

    // Test that the default alt attribute displays without an override.
    // cSpell:disable-next-line
    $this->assertNotEmpty($assert_session->waitForElementVisible('xpath', '//img[contains(@alt, "texte alternatif par dÃ©faut")]'));
    // Test `aria-label` attribute appears on the preview wrapper.
    // cSpell:disable-next-line
    $assert_session->elementExists('css', '[data-drupal-media-preview][aria-label="Tatou poilu hurlant"]');
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Click the "Override media image alternative text" button.
    $this->getBalloonButton('Override media image alternative text')->click();
    $this->assertVisibleBalloon('.ck-media-alternative-text-form');
    // Assert that the default alt on the UI is the default alt text from the
    // media entity.
    // cSpell:disable-next-line
    $assert_session->elementTextEquals('css', '.ck-media-alternative-text-form__default-alt-text-value', 'texte alternatif par dÃ©faut');

    // Fill in the alt field in the balloon form.
    // cSpell:disable-next-line
    $qui_est_zartan = 'Zartan est le chef des Dreadnoks.';
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-media-alternative-text-form input[type=text]');
    $alt_override_input->setValue($qui_est_zartan);
    $this->getBalloonButton('Save')->click();

    // Assert that the img within the media embed within CKEditor 5 contains
    // the overridden alt text set in CKEditor 5.
    $this->assertNotEmpty($assert_session->waitForElementVisible('xpath', '//img[contains(@alt, "' . $qui_est_zartan . '")]'));
    $this->getSession()->switchToIFrame();
    $page->pressButton('Save');
    $assert_session->elementExists('xpath', '//img[contains(@alt, "' . $qui_est_zartan . '")]');
  }

  /**
   * Tests alignment integration.
   *
   * Tests that alignment is reflected onto the CKEditor Widget wrapper, that
   * the media style toolbar allows altering the alignment and that the changes
   * are reflected on the widget and downcast drupal-media tag.
   */
  public function testAlignment() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));

    // Ensure that by default the "Break text" alignment option is selected.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->assertTrue(($align_button = $this->getBalloonButton('Break text'))->hasClass('ck-on'));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertFalse($drupal_media_element->hasAttribute('data-align'));
    $this->getBalloonButton('Align center and break text')->click();

    // Assert the alignment class exists after editing downcast.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-widget.drupal-media.drupal-media-style-align-center'));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertEquals('center', $drupal_media_element->getAttribute('data-align'));

    $page->pressButton('Save');
    // Check that the 'content has been updated' message status appears to confirm we left the editor.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.messages.messages--status'));
    // Check that the class is correct in the front end.
    $assert_session->elementExists('css', 'figure.align-center');
    // Go back to the editor to check that the alignment class still exists.
    $edit_url = $this->getSession()->getCurrentURL() . '/edit';
    $this->drupalGet($edit_url);
    $this->waitForEditor();
    $assert_session->elementExists('css', '.ck-widget.drupal-media.drupal-media-style-align-center');

    // Ensure that "Centered media" alignment option is selected.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->assertTrue($this->getBalloonButton('Align center and break text')->hasClass('ck-on'));
    $this->getBalloonButton('Break text')->click();
    $this->assertTrue($assert_session->waitForElementRemoved('css', '.ck-widget.drupal-media.drupal-media-style-align-center'));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertFalse($drupal_media_element->hasAttribute('data-align'));
  }

  /**
   * Ensures that Drupal Media Styles can be displayed in a dropdown.
   */
  public function testDrupalMediaStyleInDropdown() {
    \Drupal::service('module_installer')->install(['ckeditor5_drupalelementstyle_test']);
    $this->resetAll();

    $assert_session = $this->assertSession();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));

    // Ensure that by default the "Break text" alignment option is selected and
    // that the split button title is displayed.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->assertNotEmpty(($split_button = $this->getBalloonButton('Test title: Break text'))->hasClass('ck-on'));

    // Ensure that the split button can be opened.
    $split_button->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-dropdown__panel-visible'));

    // Ensure that a button inside the split button can be clicked.
    $this->assertNotEmpty($align_button = $this->getBalloonButton('Align center and break text'));
    $align_button->click();

    // Ensure that the "Align center and break text" option is selected and the
    // split button title is displayed.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-widget.drupal-media.drupal-media-style-align-center'));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertEquals('center', $drupal_media_element->getAttribute('data-align'));
    $this->assertNotEmpty(($this->getBalloonButton('Test title: Align center and break text'))->hasClass('ck-on'));
  }

  /**
   * Tests Drupal Media Style with a CSS class.
   */
  public function testDrupalMediaStyleWithClass() {
    $editor = Editor::load('test_format');
    $editor->setSettings([
      'toolbar' => [
        'items' => [
          'heading',
          'sourceEditing',
          'simpleBox',
        ],
      ],
      'plugins' => [
        'ckeditor5_heading' => [
          'enabled_headings' => [
            'heading1',
          ],
        ],
        'ckeditor5_sourceEditing' => [
          'allowed_tags' => [
            '<div>',
            '<section>',
          ],
        ],
        'media_media' => [
          'allow_view_mode_override' => TRUE,
        ],
      ],
    ]);
    $filter_format = $editor->getFilterFormat();
    $filter_format->setFilterConfig('filter_html', [
      'status' => TRUE,
      'settings' => [
        'allowed_html' => '<p> <br> <h1 class> <div class> <section class> <drupal-media data-entity-type data-entity-uuid data-align data-caption data-view-mode alt class="layercake-side">',
      ],
    ]);
    $filter_format->save();
    $editor->save();

    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();

    $page->pressButton('Source');
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')->item(0);

    // Add `layercake-side` class which is used in `ckeditor5_test_layercake`,
    // as well as an arbitrary class to compare behavior between these.
    $drupal_media_element->setAttribute('class', 'layercake-side arbitrary-class');
    $textarea = $page->find('css', '.ck-source-editing-area > textarea');
    $textarea->setValue($editor_dom->C14N());
    $page->pressButton('Source');

    // Ensure that the `layercake-side` class is retained.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-widget.drupal-media.layercake-side'));

    // Ensure that the `arbitrary-class` class is removed.
    $assert_session->elementNotExists('css', '.ck-widget.drupal-media.arbitrary-class');
    $page->pressButton('Save');

    // Check that the 'content has been updated' message status appears to confirm we left the editor.
    $assert_session->waitForElementVisible('css', 'messages messages--status');

    // Ensure that the class is correct in the front end.
    $assert_session->elementExists('css', 'figure.layercake-side');
    $assert_session->elementNotExists('css', 'figure.arbitrary-class');
  }

  /**
   * Tests view mode integration.
   *
   * Tests that view mode is reflected onto the CKEditor 5 Widget wrapper, that
   * the media style toolbar allows changing the view mode and that the changes
   * are reflected on the widget and downcast drupal-media tag.
   *
   * @dataProvider providerTestViewMode
   */
  public function testViewMode(bool $with_alignment) {
    EntityViewMode::create([
      'id' => 'media.view_mode_3',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 3',
    ])->save();
    EntityViewMode::create([
      'id' => 'media.view_mode_4',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 4',
    ])->save();
    // Enable view mode 1, 2, 4 for Image.
    EntityViewDisplay::create([
      'id' => 'media.image.view_mode_4',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'bundle' => 'image',
      'mode' => 'view_mode_4',
    ])->save();

    $filter_format = FilterFormat::load('test_format');
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          'view_mode_1' => 'view_mode_1',
          '22222' => '22222',
          'view_mode_3' => 'view_mode_3',
        ],
      ],
    ])->save();

    if (!$with_alignment) {
      $filter_format->filters('filter_align')->setConfiguration(array_merge($filter_format->filters('filter_align')->getConfiguration(), ['status' => FALSE]));
    }

    // Test that view mode dependencies are returned from the MediaEmbed
    // filter's ::getDependencies() method.
    $expected_config_dependencies = [
      'core.entity_view_mode.media.view_mode_1',
      'core.entity_view_mode.media.22222',
      'core.entity_view_mode.media.view_mode_3',
    ];

    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertEqualsCanonicalizing($expected_config_dependencies, $dependencies['config']);
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Check that there is no data-view-mode set after embedding media.
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertFalse($drupal_media_element->hasAttribute('data-view-mode'));
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('View Mode 1')->click();

    // Set view mode.
    $this->getBalloonButton('View Mode 2 has Numeric ID')->click();
    $editor_dom = $this->getEditorDataAsDom();
    // Check that “data-view-mode” exists inside source editing.
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertEquals('22222', $drupal_media_element->getAttribute('data-view-mode'));

    // Check that toolbar matches current view mode.
    $dropdown_button = $page->find('css', 'button.ck-dropdown__button > span.ck-button__label');
    $this->assertEquals('View Mode 2 has Numeric ID', $dropdown_button->getText());
    // Enter source mode.
    $this->pressEditorButton('Source');
    // Leave source mode to force CKEditor 5 to upcast again to check data
    // persistence.
    $this->pressEditorButton('Source');
    $this->click('.ck-widget.drupal-media');
    $dropdown_button = $page->find('css', 'button.ck-dropdown__button > span.ck-button__label');
    // Check that view mode 2 persisted.
    $this->assertEquals('View Mode 2 has Numeric ID', $dropdown_button->getText());

    // Check that selecting a caption that is the child of a drupal-media will
    // inherit the drupalElementStyle of its parent element.
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('Toggle caption off')->click();
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Select the caption by toggling it on.
    $this->getBalloonButton('Toggle caption on')->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.drupal-media figcaption'));
    // Ensure that the media contextual toolbar is visible after toggling
    // caption on.
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $dropdown_button = $page->find('css', 'button.ck-dropdown__button > span.ck-button__label');
    $this->assertEquals('View Mode 2 has Numeric ID', $dropdown_button->getText());

    // Remove the current view mode by setting it to Default.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('View Mode 2 has Numeric ID')->click();
    // Unset view mode.
    $this->getBalloonButton('View Mode 1')->click();
    $this->waitForEditor();
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    // Test that setting the view mode back to the default removes the
    // `data-view-mode` attribute.
    $this->assertFalse($drupal_media_element->hasAttribute('data-view-mode'));
    $assert_session->elementExists('css', 'article.media--view-mode-view-mode-1');

    // Check that the toolbar status matches "no view mode".
    $dropdown_button = $page->find('css', 'button.ck-dropdown__button > span.ck-button__label');
    $this->assertEquals('View Mode 1', $dropdown_button->getText());

    // Test that setting the allowed_view_modes option to only one option hides
    // the field (it requires more than one option).
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          'view_mode_1' => 'view_mode_1',
        ],
      ],
    ])->save();

    // Test that the dependencies change when the allowed_view_modes change.
    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertSame(['core.entity_view_mode.media.view_mode_1'], $dependencies['config']);
    // Reload page to get new configuration.
    $this->getSession()->reload();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    $this->click('.ck-widget.drupal-media');
    // Check that view mode dropdown is gone because there is only one option.
    $this->assertEmpty($assert_session->waitForElementVisible('css', '.ck.ck-dropdown', 1000));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertFalse($drupal_media_element->hasAttribute('data-view-mode'));
    $assert_session->waitForElement('css', 'article.media--view-mode-view-mode-1');

    // Test that setting allowed_view_modes back to two items restores the
    // field.
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          'view_mode_1' => 'view_mode_1',
          '22222' => '22222',
        ],
      ],
    ])->save();

    // Test that the dependencies change when the allowed_view_modes change.
    $expected_config_dependencies = [
      'core.entity_view_mode.media.view_mode_1',
      'core.entity_view_mode.media.22222',
    ];
    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertEqualsCanonicalizing($expected_config_dependencies, $dependencies['config']);
    // Reload page to get new configuration.
    $this->getSession()->reload();
    $this->waitForEditor();

    // Test that changing the view mode with an empty editable caption
    // preserves the empty editable caption when the preview reloads.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.drupal-media figcaption'));
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace('data-caption="baz"', '', $original_value);
    $this->host->save();
    $this->getSession()->reload();
    $this->waitForEditor();
    $assert_session->elementExists('css', 'article.media--view-mode-view-mode-1');

    $this->assertEmpty($assert_session->waitForElementVisible('css', '.drupal-media figcaption'));
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('View Mode 1')->click();
    $this->getBalloonButton('View Mode 2 has Numeric ID')->click();
    $assert_session->waitForElement('css', 'article.media--view-mode-_2222');
    $this->assertEmpty($assert_session->waitForElementVisible('css', '.drupal-media figcaption'));

    // Test that a media with no view modes configured will be
    // set to the default view mode.
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [],
      ],
    ])->save();
    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertSame(['core.entity_view_mode.media.view_mode_1'], $dependencies['config']);
    $this->host->body->value = '<drupal-media data-caption="armadillo" data-entity-type="media" data-entity-uuid="' . $this->mediaFile->uuid() . '"></drupal-media>';
    $this->host->save();
    // Reload page to get new configuration.
    $this->getSession()->reload();
    $this->waitForEditor();
    $assert_session->waitForElementVisible('css', 'article.media--view-mode-view-mode-1');

    // Test that having a default_view_mode that is not an allowed_view_mode
    // will still be added to the editor.
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          '22222' => '22222',
          'view_mode_4' => 'view_mode_4',
        ],
      ],
    ])->save();

    // Test that the dependencies change when the allowed_view_modes change.
    $expected_config_dependencies = [
      'core.entity_view_mode.media.22222',
      'core.entity_view_mode.media.view_mode_1',
      'core.entity_view_mode.media.view_mode_4',
    ];
    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertEqualsCanonicalizing($expected_config_dependencies, $dependencies['config']);
    $this->host->body->value = '<drupal-media data-entity-type="media" data-entity-uuid="' . $this->media->uuid() . '" data-caption="baz"></drupal-media>';
    $this->host->save();
    // Reload page to get new configuration.
    $this->getSession()->reload();
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');

    $this->assertNotEmpty($dropdown = $this->getBalloonButton('View Mode 1'));
    $dropdown->click();

    // Check that all three view modes exist including the default view mode
    // that was not originally included in the allowed_view_modes.
    $this->assertNotEmpty($this->getBalloonButton('View Mode 1'));
    $this->assertNotEmpty($this->getBalloonButton('View Mode 2 has Numeric ID'));
    $this->assertNotEmpty($this->getBalloonButton('View Mode 4'));
  }

  /**
   * For testing view modes in different scenarios.
   */
  public function providerTestViewMode(): array {
    return [
      'with alignment' => [TRUE],
      'without alignment' => [FALSE],
    ];
  }

}
