<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Core\Recipe;

use Drupal\contact\Entity\ContactForm;
use Drupal\FunctionalTests\Installer\InstallerTestBase;
use Drupal\shortcut\Entity\Shortcut;
use Drupal\Tests\standard\Traits\StandardTestTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

/**
 * Tests installing the Standard recipe via the installer.
 *
 * @group #slow
 * @group Recipe
 */
class StandardRecipeInstallTest extends InstallerTestBase {
  use StandardTestTrait {
    testStandard as doTestStandard;
  }
  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = '';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Skip permissions hardening so we can write a services file later.
    $this->settings['settings']['skip_permissions_hardening'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function visitInstaller(): void {
    // Use a URL to install from a recipe.
    $this->drupalGet($GLOBALS['base_url'] . '/core/install.php' . '?profile=&recipe=core/recipes/standard');
  }

  /**
   * {@inheritdoc}
   */
  public function testStandard(): void {
    if (!isset($this->rootUser->passRaw) && isset($this->rootUser->pass_raw)) {
      $this->rootUser->passRaw = $this->rootUser->pass_raw;
    }
    // These recipes provide functionality that is only optionally part of the
    // Standard profile, so we need to explicitly apply them.
    $this->applyRecipe('core/recipes/editorial_workflow');
    $this->applyRecipe('core/recipes/audio_media_type');
    $this->applyRecipe('core/recipes/document_media_type');
    $this->applyRecipe('core/recipes/image_media_type');
    $this->applyRecipe('core/recipes/local_video_media_type');
    $this->applyRecipe('core/recipes/remote_video_media_type');

    // Add a Home link to the main menu as Standard expects "Main navigation"
    // block on the page.
    $this->drupalGet('admin/structure/menu/manage/main/add');
    $this->submitForm([
      'title[0][value]' => 'Home',
      'link[0][uri]' => '<front>',
    ], 'Save');

    // Standard expects to set the contact form's recipient email to the
    // system's email address, but our feedback_contact_form recipe hard-codes
    // it to another value.
    // @todo This can be removed after https://drupal.org/i/3303126, which
    //   should make it possible for a recipe to reuse an already-set config
    //   value.
    ContactForm::load('feedback')?->setRecipients(['simpletest@example.com'])
      ->save();

    // Standard ships two shortcuts; ensure they exist.
    $this->assertCount(2, Shortcut::loadMultiple());

    // The installer logs you in.
    $this->drupalLogout();

    $this->doTestStandard();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpProfile(): void {
    // Noop. This form is skipped due the parameters set on the URL.
  }

  protected function installDefaultThemeFromClassProperty(ContainerInterface $container): void {
    // In this context a default theme makes no sense.
  }

  /**
   * {@inheritdoc}
   */
  protected function installResponsiveImage(): void {
    // Overrides StandardTest::installResponsiveImage() in order to use the
    // recipe.
    $this->applyRecipe('core/recipes/standard_responsive_images');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite(): void {
    $services_file = DRUPAL_ROOT . '/' . $this->siteDirectory . '/services.yml';
    // $content = file_get_contents($services_file);

    // Disable the super user access.
    $yaml = new SymfonyYaml();
    $services = [];
    $services['parameters']['security.enable_super_user'] = FALSE;
    file_put_contents($services_file, $yaml->dump($services));
    parent::setUpSite();
  }

}
