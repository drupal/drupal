<?php

namespace Drupal\modernizr_deprecation_test\Controller;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

use Drupal\Core\Extension\ModuleExtensionList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A page to facilitate library loading.
 */
class LoadLibraryController implements ContainerInjectionInterface {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * A simple page with a library attached.
   *
   * @return array
   *   A render array.
   */
  public function build($extension, $library) {
    // Confirm the extension and library are valid before proceeding.
    $extension_list = array_keys($this->moduleExtensionList->getList());
    array_push($extension_list, 'core');
    assert(in_array($extension, $extension_list), "Extension $extension not available.");
    $available_libraries = $this->libraryDiscovery->getLibrariesByExtension($extension);
    assert(isset($available_libraries[$library]), "Library $library not available in extension $extension");

    $build = [
      '#markup' => "Attaching $extension/$library",
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    $build['#attached']['library'][] = "$extension/$library";
    if ($library === 'modernizr') {
      $build['#attached']['library'][] = 'modernizr_deprecation_test/access_unsupported_property';
    }

    return $build;
  }

  /**
   * Constructs a new LoadLibraryController.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   */
  public function __construct(ModuleExtensionList $module_extension_list, LibraryDiscoveryInterface $library_discovery) {
    $this->moduleExtensionList = $module_extension_list;
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.list.module'),
      $container->get('library.discovery')
    );
  }

}
