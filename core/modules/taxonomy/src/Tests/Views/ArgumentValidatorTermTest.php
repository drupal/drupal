<?php

namespace Drupal\taxonomy\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the plugin of the taxonomy: term argument validator.
 *
 * @group taxonomy
 * @see Views\taxonomy\Plugin\views\argument_validator\Term
 */
class ArgumentValidatorTermTest extends TaxonomyTestBase {

  /**
   * Stores the taxonomy term used by this test.
   *
   * @var array
   */
  protected $terms = [];

  /**
   * Stores the taxonomy names used by this test.
   *
   * @var array
   */
  protected $names = [];

  /**
   * Stores the taxonomy IDs used by this test.
   *
   * @var array
   */
  protected $ids = [];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['taxonomy', 'taxonomy_test_views', 'views_test_config'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_argument_validator_term'];

  protected function setUp() {
    parent::setUp();

    // Add three terms to the 'tags' vocabulary.
    for ($i = 0; $i < 3; $i++) {
      $this->terms[] = $term = $this->createTerm();
      $this->names[] = $term->label();
      $this->ids[] = $term->id();
    }
  }

  /**
   * Tests the term argument validator plugin.
   */
  public function testArgumentValidatorTerm() {
    $view = Views::getView('test_argument_validator_term');
    $view->initHandlers();


    // Test the single validator for term IDs.
    $view->argument['tid']->validator->options['type'] = 'tid';

    // Pass in a single valid term.
    foreach ($this->terms as $term) {
      $this->assertTrue($view->argument['tid']->setArgument($term->id()));
      $this->assertEqual($view->argument['tid']->getTitle(), $term->label());
      $view->argument['tid']->validated_title = NULL;
      $view->argument['tid']->argument_validated = NULL;
    }

    // Pass in a invalid term.
    $this->assertFalse($view->argument['tid']->setArgument(rand(1000, 10000)));
    $this->assertEqual('', $view->argument['tid']->getTitle());
    $view->argument['tid']->validated_title = NULL;
    $view->argument['tid']->argument_validated = NULL;


    // Test the multiple validator for term IDs.
    $view->argument['tid']->validator->options['type'] = 'tids';
    $view->argument['tid']->options['break_phrase'] = TRUE;

    // Pass in a single term.
    $this->assertTrue($view->argument['tid']->setArgument($this->terms[0]->id()));
    $this->assertEqual($view->argument['tid']->getTitle(), $this->terms[0]->label());
    $view->argument['tid']->validated_title = NULL;
    $view->argument['tid']->argument_validated = NULL;

    // Check for multiple valid terms separated by commas.
    $this->assertTrue($view->argument['tid']->setArgument(implode(',', $this->ids)));
    $this->assertEqual($view->argument['tid']->getTitle(), implode(', ', $this->names));
    $view->argument['tid']->validated_title = NULL;
    $view->argument['tid']->argument_validated = NULL;

    // Check for multiple valid terms separated by plus signs.
    $this->assertTrue($view->argument['tid']->setArgument(implode('+', $this->ids)));
    $this->assertEqual($view->argument['tid']->getTitle(), implode(' + ', $this->names));
    $view->argument['tid']->validated_title = NULL;
    $view->argument['tid']->argument_validated = NULL;

    // Check for a single invalid term.
    $this->assertFalse($view->argument['tid']->setArgument(rand(1000, 10000)));
    $this->assertEqual('', $view->argument['tid']->getTitle());
    $view->argument['tid']->validated_title = NULL;
    $view->argument['tid']->argument_validated = NULL;

    // Check for multiple invalid terms.
    $this->assertFalse($view->argument['tid']->setArgument(implode(',', [rand(1000, 10000), rand(1000, 10000)])));
    $this->assertEqual('', $view->argument['tid']->getTitle());
    $view->argument['tid']->validated_title = NULL;
    $view->argument['tid']->argument_validated = NULL;
  }

}
