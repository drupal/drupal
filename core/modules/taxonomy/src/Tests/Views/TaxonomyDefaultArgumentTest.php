<?php

namespace Drupal\taxonomy\Tests\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the representative node relationship for terms.
 *
 * @group taxonomy
 */
class TaxonomyDefaultArgumentTest extends TaxonomyTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('taxonomy_default_argument_test');

  /**
   * Tests the relationship.
   */
  public function testNodePath() {
    $view = Views::getView('taxonomy_default_argument_test');

    $request = Request::create($this->nodes[0]->url());
    $request->server->set('SCRIPT_NAME', $GLOBALS['base_path'] . 'index.php');
    $request->server->set('SCRIPT_FILENAME', 'index.php');

    $response = $this->container->get('http_kernel')
      ->handle($request, HttpKernelInterface::SUB_REQUEST);
    $view->setRequest($request);
    $view->setResponse($response);

    $view->initHandlers();
    $expected = implode(',', array($this->term1->id(), $this->term2->id()));
    $this->assertEqual($expected, $view->argument['tid']->getDefaultArgument());
    $view->destroy();
  }

  public function testNodePathWithViewSelection() {
    // Change the term entity reference field to use a view as selection plugin.
    \Drupal::service('module_installer')->install(['entity_reference_test']);

    $field_name = 'field_' . $this->vocabulary->id();
    $field = FieldConfig::loadByName('node', 'article', $field_name);
    $field->setSetting('handler', 'views');
    $field->setSetting('handler_settings', [
      'view' => [
        'view_name' => 'test_entity_reference',
        'display_name' => 'entity_reference_1',
      ],
    ]);
    $field->save();

    $view = Views::getView('taxonomy_default_argument_test');

    $request = Request::create($this->nodes[0]->url());
    $request->server->set('SCRIPT_NAME', $GLOBALS['base_path'] . 'index.php');
    $request->server->set('SCRIPT_FILENAME', 'index.php');

    $response = $this->container->get('http_kernel')->handle($request, HttpKernelInterface::SUB_REQUEST);
    $view->setRequest($request);
    $view->setResponse($response);

    $view->initHandlers();
    $expected = implode(',', array($this->term1->id(), $this->term2->id()));
    $this->assertEqual($expected, $view->argument['tid']->getDefaultArgument());
  }

  public function testTermPath() {
    $view = Views::getView('taxonomy_default_argument_test');

    $request = Request::create($this->term1->url());
    $request->server->set('SCRIPT_NAME', $GLOBALS['base_path'] . 'index.php');
    $request->server->set('SCRIPT_FILENAME', 'index.php');

    $response = $this->container->get('http_kernel')->handle($request, HttpKernelInterface::SUB_REQUEST);
    $view->setRequest($request);
    $view->setResponse($response);
    $view->initHandlers();

    $expected = $this->term1->id();
    $this->assertEqual($expected, $view->argument['tid']->getDefaultArgument());
  }

  /**
   * Tests escaping of page title when the taxonomy plugin provides it.
   */
  public function testTermTitleEscaping() {
    $this->term1->setName('<em>Markup</em>')->save();
    $this->drupalGet('taxonomy_default_argument_test/' . $this->term1->id());
    $this->assertEscaped($this->term1->label());
  }

}
