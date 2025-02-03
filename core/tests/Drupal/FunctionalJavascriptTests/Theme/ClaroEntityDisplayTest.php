<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Theme;

use Drupal\entity_test\EntityTestHelper;
use Drupal\Tests\field_ui\FunctionalJavascript\EntityDisplayTest;

/**
 * Runs EntityDisplayTest in Claro.
 *
 * @group claro
 *
 * @see \Drupal\Tests\field_ui\FunctionalJavascript\EntityDisplayTest.
 */
class ClaroEntityDisplayTest extends EntityDisplayTest {

  /**
   * Modules to install.
   *
   * Install the shortcut module so that claro.settings has its schema checked.
   * There's currently no way for Claro to provide a default and have valid
   * configuration as themes cannot react to a module install.
   *
   * @var string[]
   */
  protected static $modules = ['shortcut'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('theme_installer')->install(['claro']);
    $this->config('system.theme')->set('default', 'claro')->save();
  }

  /**
   * Copied from parent.
   *
   * This is Drupal\Tests\field_ui\FunctionalJavascript\EntityDisplayTest::testEntityForm()
   * with a line changed to reflect row weight toggle being a link instead
   * of a button.
   */
  public function testEntityForm(): void {
    $this->drupalGet('entity_test/manage/1/edit');
    $this->assertSession()->fieldExists('field_test_text[0][value]');

    $this->drupalGet('entity_test/structure/entity_test/form-display');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'content')->isSelected());
    $this->getSession()->getPage()->pressButton('Show row weights');
    $this->assertSession()->waitForElementVisible('css', '[name="fields[field_test_text][region]"]');
    $this->getSession()->getPage()->selectFieldOption('fields[field_test_text][region]', 'hidden');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'hidden')->isSelected());

    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'hidden')->isSelected());

    $this->drupalGet('entity_test/manage/1/edit');
    $this->assertSession()->fieldNotExists('field_test_text[0][value]');
  }

  /**
   * Copied from parent.
   *
   * This is Drupal\Tests\field_ui\FunctionalJavascript\EntityDisplayTest::testEntityView()
   * with a line changed to reflect row weight toggle being a link instead
   * of a button.
   */
  public function testEntityView(): void {
    $this->drupalGet('entity_test/1');
    $this->assertSession()->elementNotExists('css', '.field--name-field-test-text');

    $this->drupalGet('entity_test/structure/entity_test/display');
    $this->assertSession()->elementExists('css', '.region-content-message.region-empty');
    $this->getSession()->getPage()->pressButton('Show row weights');
    $this->assertSession()->waitForElementVisible('css', '[name="fields[field_test_text][region]"]');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'hidden')->isSelected());

    $this->getSession()->getPage()->selectFieldOption('fields[field_test_text][region]', 'content');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'content')->isSelected());

    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'content')->isSelected());

    $this->drupalGet('entity_test/1');
    $this->assertSession()->elementExists('css', '.field--name-field-test-text');
  }

  /**
   * Copied from parent.
   *
   * This is Drupal\Tests\field_ui\FunctionalJavascript\EntityDisplayTest::testExtraFields()
   * with a line changed to reflect Claro's tabledrag selector.
   */
  public function testExtraFields(): void {
    EntityTestHelper::createBundle('bundle_with_extra_fields');
    $this->drupalGet('entity_test/structure/bundle_with_extra_fields/display');
    $this->assertSession()->waitForElement('css', '.tabledrag-handle');
    $id = $this->getSession()->getPage()->find('css', '[name="form_build_id"]')->getValue();

    $extra_field_row = $this->getSession()->getPage()->find('css', '#display-extra-field');
    $disabled_region_row = $this->getSession()->getPage()->find('css', '.region-hidden-title');

    $extra_field_row->find('css', '.js-tabledrag-handle')->dragTo($disabled_region_row);
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()
      ->waitForElement('css', "[name='form_build_id']:not([value='$id'])");

    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
  }

}
