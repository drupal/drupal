<?php

declare(strict_types=1);

namespace Drupal\Tests\system\FunctionalJavascript\Form;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests forms using #config_target and #ajax together.
 *
 * @group Form
 */
class ConfigTargetTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests #config_target with no callbacks.
   *
   * If a #config_target has no callbacks, the form can be cached.
   */
  public function testTree(): void {
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable */
    $key_value_expirable = \Drupal::service('keyvalue.expirable')->get('form');

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/form-test/tree-config-target');
    $this->assertCount(0, $key_value_expirable->getAll());
    $page->fillField('Nemesis', 'Test');
    $assert_session->pageTextNotContains('Option 3');
    $page->selectFieldOption('test1', 'Option 2');
    $assert_session->waitForText('Option 3');
    $assert_session->pageTextContains('Option 3');
    // The ajax request should result in the form being cached.
    $this->assertCount(1, $key_value_expirable->getAll());

    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The configuration options have been saved.');
    $assert_session->fieldValueEquals('Nemesis', 'Test');

    // The form cache will be deleted after submission.
    $this->assertCount(0, $key_value_expirable->getAll());
  }

  /**
   * Tests #config_target with callbacks.
   *
   * If a #config_target has closures as callbacks, form cache will be disabled.
   */
  public function testNested(): void {
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable */
    $key_value_expirable = \Drupal::service('keyvalue.expirable')->get('form');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/form-test/nested-config-target');
    $this->assertCount(0, $key_value_expirable->getAll());
    $page->fillField('First choice', 'Apple');
    $page->fillField('Second choice', 'Kiwi');

    $assert_session->pageTextNotContains('Option 3');
    $page->selectFieldOption('test1', 'Option 2');
    $assert_session->waitForText('Option 3');
    $assert_session->pageTextContains('Option 3');
    $this->assertCount(0, $key_value_expirable->getAll());

    $page->pressButton('Save configuration');
    $assert_session->statusMessageContains('The configuration options have been saved.', 'status');

    $assert_session->fieldValueEquals('First choice', 'Apple');
    $assert_session->fieldValueEquals('Second choice', 'Kiwi');
    $this->assertCount(0, $key_value_expirable->getAll());
  }

}
