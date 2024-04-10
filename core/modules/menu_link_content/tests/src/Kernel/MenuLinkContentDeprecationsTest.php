<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_link_content\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Form\MenuLinkContentForm;

/**
 * Tests the deprecation notices of the menu_link_content module.
 *
 * @group menu_link_content
 * @group legacy
 */
class MenuLinkContentDeprecationsTest extends KernelTestBase {

  /**
   * Tests the deprecation in the \Drupal\menu_link_content\Form\MenuLinkContentForm constructor.
   */
  public function testMenuLinkContentFormConstructorDeprecation(): void {
    $entity_repository = $this->prophesize(EntityRepositoryInterface::class);
    $menu_parent_form_selector = $this->prophesize(MenuParentFormSelectorInterface::class);
    $language_manager = $this->prophesize(LanguageManagerInterface::class);
    $path_validator = $this->prophesize(PathValidatorInterface::class);
    $entity_type_bundle_info = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $time = $this->prophesize(TimeInterface::class);
    $this->expectDeprecation('Calling Drupal\menu_link_content\Form\MenuLinkContentForm::__construct() with the $language_manager argument is deprecated in drupal:10.2.0 and is removed in drupal:11.0.0. See https://www.drupal.org/node/3325178');
    new MenuLinkContentForm(
      $entity_repository->reveal(),
      $menu_parent_form_selector->reveal(),
      $language_manager->reveal(),
      $path_validator->reveal(),
      $entity_type_bundle_info->reveal(),
      $time->reveal()
    );
  }

}
