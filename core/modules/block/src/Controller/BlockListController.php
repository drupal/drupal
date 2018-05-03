<?php

namespace Drupal\block\Controller;

use Drupal\Core\Entity\Controller\EntityListController;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines a controller to list blocks.
 */
class BlockListController extends EntityListController {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs the BlockListController.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler')
    );
  }

  /**
   * Shows the block administration page.
   *
   * @param string|null $theme
   *   Theme key of block list.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function listing($theme = NULL, Request $request = NULL) {
    $theme = $theme ?: $this->config('system.theme')->get('default');
    if (!$this->themeHandler->hasUi($theme)) {
      throw new NotFoundHttpException();
    }

    return $this->entityManager()->getListBuilder('block')->render($theme, $request);
  }

}
