<?php

declare(strict_types=1);

namespace Drupal\batch_test;

use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Batch callbacks using dependency injection for testing batches.
 */
class BatchInjectionCallbacks {
  use StringTranslationTrait;

  public function __construct(
    protected readonly ThemeManagerInterface $themeManager,
    protected readonly RouteMatchInterface $routeMatch,
    protected readonly RequestStack $requestStack,
    protected readonly TitleResolverInterface $titleResolver,
  ) {}

  /**
   * Implements callback_batch_operation().
   *
   * Tests the progress page theme.
   */
  public function themeCallback(): void {
    $batch_test_helper = new BatchTestHelper();
    // Because drupalGet() steps through the full progressive batch before
    // returning control to the test function, we cannot test that the correct
    // theme is being used on the batch processing page by viewing that page
    // directly. Instead, we save the theme being used in a variable here, so
    // that it can be loaded and inspected in the thread running the test.
    $theme = $this->themeManager->getActiveTheme()->getName();
    $batch_test_helper->stack($theme);
  }

  /**
   * Tests the title on the progress page by performing a batch callback.
   */
  public function titleCallback(): void {
    $batch_test_helper = new BatchTestHelper();
    // Because drupalGet() steps through the full progressive batch before
    // returning control to the test function, we cannot test that the correct
    // title is being used on the batch processing page by viewing that page
    // directly. Instead, we save the title being used in a variable here, so
    // that it can be loaded and inspected in the thread running the test.
    $title = $this->titleResolver->getTitle($this->requestStack->getCurrentRequest(), $this->routeMatch->getRouteObject());
    $batch_test_helper->stack($title);
  }

}
