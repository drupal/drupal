<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\Core\Link;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests term hierarchy.
 */
#[Group('taxonomy')]
#[RunTestsInSeparateProcesses]
class TermHierarchyTest extends KernelTestBase {

  use UserCreationTrait;
  use BlockCreationTrait;
  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'block',
    'node',
    'filter',
    'text',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['filter']);
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');

    $this->container->get('theme_installer')->install(['stark']);
    $this->container->get('theme.manager')->setActiveTheme(
      $this->container->get('theme.initialization')->initTheme('stark')
    );

    $this->placeBlock('system_breadcrumb_block', [
      'region' => 'content',
      'theme' => 'stark',
    ]);

    $this->setUpCurrentUser(permissions: [
      'administer taxonomy',
      'bypass node access',
    ]);
  }

  /**
   * Tests that there is a link to the parent term on the child term page.
   */
  public function testTaxonomyTermHierarchyBreadcrumbs(): void {
    $vocabulary = $this->createVocabulary();

    // Create two taxonomy terms and set term2 as the parent of term1.
    $term1 = $this->createTerm($vocabulary);
    $term2 = $this->createTerm($vocabulary);
    $term1->parent = [$term2->id()];
    $term1->save();

    // Verify that the page breadcrumbs include a link to the parent term.
    $this->drupalGet('taxonomy/term/' . $term1->id());
    // Breadcrumbs are not rendered with a language, prevent the term
    // language from being added to the options.
    // Check that parent term link is displayed when viewing the node.
    $this->assertSession()->responseContains(Link::fromTextAndUrl($term2->getName(), $term2->toUrl('canonical', ['language' => NULL]))->toString());
  }

}
