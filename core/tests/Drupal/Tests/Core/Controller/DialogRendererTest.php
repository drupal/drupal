<?php

namespace Drupal\Tests\Core\Controller;

use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Render\MainContent\DialogRenderer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Core\Render\MainContent\DialogRenderer
 * @group Ajax
 */
class DialogRendererTest extends UnitTestCase {

  /**
   * @group legacy
   * @expectedDeprecation The renderer service must be passed to Drupal\Core\Render\MainContent\DialogRenderer::__construct and will be required before Drupal 9.0.0. See https://www.drupal.org/node/3009400
   */
  public function testConstructorRendererArgument() {
    $title_resolver = $this->createMock(TitleResolverInterface::class);
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())
      ->method('get')
      ->with('renderer')
      ->willReturn(NULL);
    \Drupal::setContainer($container);
    new DialogRenderer($title_resolver);
  }

}
