<?php

namespace Drupal\Tests\menu_link_content\Unit\Plugin\migrate\process\d6;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Url;
use Drupal\menu_link_content\Plugin\migrate\process\d6\LinkUri;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidator;

/**
 * Tests \Drupal\menu_link_content\Plugin\migrate\process\d6\LinkUri.
 *
 * @group menu_link_content
 *
 * @coversDefaultClass \Drupal\menu_link_content\Plugin\migrate\process\d6\LinkUri
 */
class LinkUriTest extends UnitTestCase {

  /**
   * The entity type manager prophecy used in the test.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The 'link_uri' process plugin being tested.
   *
   * @var \Drupal\menu_link_content\Plugin\migrate\process\d6\LinkUri
   */
  protected $processPlugin;

  /**
   * The path validator prophecy used in the test.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * The fake entity type ID used in the test.
   *
   * @var string
   */
  protected $entityTypeId = 'the_entity_type_id';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeManager->getDefinitions()->willReturn([$this->entityTypeId => '']);
    $this->processPlugin = new LinkUri([], 'link_uri', [], $this->entityTypeManager->reveal());

    // Url::fromInternalUri() accesses the path validator from the global
    // container.
    // @see \Drupal\Core\Url::fromInternalUri()
    $this->pathValidator = $this->prophesize(PathValidator::class);
    $container = new ContainerBuilder();
    $container->set('path.validator', $this->pathValidator->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests LinkUri::transform().
   *
   * @param array $value
   *   The value to pass to LinkUri::transform().
   * @param string $expected
   *   The expected return value of LinkUri::transform().
   * @param \Drupal\Core\Url $url
   *   (optional) The URL that the path validator prophecy will return.
   *
   * @dataProvider providerTestTransform
   *
   * @covers ::transform
   */
  public function testTransform(array $value, $expected, Url $url = NULL) {
    $migrate_executable = $this->prophesize(MigrateExecutableInterface::class);
    $row = $this->prophesize(Row::class);

    if ($url) {
      $this->pathValidator->getUrlIfValidWithoutAccessCheck(reset($value))->willReturn($url);
    }

    $actual = $this->processPlugin->transform($value, $migrate_executable->reveal(), $row->reveal(), 'link/uri');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Provides test cases for LinkUriTest::testTransform().
   *
   * @return array
   *   An array of test cases, each which the following values:
   *   - The value array to pass to LinkUri::transform().
   *   - The expected path returned by LinkUri::transform().
   *   - (optional) A URL object that the path validator prophecy will return.
   */
  public function providerTestTransform() {
    $tests = [];

    $value = ['http://example.com'];
    $expected = 'http://example.com';
    $tests['with_scheme'] = [$value, $expected];

    $value = ['/test'];
    $expected = 'internal:/test';
    $tests['leading_slash'] = [$value, $expected];

    $value = ['test'];
    $expected = 'internal:/test';
    $tests['without_scheme'] = [$value, $expected];

    $url = Url::fromRoute('route_name');
    $tests['with_route'] = [$value, $expected, $url];

    $url = Url::fromRoute('entity.not_an_entity_type_id.canonical');
    $tests['without_entity_type'] = [$value, $expected, $url];

    $url = Url::fromRoute('entity.the_entity_type_id.canonical');
    $tests['without_route_parameter'] = [$value, $expected, $url];

    $url = Url::fromRoute('entity.the_entity_type_id.canonical', ['the_entity_type_id' => 'the_entity_id']);
    $expected = 'entity:the_entity_type_id/the_entity_id';
    $tests['entity_path'] = [$value, $expected, $url];

    return $tests;
  }

}
