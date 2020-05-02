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
  public static $modules = [
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
   * A taxonomy term to use in this test.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $term1;

  /**
   * An admin user.
   *
   * @var \Drupal\user\Entity\User;
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
    $this->installConfig(['filter']);

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = $this->createVocabulary();
    $this->term1 = $this->createTerm($vocabulary);

    // Create user 1 and set is as the logged in user, so that the logged in
    // user has the correct permissions to view the vocabulary name.
    $this->adminUser = User::create(['name' => $this->randomString()]);
    $this->adminUser->save();
    $this->container->get('current_user')->setAccount($this->adminUser);

    ViewTestData::createTestViews(get_class($this), ['taxonomy_test_views']);
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
    $vocabulary = Vocabulary::load($this->term1->bundle());
    $expected = $vocabulary->get('name');

    $this->assertEquals($expected, $actual);
  }

}
