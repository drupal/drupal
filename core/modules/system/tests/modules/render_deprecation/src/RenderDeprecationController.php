<?php

namespace Drupal\render_deprecation;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Response;

class RenderDeprecationController implements ContainerAwareInterface {

  use ContainerAwareTrait;

  protected function renderArray() {
    return [
      'div' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'render-deprecation-test-result',
        ],
        'info' => [
          '#markup' => 'Hello.',
        ],
      ],
    ];
  }

  public function buildRenderFunction() {
    $build = $this->renderArray();
    $render = render($build);
    return Response::create($render);
  }

  public function buildRenderService() {
    $build = $this->renderArray();
    $render = $this->container->get('renderer')->render($build);
    return Response::create($render);
  }

}
