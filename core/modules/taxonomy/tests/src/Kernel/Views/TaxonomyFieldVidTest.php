<?php

namespace Drupal\Tests\taxonomy\Kernel\Views;

use Drupal\Core\Render\RenderContext;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\User;
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

    // Create user 1 and set is as the logged in user, so that the logged in
    // user has the correct permissions to view the vocabulary name.
    $this->adminUser = User::create(['name' => $this->randomString()]);
    $this->adminUser->save();
    $this->container->get('current_user')->setAccount($this->adminUser);

    ViewTestData::createTestViews(static::class, ['taxonomy_test_views']);
  }

  /**
   * Tests the field handling for the Vocabulary ID.
   */
  public function testViewsHandlerVidField() {
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
  }

}
