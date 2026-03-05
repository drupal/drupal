<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\taxonomy\Entity\Vocabulary;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests page variable deprecation.
 */
#[Group('taxonomy_term')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class TaxonomyTermPageVariableDeprecationTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'test_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createVocabulary(['vid' => 'test_page_variable']);
    EntityViewMode::create([
      'id' => 'taxonomy_term.test_page_variable',
      'targetEntityType' => 'taxonomy_term',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'Test page variable',
    ])->save();
  }

  /**
   * Tests that deprecations are thrown correctly for the page variable.
   */
  public function testPageVariableDeprecation(): void {
    $term = $this->createTerm(Vocabulary::load('test_page_variable'));
    $this->expectDeprecation("'page' is deprecated in drupal:11.3.0 and is removed in drupal:13.0.0. Use 'view_mode' instead. See https://www.drupal.org/node/3542527");
    $this->drupalGet($term->toUrl());
    $this->assertSession()->pageTextContains('The page variable is set');

    $build = \Drupal::entityTypeManager()->getViewBuilder('taxonomy_term')->view($term, 'test_page_variable');
    $output = (string) \Drupal::service('renderer')->renderRoot($build);
    $this->assertStringNotContainsString('The page variable is set', $output);
  }

}
