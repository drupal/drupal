<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Views;

use Drupal\Core\Render\RenderContext;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the taxonomy term VID field handler.
 *
 * @group taxonomy
 */
class TaxonomyFieldVidTest extends ViewsKernelTestBase {

  use TaxonomyTestTrait;
  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'taxonomy',
    'taxonomy_test_views',
    'text',
    'filter',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_taxonomy_vid_field'];

  /**
   * An array of taxonomy term used in this test.
   *
   * @var \Drupal\taxonomy\Entity\Term[]
   */
  protected $terms;

  /**
   * An admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
    $this->installConfig(['filter']);

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = $this->createVocabulary(['vid' => 'aaa']);
    $term = $this->createTerm($vocabulary);
    $this->terms[$term->id()] = $term;

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary2 */
    $vocabulary2 = $this->createVocabulary(['vid' => 'bbb']);
    $term = $this->createTerm($vocabulary2);
    $this->terms[$term->id()] = $term;
    $this->adminUser = $this->createUser(['administer taxonomy']);
    $this->container->get('current_user')->setAccount($this->adminUser);

    ViewTestData::createTestViews(static::class, ['taxonomy_test_views']);
  }

  /**
   * Tests the field handling for the Vocabulary ID.
   */
  public function testViewsHandlerVidField(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_taxonomy_vid_field');
    $this->executeView($view);

    $actual = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['vid']->advancedRender($view->result[0]);
    });
    $tid = $view->result[0]->_entity->id();
    $vocabulary = Vocabulary::load($this->terms[$tid]->bundle());
    $expected = $vocabulary->get('name');

    $this->assertEquals($expected, $actual, 'Displayed vocabulary name should match that loaded from the term.');
    $this->assertEquals('aaa', $vocabulary->id(), 'First result should be vocabulary "aaa", due to ASC sorting.');

    // Reverse sorting.

    $view = Views::getView('test_taxonomy_vid_field');
    $view->setHandlerOption('default', 'sort', 'vid', 'order', 'DESC');
    $this->executeView($view);

    $actual = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['vid']->advancedRender($view->result[0]);
    });
    $tid = $view->result[0]->_entity->id();
    $vocabulary = Vocabulary::load($this->terms[$tid]->bundle());
    $expected = $vocabulary->get('name');

    $this->assertEquals($expected, $actual, 'Displayed vocabulary name should match that loaded from the term.');
    $this->assertEquals('bbb', $vocabulary->id(), 'First result should be vocabulary "bbb", due to DESC sorting.');

    // Test with user without 'view vocabulary labels' permission.
    $this->setUpCurrentUser();
    $actual = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['vid']->advancedRender($view->result[0]);
    });
    $expected = '';
    $this->assertEquals($expected, $actual);

    // Test with user with 'view vocabulary labels' permissions.
    $this->setUpCurrentUser([], ['view vocabulary labels']);
    $actual = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['vid']->advancedRender($view->result[0]);
    });
    $expected = $vocabulary->label();
    $this->assertEquals($expected, $actual);

    // Test with user with 'administer taxonomy' and 'access taxonomy overview'
    // permissions. Label should be displayed for either permission.
    $this->setUpCurrentUser([], [
      'administer taxonomy',
      'access taxonomy overview',
    ]);
    $actual = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['vid']->advancedRender($view->result[0]);
    });
    $expected = $vocabulary->label();
    $this->assertEquals($expected, $actual);
  }

}
