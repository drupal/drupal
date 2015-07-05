<?php

/**
 * @file
 * Contains \Drupal\early_rendering_controller_test\EarlyRenderingTestController.
 */

namespace Drupal\early_rendering_controller_test;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for early_rendering_test routes.
 *
 * The methods on this controller each correspond to a route for this module,
 * each of which exist solely for test cases in EarlyRenderingControllerTest;
 * see that test for documentation.
 *
 * @see core/modules/early_rendering_controller_test/early_rendering_controller_test.routing.yml
 * @see \Drupal\system\Tests\Common\EarlyRenderingControllerTest::testEarlyRendering()
 */
class EarlyRenderingTestController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a EarlyRenderingTestController.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  protected function earlyRenderContent() {
    return [
      '#markup' => 'Hello world!',
      '#cache' => [
        'tags' => [
          'foo',
        ],
      ],
    ];
  }

  public function renderArray() {
    return [
      '#pre_render' => [function () {
        $elements = $this->earlyRenderContent();
        return $elements;
      }],
    ];
  }

  public function renderArrayEarly() {
    $render_array = $this->earlyRenderContent();
    return [
      '#markup' => $this->renderer->render($render_array),
    ];
  }

  public function response() {
    return new Response('Hello world!');
  }

  public function responseEarly() {
    $render_array = $this->earlyRenderContent();
    return new Response($this->renderer->render($render_array));
  }

  public function responseWithAttachments() {
    return new AttachmentsTestResponse('Hello world!');
  }

  public function responseWithAttachmentsEarly() {
    $render_array = $this->earlyRenderContent();
    return new AttachmentsTestResponse($this->renderer->render($render_array));
  }

  public function cacheableResponse() {
    return new CacheableTestResponse('Hello world!');
  }

  public function cacheableResponseEarly() {
    $render_array = $this->earlyRenderContent();
    return new CacheableTestResponse($this->renderer->render($render_array));
  }

  public function domainObject() {
    return new TestDomainObject();
  }

  public function domainObjectEarly() {
    $render_array = $this->earlyRenderContent();
    $this->renderer->render($render_array);
    return new TestDomainObject();
  }

  public function domainObjectWithAttachments() {
    return new AttachmentsTestDomainObject();
  }

  public function domainObjectWithAttachmentsEarly() {
    $render_array = $this->earlyRenderContent();
    $this->renderer->render($render_array);
    return new AttachmentsTestDomainObject();
  }

  public function cacheableDomainObject() {
    return new CacheableTestDomainObject();
  }

  public function cacheableDomainObjectEarly() {
    $render_array = $this->earlyRenderContent();
    $this->renderer->render($render_array);
    return new CacheableTestDomainObject();
  }

}
