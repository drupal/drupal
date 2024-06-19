<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Views;

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
  public static $testViews = ['taxonomy_default_argument_test'];

  /**
   * Init view with a request by provided URL.
   *
   * @param string $request_url
   *   The requested URL.
   * @param string $view_name
   *   The name of the view.
   *
   * @return \Drupal\views\ViewExecutable
   *   The initiated view.
   *
   * @throws \Exception
   */
  protected function initViewWithRequest($request_url, $view_name = 'taxonomy_default_argument_test') {
    $view = Views::getView($view_name);

    $request = Request::create($request_url);
    $request->server->set('SCRIPT_NAME', $GLOBALS['base_path'] . 'index.php');
    $request->server->set('SCRIPT_FILENAME', 'index.php');

    $response = $this->container->get('http_kernel')
      ->handle($request, HttpKernelInterface::SUB_REQUEST);

    $view->setRequest($request);
    $view->setResponse($response);
    $view->initHandlers();

    return $view;
  }

  /**
   * Tests the relationship.
   */
  public function testNodePath(): void {
    $view = $this->initViewWithRequest($this->nodes[0]->toUrl()->toString());

    $expected = implode(',', [$this->term1->id(), $this->term2->id()]);
    $this->assertEquals($expected, $view->argument['tid']->getDefaultArgument());
    $this->assertEquals($this->nodes[0]->getCacheTags(), $view->argument['tid']->getPlugin('argument_default')->getCacheTags());
    $view->destroy();
  }

  public function testNodePathWithViewSelection(): void {
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

    $view = $this->initViewWithRequest($this->nodes[0]->toUrl()->toString());

    $expected = implode(',', [$this->term1->id(), $this->term2->id()]);
    $this->assertEquals($expected, $view->argument['tid']->getDefaultArgument());
    $this->assertEquals($this->nodes[0]->getCacheTags(), $view->argument['tid']->getPlugin('argument_default')->getCacheTags());
  }

  public function testTermPath(): void {
    $view = $this->initViewWithRequest($this->term1->toUrl()->toString());

    $expected = $this->term1->id();
    $this->assertEquals($expected, $view->argument['tid']->getDefaultArgument());
    $this->assertEmpty($view->argument['tid']->getPlugin('argument_default')->getCacheTags());
  }

}
