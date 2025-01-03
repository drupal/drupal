<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_link_content\Kernel\Migrate;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\MenuLinkContentInterface;

/**
 * Provides assertions for testing MenuLinkContent.
 */
trait MigrateMenuLinkTestTrait {

  /**
   * Asserts various aspects of a menu link entity.
   *
   * @param int $id
   *   The link ID.
   * @param string $langcode
   *   The language of the menu link.
   * @param string $title
   *   The expected title of the link.
   * @param string $menu
   *   The expected ID of the menu to which the link will belong.
   * @param string|null $description
   *   The link's expected description.
   * @param bool $enabled
   *   Whether the link is enabled.
   * @param bool $expanded
   *   Whether the link is expanded.
   * @param array $attributes
   *   Additional attributes the link is expected to have.
   * @param string $uri
   *   The expected URI of the link.
   * @param int $weight
   *   The expected weight of the link.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface
   *   The menu link content.
   */
  protected function assertEntity($id, $langcode, $title, $menu, $description, $enabled, $expanded, array $attributes, $uri, $weight) {
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
    $menu_link = MenuLinkContent::load($id);
    $menu_link = $menu_link->getTranslation($langcode);
    $this->assertInstanceOf(MenuLinkContentInterface::class, $menu_link);
    $this->assertSame($title, $menu_link->getTitle());
    $this->assertSame($langcode, $menu_link->language()->getId());
    $this->assertSame($menu, $menu_link->getMenuName());
    $this->assertSame($description, $menu_link->getDescription());
    $this->assertSame($enabled, $menu_link->isEnabled());
    $this->assertSame($expanded, $menu_link->isExpanded());
    $this->assertSame($attributes, $menu_link->link->options);
    $this->assertSame($uri, $menu_link->link->uri);
    $this->assertSame($weight, $menu_link->getWeight());
    return $menu_link;
  }

}
