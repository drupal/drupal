<?php

declare(strict_types=1);

namespace Drupal\router_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\Routing\Attribute\DeprecatedAlias;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Test controller for method-level Route attributes.
 */
class TestAttributes extends ControllerBase {

  #[Route('/test_method_attribute', name: 'router_test.method_attribute', requirements: ['_access' => 'TRUE'], alias: 'router_test.alias_test')]
  #[Route('/test_method_attribute-other-path', name: 'router_test.method_attribute_other', requirements: ['_access' => 'TRUE'])]
  public function attributeMethod(): array {
    return ['#markup' => 'Testing method with a Route attribute'];
  }

  #[Route(
    path: '/test_all_properties/{parameter}',
    name: 'router_test.all_properties',
    requirements: ['_access' => 'TRUE', 'parameter' => '\d+'],
    options: ['_admin_route' => TRUE, 'utf8' => TRUE],
    defaults: ['_title' => 'Test all properties', 'parameter' => '1'],
    host: '{subdomain}.example.com',
    methods: ['GET', 'POST'],
    schemes: ['https'],
    priority: 10,
    alias: [
      'router_test.all_properties_alias',
      new DeprecatedAlias(
        aliasName: 'router_test.all_properties_deprecated',
        package: 'drupal/core',
        version: 'X.0.0',
        message: 'The "%alias_id%" route is deprecated.',
      ),
    ],
  )]
  public function allProperties(): array {
    return ['#markup' => 'Testing route with all properties'];
  }

}
