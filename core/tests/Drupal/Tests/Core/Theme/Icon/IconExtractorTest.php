<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Theme\Icon\Attribute\IconExtractor;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Theme\Icon\Attribute\IconExtractor
 *
 * @group icon
 */
class IconExtractorTest extends UnitTestCase {

  /**
   * The container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  private ContainerBuilder $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->createMock(TranslationInterface::class));
    \Drupal::setContainer($this->container);
  }

  /**
   * Test the IconExtractor::_construct method.
   */
  public function testConstruct(): void {
    $plugin = new IconExtractor(
      'foo',
      new TranslatableMarkup('Foo'),
      new TranslatableMarkup('Foo description'),
      NULL,
      ['bar' => 'baz'],
    );
    $plugin->setProvider('example');
    $this->assertEquals('example', $plugin->getProvider());
    $this->assertEquals('foo', $plugin->getId());

    $plugin->setClass('\Drupal\Foo');
    $this->assertEquals('\Drupal\Foo', $plugin->getClass());

    $this->assertEquals('Foo', $plugin->label->getUntranslatedString());
    $this->assertSame('Foo description', $plugin->description->getUntranslatedString());
    $this->assertSame(['bar' => 'baz'], $plugin->forms);
  }

}
