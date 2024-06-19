<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Views;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewTestData;

/**
 * Test the taxonomy term index filter.
 *
 * @see \Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid
 *
 * @group taxonomy
 */
class TaxonomyIndexTidFilterTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_filter_taxonomy_index_tid__non_existing_dependency'];

  /**
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(FALSE);

    // Setup vocabulary and terms so the initial import is valid.
    Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ])->save();

    // This will get a term ID of 3.
    $term = Term::create([
      'vid' => 'tags',
      'name' => 'muh',
    ]);
    $term->save();
    // This will get a term ID of 4.
    $this->terms[$term->id()] = $term;
    $term = Term::create([
      'vid' => 'tags',
      'name' => 'muh',
    ]);
    $term->save();
    $this->terms[$term->id()] = $term;

    ViewTestData::createTestViews(static::class, ['taxonomy_test_views']);
  }

  /**
   * Tests dependencies are not added for terms that do not exist.
   */
  public function testConfigDependency(): void {
    /** @var \Drupal\views\Entity\View $view */
    $view = View::load('test_filter_taxonomy_index_tid__non_existing_dependency');

    // Dependencies are sorted.
    $content_dependencies = [
      $this->terms[3]->getConfigDependencyName(),
      $this->terms[4]->getConfigDependencyName(),
    ];
    sort($content_dependencies);

    $this->assertEquals([
      'config' => [
        'taxonomy.vocabulary.tags',
      ],
      'content' => $content_dependencies,
      'module' => [
        'node',
        'taxonomy',
        'user',
      ],
    ], $view->calculateDependencies()->getDependencies());

    $this->terms[3]->delete();

    $this->assertEquals([
      'config' => [
        'taxonomy.vocabulary.tags',
      ],
      'content' => [
        $this->terms[4]->getConfigDependencyName(),
      ],
      'module' => [
        'node',
        'taxonomy',
        'user',
      ],
    ], $view->calculateDependencies()->getDependencies());
  }

}
