<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\media\Entity\MediaType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests installing from a profile that also applies recipes.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
class ProfileWithRecipesTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_with_recipes';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the recipes listed by the profile were applied.
   */
  public function testRecipeWasApplied(): void {
    // The profile specified a module, which should have been installed.
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('workflows'));
    // It also applied a media type recipe.
    $this->assertInstanceOf(MediaType::class, MediaType::load('audio'));
  }

}
