<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Views;

use Drupal\Core\Link;
use Drupal\Core\Render\RenderContext;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the taxonomy term TID field handler.
 *
 * @group taxonomy
 */
class TaxonomyFieldTidTest extends ViewsKernelTestBase {

  use TaxonomyTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy',
    'taxonomy_test_views',
    'text',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_taxonomy_tid_field'];

  /**
   * A taxonomy term to use in this test.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term1;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
    $this->installConfig(['filter']);
    $this->setUpCurrentUser(permissions: [
      'access content',
    ]);

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = $this->createVocabulary();
    $this->term1 = $this->createTerm($vocabulary);

    ViewTestData::createTestViews(static::class, ['taxonomy_test_views']);
  }

  /**
   * Tests the taxonomy field handler.
   */
  public function testViewsHandlerTidField(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_taxonomy_tid_field');
    $this->executeView($view);

    $actual = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['name']->advancedRender($view->result[0]);
    });
    $expected = Link::fromTextAndUrl($this->term1->label(), $this->term1->toUrl());

    $this->assertEquals($expected->toString(), $actual);
  }

}
