<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Views;

use Drupal\TestTools\Random;
use Drupal\views\Views;

/**
 * Tests taxonomy term argument transformation.
 *
 * @group taxonomy
 *
 * @see \Drupal\taxonomy\Plugin\views\argument_validator\TermName
 */
class ArgumentTransformTermTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_argument_transform_term'];

  /**
   * Tests term argument transformation of hyphens and spaces.
   *
   * @dataProvider termArgumentTransformationProvider
   *
   * @param string $name
   *   The name of the taxonomy term to use for the test.
   */
  public function testTermArgumentTransformation($name): void {
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->createTerm(['name' => $name]);

    /** @var \Drupal\views\ViewExecutable $view */
    $view = Views::getView('test_argument_transform_term');
    $view->initHandlers();

    /** @var string $hyphenated_term */
    $hyphenated_term = str_replace(' ', '-', $term->label());
    $this->assertTrue($view->argument['tid']->setArgument($hyphenated_term));
    // Assert hyphens are converted back to spaces.
    $this->assertEquals($term->label(), $view->argument['tid']->argument);
  }

  /**
   * Provides data for testTermArgumentTransformation().
   *
   * @return array[]
   *   Test data.
   */
  public static function termArgumentTransformationProvider() {
    return [
      'space in the middle' => [
        'name' => Random::machineName() . ' ' . Random::machineName(),
      ],
      'space at the start' => [
        'name' => ' ' . Random::machineName(),
      ],
      'space at the end' => [
        'name' => Random::machineName() . ' ',
      ],
    ];
  }

}
