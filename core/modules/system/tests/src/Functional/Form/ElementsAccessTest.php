<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests access control for form elements.
 *
 * @group Form
 */
class ElementsAccessTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensures that child values are still processed when #access = FALSE.
   */
  public function testAccessFalse() {
    $this->drupalPostForm('form_test/vertical-tabs-access', NULL, t('Submit'));
    $this->assertNoText(t('This checkbox inside a vertical tab does not have its default value.'));
    $this->assertNoText(t('This textfield inside a vertical tab does not have its default value.'));
    $this->assertNoText(t('This checkbox inside a fieldset does not have its default value.'));
    $this->assertNoText(t('This checkbox inside a container does not have its default value.'));
    $this->assertNoText(t('This checkbox inside a nested container does not have its default value.'));
    $this->assertNoText(t('This checkbox inside a vertical tab whose fieldset access is allowed does not have its default value.'));
    $this->assertText(t('The form submitted correctly.'));
  }

}
