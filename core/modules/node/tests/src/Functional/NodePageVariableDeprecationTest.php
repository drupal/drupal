<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Entity\Entity\EntityViewMode;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests page variable deprecation.
 */
#[Group('node')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class NodePageVariableDeprecationTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'test_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'test_page_variable']);
    EntityViewMode::create([
      'id' => 'node.test_page_variable',
      'targetEntityType' => 'node',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'Test page variable',
    ])->save();
  }

  /**
   * Tests that deprecations are thrown correctly for the page variable.
   */
  public function testPageVariableDeprecation(): void {
    // Create a dummy node to skip node--1.html.twig in test_theme.
    $this->drupalCreateNode();
    $node = $this->drupalCreateNode(['type' => 'test_page_variable']);
    $this->expectDeprecation("'page' is deprecated in drupal:11.3.0 and is removed in drupal:13.0.0. Use 'view_mode' instead. See https://www.drupal.org/node/3458593");
    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextContains('The page variable is set');

    $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, 'test_page_variable');
    $output = (string) \Drupal::service('renderer')->renderRoot($build);
    $this->assertStringNotContainsString('The page variable is set', $output);
  }

}
