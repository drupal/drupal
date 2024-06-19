<?php

declare(strict_types=1);

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
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_term_show_entity'];

  /**
   * Sets base fields to configurable display and check settings are respected.
   */
  public function testDisplayConfigurable(): void {
    $user = $this->drupalCreateUser(['administer nodes']);
    $this->drupalLogin($user);

    $assert = $this->assertSession();

    // Check the taxonomy_term with default non-configurable display.
    $this->drupalGet('test_term_show_entity');
    // Name should be linked to entity and description should be displayed.
    $assert->pageTextContains($this->term1->getName());
    $assert->linkByHrefExists($this->term1->toUrl()->toString());
    $assert->pageTextContains($this->term1->getDescription());
    $assert->pageTextContains($this->term2->getName());
    $assert->linkByHrefExists($this->term2->toUrl()->toString());
    $assert->pageTextContains($this->term2->getDescription());
    // The field labels should not be present.
    $assert->pageTextNotContains('Name');
    $assert->pageTextNotContains('Description');

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
    // The description should be the first field in each row, with no label.
    // Name field should be the second field in view row. Value should be
    // displayed after the label. It should not be linked to the term.
    $assert->pageTextContains('Name');
    $assert->pageTextNotContains('Description');
    $assert->pageTextContains($this->term1->getName());
    $assert->linkByHrefNotExists($this->term1->toUrl()->toString());
    $assert->pageTextContains($this->term1->getDescription());
    $assert->elementTextContains('xpath', '//*[@class="views-row"][1]/div/div[1]//p', $this->term1->getDescription());
    $assert->elementTextContains('xpath', '//*[@class="views-row"][1]/div/div[2]/div[1]', 'Name');
    $assert->elementTextContains('xpath', '//*[@class="views-row"][1]/div/div[2]/div[2]', $this->term1->getName());
    $assert->pageTextContains($this->term2->getName());
    $assert->linkByHrefNotExists($this->term2->toUrl()->toString());
    $assert->pageTextContains($this->term2->getDescription());
    $assert->elementTextContains('xpath', '//*[@class="views-row"][2]/div/div[1]//p', $this->term2->getDescription());
    $assert->elementTextContains('xpath', '//*[@class="views-row"][2]/div/div[2]/div[1]', 'Name');
    $assert->elementTextContains('xpath', '//*[@class="views-row"][2]/div/div[2]/div[2]', $this->term2->getName());

    // Remove 'name' field from display.
    $display->removeComponent('name')->save();

    // Recheck the taxonomy_term with 'name' field removed from display.
    // There should just be an unlabelled description. Nothing should be
    // linked to the terms.
    $this->drupalGet('test_term_show_entity');
    $assert->pageTextNotContains('Name');
    $assert->pageTextNotContains('Description');
    $assert->pageTextNotContains($this->term1->getName());
    $assert->linkByHrefNotExists($this->term1->toUrl()->toString());
    $assert->pageTextContains($this->term1->getDescription());
    $assert->pageTextNotContains($this->term2->getName());
    $assert->linkByHrefNotExists($this->term2->toUrl()->toString());
    $assert->pageTextContains($this->term2->getDescription());
  }

}
