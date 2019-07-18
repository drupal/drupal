<?php

namespace Drupal\Tests\editor\Unit;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Plugin\EditorBase;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\editor\Plugin\EditorBase
 * @group editor
 *
 * @group legacy
 */
class EditorBaseTest extends UnitTestCase {

  /**
   * @covers ::buildConfigurationForm
   * @covers ::validateConfigurationForm
   * @covers ::submitConfigurationForm
   *
   * @expectedDeprecation Drupal\Tests\editor\Unit\BcEditor::settingsForm is deprecated since version 8.3.x. Rename the implementation 'buildConfigurationForm'. See https://www.drupal.org/node/2819753
   * @expectedDeprecation Drupal\Tests\editor\Unit\BcEditor::settingsFormValidate is deprecated since version 8.3.x. Rename the implementation 'validateConfigurationForm'. See https://www.drupal.org/node/2819753
   * @expectedDeprecation Drupal\Tests\editor\Unit\BcEditor::settingsFormSubmit is deprecated since version 8.3.x. Rename the implementation 'submitConfigurationForm'. See https://www.drupal.org/node/2819753
   */
  public function testBc() {
    $form_state = new FormState();
    $form_state->set('editor', $this->prophesize(Editor::class)->reveal());
    $editor_plugin = new BcEditor([], 'editor_plugin', []);

    // settingsForm() is deprecated in favor of buildConfigurationForm().
    $this->assertSame(
      $editor_plugin->settingsForm([], clone $form_state, $this->prophesize(Editor::class)->reveal()),
      $editor_plugin->buildConfigurationForm([], clone $form_state)
    );

    // settingsFormValidate() is deprecated in favor of
    // validateConfigurationForm().
    $form = [];
    $form_state_a = clone $form_state;
    $form_state_b = clone $form_state;
    $editor_plugin->settingsFormValidate($form, $form_state_a, $this->prophesize(Editor::class)->reveal());
    $editor_plugin->validateConfigurationForm($form, $form_state_b);
    $this->assertEquals($form_state_a, $form_state_b);

    // settingsFormSubmit() is deprecated in favor of submitConfigurationForm().
    $form = [];
    $form_state_a = clone $form_state;
    $form_state_b = clone $form_state;
    $editor_plugin->settingsFormSubmit($form, $form_state_a, $this->prophesize(Editor::class)->reveal());
    $editor_plugin->submitConfigurationForm($form, $form_state_b);
    $this->assertEquals($form_state_a, $form_state_b);
  }

}

class BcEditor extends EditorBase {

  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    return ['foo' => 'bar'];
  }

  public function settingsFormValidate(array $form, FormStateInterface $form_state) {
    $form_state->setValue('foo', 'bar');
  }

  public function settingsFormSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setValue('bar', 'baz');
  }

  public function getJSSettings(Editor $editor) {
    return [];
  }

  public function getLibraries(Editor $editor) {
    return [];
  }

}
