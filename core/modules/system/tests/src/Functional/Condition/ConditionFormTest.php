<?php

namespace Drupal\Tests\system\Functional\Condition;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that condition plugins basic form handling is working.
 *
 * Checks condition forms and submission and gives a very cursory check to make
 * sure the configuration that was submitted actually causes the condition to
 * validate correctly.
 *
 * @group Condition
 */
class ConditionFormTest extends BrowserTestBase {

  protected static $modules = ['node', 'condition_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Submit the condition_node_type_test_form to test condition forms.
   */
  public function testConfigForm() {
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    $article = Node::create([
      'type' => 'article',
      'title' => $this->randomMachineName(),
    ]);
    $article->save();

    $this->drupalGet('condition_test');
    $this->assertSession()->fieldExists('entity_bundle[bundles][article]');
    $this->assertSession()->fieldExists('entity_bundle[bundles][page]');
    $this->submitForm(['entity_bundle[bundles][page]' => 'page', 'entity_bundle[bundles][article]' => 'article'], 'Submit');
    // @see \Drupal\condition_test\FormController::submitForm()
    $this->assertSession()->pageTextContains('Bundle: page');
    $this->assertSession()->pageTextContains('Bundle: article');
    $this->assertSession()->pageTextContains('Executed successfully.');

    $this->assertSession()->pageTextContains('The current theme is stark');
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = $this->container->get('theme_installer');
    $theme_installer->install(['olivero']);
    $this->drupalGet('condition_test');
    $this->submitForm(['current_theme[theme]' => 'olivero', 'current_theme[negate]' => TRUE], 'Submit');
    $this->assertSession()->pageTextContains('The current theme is not olivero');
  }

}
