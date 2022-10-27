<?php

namespace Drupal\Tests\quickedit\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use WebDriver\Key;

/**
 * Base class for testing the QuickEdit.
 */
class QuickEditJavascriptTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['contextual', 'quickedit', 'toolbar'];

  /**
   * A user with permissions to edit Articles and use Quick Edit.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $contentAuthorUser;

  protected static $expectedFieldStateAttributes = [
    'inactive'  => '.quickedit-field:not(.quickedit-editable):not(.quickedit-candidate):not(.quickedit-highlighted):not(.quickedit-editing):not(.quickedit-changed)',
    // A field in 'candidate' state may still have the .quickedit-changed class
    // because when its changes were saved to tempstore, it'll still be changed.
    // It's just not currently being edited, so that's why it is not in the
    // 'changed' state.
    'candidate' => '.quickedit-field.quickedit-editable.quickedit-candidate:not(.quickedit-highlighted):not(.quickedit-editing)',
    'highlighted' => '.quickedit-field.quickedit-editable.quickedit-candidate.quickedit-highlighted:not(.quickedit-editing)',
    'activating' => '.quickedit-field.quickedit-editable.quickedit-candidate.quickedit-highlighted.quickedit-editing:not(.quickedit-changed)',
    'active'    => '.quickedit-field.quickedit-editable.quickedit-candidate.quickedit-highlighted.quickedit-editing:not(.quickedit-changed)',
    'changed'   => '.quickedit-field.quickedit-editable.quickedit-candidate.quickedit-highlighted.quickedit-editing.quickedit-changed',
    'saving'    => '.quickedit-field.quickedit-editable.quickedit-candidate.quickedit-highlighted.quickedit-editing.quickedit-changed',
  ];

  /**
   * Starts in-place editing of the given entity instance.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   * @param int $entity_instance_id
   *   The entity instance ID. (Instance on the page.)
   */
  protected function startQuickEditViaToolbar($entity_type_id, $entity_id, $entity_instance_id) {
    $page = $this->getSession()->getPage();

    $toolbar_edit_button_selector = '#toolbar-bar .contextual-toolbar-tab button';
    $entity_instance_selector = '[data-quickedit-entity-id="' . $entity_type_id . '/' . $entity_id . '"][data-quickedit-entity-instance-id="' . $entity_instance_id . '"]';
    $contextual_links_trigger_selector = '[data-contextual-id] > .trigger';

    // Assert the original page state does not have the toolbar's "Edit" button
    // pressed/activated, and hence none of the contextual link triggers should
    // be visible.
    $toolbar_edit_button = $page->find('css', $toolbar_edit_button_selector);
    $this->assertSame('false', $toolbar_edit_button->getAttribute('aria-pressed'), 'The "Edit" button in the toolbar is not yet pressed.');
    $this->assertFalse($toolbar_edit_button->hasClass('is-active'), 'The "Edit" button in the toolbar is not yet marked as active.');
    foreach ($page->findAll('css', $contextual_links_trigger_selector) as $dom_node) {
      /** @var \Behat\Mink\Element\NodeElement $dom_node */
      $this->assertTrue($dom_node->hasClass('visually-hidden'), 'The contextual links trigger "' . $dom_node->getParent()->getAttribute('data-contextual-id') . '" is hidden.');
    }
    $this->assertTrue(TRUE, 'All contextual links triggers are hidden.');

    // Click the "Edit" button in the toolbar.
    $this->click($toolbar_edit_button_selector);

    // Assert the toolbar's "Edit" button is now pressed/activated, and hence
    // all of the contextual link triggers should be visible.
    $this->assertSame('true', $toolbar_edit_button->getAttribute('aria-pressed'), 'The "Edit" button in the toolbar is pressed.');
    $this->assertTrue($toolbar_edit_button->hasClass('is-active'), 'The "Edit" button in the toolbar is marked as active.');
    foreach ($page->findAll('css', $contextual_links_trigger_selector) as $dom_node) {
      /** @var \Behat\Mink\Element\NodeElement $dom_node */
      $this->assertFalse($dom_node->hasClass('visually-hidden'), 'The contextual links trigger "' . $dom_node->getParent()->getAttribute('data-contextual-id') . '" is visible.');
    }
    $this->assertTrue(TRUE, 'All contextual links triggers are visible.');

    // @todo Press tab key to verify that tabbing is now constrained to only
    // contextual links triggers: https://www.drupal.org/node/2834776

    // Assert that the contextual links associated with the entity's contextual
    // links trigger are not visible.
    /** @var \Behat\Mink\Element\NodeElement $entity_contextual_links_container */
    $entity_contextual_links_container = $page->find('css', $entity_instance_selector)
      ->find('css', $contextual_links_trigger_selector)
      ->getParent();
    $this->assertFalse($entity_contextual_links_container->hasClass('open'));
    $this->assertTrue($entity_contextual_links_container->find('css', 'ul.contextual-links')->hasAttribute('hidden'));

    // Click the contextual link trigger for the entity we want to Quick Edit.
    $this->click($entity_instance_selector . ' ' . $contextual_links_trigger_selector);

    $this->assertTrue($entity_contextual_links_container->hasClass('open'));
    $this->assertFalse($entity_contextual_links_container->find('css', 'ul.contextual-links')->hasAttribute('hidden'));

    // Click the "Quick edit" contextual link.
    $this->click($entity_instance_selector . ' [data-contextual-id] ul.contextual-links li.quickedit a');

    // Assert the Quick Edit internal state is correct.
    $js_condition = <<<JS
Drupal.quickedit.collections.entities.where({isActive: true}).length === 1 && Drupal.quickedit.collections.entities.where({isActive: true})[0].get('entityID') === '$entity_type_id/$entity_id'
JS;
    $this->assertJsCondition($js_condition);
  }

  /**
   * Clicks the 'Save' button in the Quick Edit entity toolbar.
   */
  protected function saveQuickEdit() {
    $quickedit_entity_toolbar = $this->getSession()->getPage()->findById('quickedit-entity-toolbar');
    $save_button = $quickedit_entity_toolbar->find('css', 'button.action-save');
    $save_button->press();
    $this->assertSame('Saving', $save_button->getText());
  }

  /**
   * Awaits Quick Edit to be initiated for all instances of the given entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   */
  protected function awaitQuickEditForEntity($entity_type_id, $entity_id) {
    $entity_selector = '[data-quickedit-entity-id="' . $entity_type_id . '/' . $entity_id . '"]';
    $condition = "document.querySelectorAll('" . $entity_selector . "').length === document.querySelectorAll('" . $entity_selector . " .quickedit').length";
    $this->assertJsCondition($condition, 10000);
  }

  /**
   * Awaits a particular field instance to reach a particular state.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   * @param int $entity_instance_id
   *   The entity instance ID. (Instance on the page.)
   * @param string $field_name
   *   The field name.
   * @param string $langcode
   *   The language code.
   * @param string $awaited_state
   *   One of the possible field states.
   */
  protected function awaitEntityInstanceFieldState($entity_type_id, $entity_id, $entity_instance_id, $field_name, $langcode, $awaited_state) {
    $entity_page_id = $entity_type_id . '/' . $entity_id . '[' . $entity_instance_id . ']';
    $logical_field_id = $entity_type_id . '/' . $entity_id . '/' . $field_name . '/' . $langcode;
    $this->assertJsCondition("Drupal.quickedit.collections.entities.get('$entity_page_id').get('fields').findWhere({logicalFieldID: '$logical_field_id'}).get('state') === '$awaited_state'");
  }

  /**
   * Asserts the state of the Quick Edit entity toolbar.
   *
   * @param string $expected_entity_label
   *   The expected entity label in the Quick Edit Entity Toolbar.
   * @param string|null $expected_field_label
   *   The expected field label in the Quick Edit Entity Toolbar, or NULL
   *   if no field label is expected.
   */
  protected function assertQuickEditEntityToolbar($expected_entity_label, $expected_field_label) {
    $quickedit_entity_toolbar = $this->getSession()->getPage()->findById('quickedit-entity-toolbar');
    // We cannot use ->getText() because it also returns the text of all child
    // nodes. We also cannot use XPath to select text node in Selenium. So we
    // use JS expression to select only the text node.
    $this->assertSame($expected_entity_label, $this->getSession()->evaluateScript("return window.jQuery('#quickedit-entity-toolbar .quickedit-toolbar-label').clone().children().remove().end().text();"));
    if ($expected_field_label !== NULL) {
      $field_label = $quickedit_entity_toolbar->find('css', '.quickedit-toolbar-label > .field');
      // Only try to find the text content of the element if it was actually
      // found; otherwise use the returned value for assertion. This helps
      // us find a more useful stack/error message from testbot instead of the
      // trimmed partial exception stack.
      if ($field_label) {
        $field_label = $field_label->getText();
      }
      $this->assertSame($expected_field_label, $field_label);
    }
    else {
      $this->assertEmpty($quickedit_entity_toolbar->find('css', '.quickedit-toolbar-label > .field'));
    }
  }

  /**
   * Asserts all EntityModels (entity instances) on the page.
   *
   * @param array $expected_entity_states
   *   Must describe the expected state of all in-place editable entity
   *   instances on the page.
   *
   * @see Drupal.quickedit.EntityModel
   */
  protected function assertEntityInstanceStates(array $expected_entity_states) {
    $js_get_all_field_states_for_entity = <<<JS
function () {
    Drupal.quickedit.collections.entities.reduce(function (result, fieldModel) { result[fieldModel.get('id')] = fieldModel.get('state'); return result; }, {})
  var entityCollection = Drupal.quickedit.collections.entities;
  return entityCollection.reduce(function (result, entityModel) {
    result[entityModel.id] = entityModel.get('state');
    return result;
  }, {});
}()
JS;
    $this->assertSame($expected_entity_states, $this->getSession()->evaluateScript($js_get_all_field_states_for_entity));
  }

  /**
   * Asserts all FieldModels for the given entity instance.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   * @param int $entity_instance_id
   *   The entity instance ID. (Instance on the page.)
   * @param array $expected_field_states
   *   Must describe the expected state of all in-place editable fields of the
   *   given entity instance.
   */
  protected function assertEntityInstanceFieldStates($entity_type_id, $entity_id, $entity_instance_id, array $expected_field_states) {
    // Get all FieldModel states for the entity instance being asserted. This
    // ensures that $expected_field_states must describe the state of all fields
    // of the entity instance.
    $entity_page_id = $entity_type_id . '/' . $entity_id . '[' . $entity_instance_id . ']';
    $js_get_all_field_states_for_entity = <<<JS
function () {
  var entityCollection = Drupal.quickedit.collections.entities;
  var entityModel = entityCollection.get('$entity_page_id');
  return entityModel.get('fields').reduce(function (result, fieldModel) {
    result[fieldModel.get('fieldID')] = fieldModel.get('state');
    return result;
  }, {});
}()
JS;
    $this->assertEquals($expected_field_states, $this->getSession()->evaluateScript($js_get_all_field_states_for_entity));

    // Assert that those fields also have the appropriate DOM decorations.
    $expected_field_attributes = [];
    foreach ($expected_field_states as $quickedit_field_id => $expected_field_state) {
      $expected_field_attributes[$quickedit_field_id] = static::$expectedFieldStateAttributes[$expected_field_state];
    }
    $this->assertEntityInstanceFieldMarkup($expected_field_attributes);
  }

  /**
   * Asserts all in-place editable fields with markup expectations.
   *
   * @param array $expected_field_attributes
   *   Must describe the expected markup attributes for all given in-place
   *   editable fields.
   *
   * @todo https://www.drupal.org/project/drupal/issues/3178758 Remove
   *   deprecation layer and add array typehint.
   */
  protected function assertEntityInstanceFieldMarkup($expected_field_attributes) {
    if (func_num_args() === 4) {
      $expected_field_attributes = func_get_arg(3);
      @trigger_error('Calling ' . __METHOD__ . '() with 4 arguments is deprecated in drupal:9.1.0 and will throw an error in drupal:10.0.0. See https://www.drupal.org/project/drupal/issues/3037436', E_USER_DEPRECATED);
    }
    if (!is_array($expected_field_attributes)) {
      throw new \InvalidArgumentException('The $expected_field_attributes argument must be an array.');
    }
    foreach ($expected_field_attributes as $quickedit_field_id => $expectation) {
      $element = $this->assertSession()->waitForElementVisible('css', '[data-quickedit-field-id="' . $quickedit_field_id . '"]' . $expectation);
      $this->assertNotEmpty($element, 'Field ' . $quickedit_field_id . ' did not match its expectation selector (' . $expectation . ')');
    }
  }

  /**
   * Simulates typing in a 'plain_text' in-place editor.
   *
   * @param string $css_selector
   *   The CSS selector to find the DOM element (with the 'contenteditable=true'
   *   attribute set), to type in.
   * @param string $text
   *   The text to type.
   *
   * @see \Drupal\quickedit\Plugin\InPlaceEditor\PlainTextEditor
   */
  protected function typeInPlainTextEditor($css_selector, $text) {
    $field = $this->getSession()->getPage()->find('css', $css_selector);
    $field->setValue(Key::END . $text);
    $this->getSession()->evaluateScript("document.querySelector('$css_selector').dispatchEvent(new Event('blur', {bubbles:true}))");
  }

  /**
   * Simulates typing in an input[type=text] inside a 'form' in-place editor.
   *
   * @param string $input_name
   *   The "name" attribute of the input[type=text] to type in.
   * @param string $text
   *   The text to type.
   *
   * @see \Drupal\quickedit\Plugin\InPlaceEditor\FormEditor
   */
  protected function typeInFormEditorTextInputField($input_name, $text) {
    $input = $this->cssSelect('.quickedit-form-container > .quickedit-form[role="dialog"] form.quickedit-field-form input[type=text][name="' . $input_name . '"]')[0];
    $input->setValue($text);
    $js_simulate_user_typing = <<<JS
function () {
  var el = document.querySelector('.quickedit-form-container > .quickedit-form[role="dialog"] form.quickedit-field-form input[name="$input_name"]');
  window.jQuery(el).trigger('formUpdated');
}()
JS;
    $this->getSession()->evaluateScript($js_simulate_user_typing);
  }

}
