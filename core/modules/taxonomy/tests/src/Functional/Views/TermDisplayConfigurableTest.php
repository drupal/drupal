<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

/**
 * Tests making taxonomy term base fields' displays configurable.
 *
 * @group taxonomy
 */
class TermDisplayConfigurableTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_term_show_entity'];

  /**
   * Sets base fields to configurable display and check settings are respected.
   */
  public function testDisplayConfigurable() {
    $user = $this->drupalCreateUser(['administer nodes']);
    $this->drupalLogin($user);

    $assert = $this->assertSession();

    // Check the taxonomy_term with default non-configurable display.
    $this->drupalGet('test_term_show_entity');
    $assert->elementTextContains('css', 'h2 > a > .field--name-name', $this->term1->getName());
    $assert->elementNotExists('css', '.field--name-name .field__item');
    $assert->elementNotExists('css', '.field--name-name .field__label');

    // Enable helper module to make base fields' displays configurable.
    \Drupal::service('module_installer')->install(['taxonomy_term_display_configurable_test']);

    // Configure display.
    $display = \Drupal::service('entity_display.repository')->getViewDisplay('taxonomy_term', $this->vocabulary->id(), 'default');
    $display->setComponent('name', [
      'type' => 'text_default',
      'label' => 'above',
    ])->save();

    // Recheck the taxonomy_term with configurable display.
    $this->drupalGet('test_term_show_entity');
    $assert->elementTextContains('css', 'div.field--name-name > div.field__item', $this->term1->getName());
    $assert->elementExists('css', 'div.field--name-name > div.field__label');

    // Remove 'name' field from display.
    $display->removeComponent('name')->save();

    // Recheck the taxonomy_term with 'name' field removed from display.
    $this->drupalGet('test_term_show_entity');
    $assert->responseNotContains($this->term1->getName());
    $assert->elementNotExists('css', 'div.field--name-name');
  }

}
