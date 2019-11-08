<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\views\Views;

/**
 * Tests the term_name field handler.
 *
 * @group taxonomy
 *
 * @see \Drupal\taxonomy\Plugin\views\field\TermName
 */
class TermNameFieldTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_taxonomy_term_name'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests term name field plugin functionality.
   */
  public function testTermNameField() {
    $this->term1->name->value = $this->randomMachineName() . ' ' . $this->randomMachineName();
    $this->term1->save();

    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $view = Views::getView('test_taxonomy_term_name');
    $view->initDisplay();
    $this->executeView($view);

    $this->assertEqual($this->term1->getName(), $view->getStyle()->getField(0, 'name'));
    $this->assertEqual($this->term2->getName(), $view->getStyle()->getField(1, 'name'));

    $view = Views::getView('test_taxonomy_term_name');
    $display =& $view->storage->getDisplay('default');
    $display['display_options']['fields']['name']['convert_spaces'] = TRUE;
    $view->storage->invalidateCaches();
    $this->executeView($view);

    $this->assertEqual(str_replace(' ', '-', $this->term1->getName()), $view->getStyle()->getField(0, 'name'));
    $this->assertEqual($this->term2->getName(), $view->getStyle()->getField(1, 'name'));
  }

}
