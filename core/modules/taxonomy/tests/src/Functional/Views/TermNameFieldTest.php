<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\Core\Link;
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

    $this->assertEquals($this->term1->getName(), $view->getStyle()->getField(0, 'name'));
    $this->assertEquals($this->term2->getName(), $view->getStyle()->getField(1, 'name'));

    $view = Views::getView('test_taxonomy_term_name');
    $display =& $view->storage->getDisplay('default');
    $display['display_options']['fields']['name']['convert_spaces'] = TRUE;
    $view->storage->invalidateCaches();
    $this->executeView($view);

    $this->assertEquals(str_replace(' ', '-', $this->term1->getName()), $view->getStyle()->getField(0, 'name'));
    $this->assertEquals($this->term2->getName(), $view->getStyle()->getField(1, 'name'));

    // Enable link_to_entity option and ensure that title is displayed properly.
    $view = Views::getView('test_taxonomy_term_name');
    $display =& $view->storage->getDisplay('default');
    $display['display_options']['fields']['name']['convert_spaces'] = TRUE;
    $display['display_options']['fields']['name']['settings']['link_to_entity'] = TRUE;
    $view->storage->invalidateCaches();
    $this->executeView($view);

    $expected_link1 = Link::fromTextAndUrl(str_replace(' ', '-', $this->term1->getName()), $this->term1->toUrl());
    $expected_link2 = Link::fromTextAndUrl($this->term2->getName(), $this->term2->toUrl());
    $this->assertEquals($expected_link1->toString(), $view->getStyle()->getField(0, 'name'));
    $this->assertEquals($expected_link2->toString(), $view->getStyle()->getField(1, 'name'));
  }

}
