<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests copy field value functionality.
 *
 * @see Drupal.behaviors.copyFieldValue.
 *
 * @group system
 */
class CopyFieldValueTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * Tests copy field value JavaScript functionality.
   */
  public function testCopyFieldValue(): void {
    $this->drupalGet('/system-test/copy-field-value-test-form');
    $page = $this->getSession()->getPage();
    $source_field_selector = 'edit-source-field';
    $target_field = $page->find('css', '#edit-target-field');

    $random_string = $this->randomString();
    // Ensure that after source field has been filled, target field is filled
    // with the same value.
    $page->fillField($source_field_selector, $random_string);
    $target_field->focus();
    $this->assertEquals($target_field->getValue(), $random_string);

    // Ensure that the target value doesn't change after it has been focused.
    $page->fillField($source_field_selector, '');
    $target_field->focus();
    $this->assertEquals($target_field->getValue(), $random_string);
  }

}
