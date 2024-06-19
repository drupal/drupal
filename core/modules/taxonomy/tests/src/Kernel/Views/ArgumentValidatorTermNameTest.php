<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Views;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\views\Views;

/**
 * Tests the plugin of the taxonomy: taxonomy_term_name argument validator.
 *
 * @group taxonomy
 */
class ArgumentValidatorTermNameTest extends TaxonomyTestBase {

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
   * {@inheritdoc}
   */
  public static $testViews = ['test_taxonomy_name_argument'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    // Add three terms to the 'tags' vocabulary.
    for ($i = 0; $i < 3; $i++) {
      $this->terms[] = $term = $this->createTerm();
      $this->names[] = $term->label();
      $this->ids[] = $term->id();
    }

    // Create a second vocabulary.
    $vocabulary2 = Vocabulary::create([
      'name' => 'Views testing tags 2',
      'vid' => 'views_testing_tags_2',
    ]);
    $vocabulary2->save();
    // Add term in this vocabulary that has same name as term 3.
    $duplicate = $this->createTerm([
      'name' => $this->names[2],
      'vid' => 'views_testing_tags_2',
    ]);
    $this->terms[] = $duplicate;
    $this->names[] = $duplicate->label();
    $this->ids[] = $duplicate->id();

    // Add uniquely named term in second vocab as well.
    $unique = $this->createTerm([
      'vid' => 'views_testing_tags_2',
    ]);
    $this->terms[] = $unique;
    $this->names[] = $unique->label();
    $this->ids[] = $unique->id();
  }

  /**
   * Tests the term name argument validator plugin.
   */
  public function testArgumentValidatorTermName(): void {
    $view = Views::getView('test_taxonomy_name_argument');
    $view->initHandlers();

    // Test with name that does not correspond to any term.
    $this->assertFalse($view->argument['name']->setArgument('not a term name'));
    $view->argument['name']->validated_title = NULL;
    $view->argument['name']->argument_validated = NULL;

    // Test with term in the wrong vocabulary.
    $this->assertFalse($view->argument['name']->setArgument($this->names[4]));
    $view->argument['name']->validated_title = NULL;
    $view->argument['name']->argument_validated = NULL;

    // Test with a couple valid names.
    $this->assertTrue($view->argument['name']->setArgument($this->names[0]));
    $this->assertEquals($this->names[0], $view->argument['name']->getTitle());
    $view->argument['name']->validated_title = NULL;
    $view->argument['name']->argument_validated = NULL;

    $this->assertTrue($view->argument['name']->setArgument($this->names[1]));
    $this->assertEquals($this->names[1], $view->argument['name']->getTitle());
    $view->argument['name']->validated_title = NULL;
    $view->argument['name']->argument_validated = NULL;

    // Test that multiple valid terms don't validate because multiple arguments
    // are currently not supported.
    $multiple_terms = $this->names[0] . '+' . $this->names[1];
    $this->assertFalse($view->argument['name']->setArgument($multiple_terms));
    $view->argument['name']->validated_title = NULL;
    $view->argument['name']->argument_validated = NULL;

    // Test term whose name is shared by term in disallowed bundle.
    $this->assertTrue($view->argument['name']->setArgument($this->names[2]));
    $this->assertEquals($this->names[2], $view->argument['name']->getTitle());
    $view->argument['name']->validated_title = NULL;
    $view->argument['name']->argument_validated = NULL;

    // Add the second vocabulary as an allowed bundle.
    $view->argument['name']->options['validate_options']['bundles']['views_testing_tags_2'] = 'views_testing_tags_2';

    // Test that an array of bundles is handled by passing terms with unique
    // names in each bundle.
    $this->assertTrue($view->argument['name']->setArgument($this->names[0]));
    $this->assertEquals($this->names[0], $view->argument['name']->getTitle());
    $view->argument['name']->validated_title = NULL;
    $view->argument['name']->argument_validated = NULL;

    $this->assertTrue($view->argument['name']->setArgument($this->names[4]));
    $this->assertEquals($this->names[4], $view->argument['name']->getTitle());
    $view->argument['name']->validated_title = NULL;
    $view->argument['name']->argument_validated = NULL;

    // Allow any and all bundles.
    $view->argument['name']->options['validate_options']['bundles'] = [];

    // Test that an empty array of bundles is handled by testing terms with
    // unique names in each bundle.
    $this->assertTrue($view->argument['name']->setArgument($this->names[0]));
    $this->assertEquals($this->names[0], $view->argument['name']->getTitle());
    $view->argument['name']->validated_title = NULL;
    $view->argument['name']->argument_validated = NULL;

    $this->assertTrue($view->argument['name']->setArgument($this->names[4]));
    $this->assertEquals($this->names[4], $view->argument['name']->getTitle());
  }

  /**
   * Tests the access checking in term name argument validator plugin.
   */
  public function testArgumentValidatorTermNameAccess(): void {
    $this->installConfig(['user']);
    $this->setCurrentUser($this->createUser(['access content']));
    $view = Views::getView('test_taxonomy_name_argument');
    $view->initHandlers();

    // Enable access checking on validator.
    $view->argument['name']->options['validate_options']['access'] = TRUE;
    // Allow all bundles.
    $view->argument['name']->options['validate_options']['bundles'] = [];

    // A uniquely named unpublished term in an allowed bundle.
    $this->terms[0]->setUnpublished()->save();
    $this->assertFalse($view->argument['name']->setArgument($this->names[0]));
    $view->argument['name']->validated_title = NULL;
    $view->argument['name']->argument_validated = NULL;

    // A name used by two terms in a single vocabulary. One is unpublished.
    // We re-name the second term to match the first one.
    $this->terms[1]->set('name', $this->names[0])->save();
    $this->names[1] = $this->terms[1]->label();
    $this->assertTrue($view->argument['name']->setArgument($this->names[0]));
    $this->assertEquals($this->names[0], $view->argument['name']->getTitle());
    $view->argument['name']->validated_title = NULL;
    $view->argument['name']->argument_validated = NULL;

    // A name shared by a term in each vocabulary. One is unpublished.
    $this->terms[3]->setUnpublished()->save();
    $this->assertTrue($view->argument['name']->setArgument($this->names[3]));
    $this->assertEquals($this->names[3], $view->argument['name']->getTitle());
  }

}
