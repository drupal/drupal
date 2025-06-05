<?php

declare(strict_types=1);

// cspell:ignore Abre er bânnêh en una nueba bentana la mîmma

namespace Drupal\KernelTests\Components;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\locale\StringInterface;
use Drupal\locale\StringStorageInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * Tests the component can be translated.
 *
 * @group sdc
 */
class ComponentTranslationTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'sdc_test',
    'locale',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['sdc_theme_test'];

  /**
   * The locale storage.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected StringStorageInterface $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Add a default locale storage for all these tests.
    $this->storage = $this->container->get('locale.storage');
    ConfigurableLanguage::createFromLangcode('epa')->save();
    $this->container->get('string_translation')->setDefaultLangcode('epa');
    $this->installSchema('locale', [
      'locales_location',
      'locales_source',
      'locales_target',
    ]);
  }

  /**
   * Test that components render enum props correctly with their translations.
   */
  public function testEnumPropsCanBeTranslated(): void {
    $bannerString = $this->buildSourceString(['source' => 'Open in a new window', 'context' => 'Banner link target']);
    $bannerString->save();
    $ctaString = $this->buildSourceString(['source' => 'Open in a new window', 'context' => 'CTA link target']);
    $ctaString->save();
    $ctaEmptyString = $this->buildSourceString(['source' => 'Open in same window', 'context' => 'CTA link target']);
    $ctaEmptyString->save();
    $this->createTranslation($bannerString, 'epa', ['translation' => 'Abre er bânnêh en una nueba bentana']);
    $this->createTranslation($ctaString, 'epa', ['translation' => 'Abre er CTA en una nueba bentana']);
    $this->createTranslation($ctaEmptyString, 'epa', ['translation' => 'Abre er CTA en la mîmma bentana']);

    $build = [
      'banner' => [
        '#type' => 'component',
        '#component' => 'sdc_test:my-banner',
        '#props' => [
          'heading' => 'I am a banner',
          'ctaText' => 'Click me',
          'ctaHref' => 'https://www.example.org',
          'ctaTarget' => '_blank',
        ],
      ],
      'cta' => [
        '#type' => 'component',
        '#component' => 'sdc_test:my-cta',
        '#props' => [
          'text' => 'Click me',
          'href' => 'https://www.example.org',
          'target' => '_blank',
        ],
      ],
      'cta_with_empty_enum' => [
        '#type' => 'component',
        '#component' => 'sdc_test:my-cta',
        '#props' => [
          'text' => 'Click me',
          'href' => 'https://www.example.org',
          'target' => '',
        ],
      ],
    ];
    \Drupal::state()->set('sdc_test_component', $build);
    $response = $this->request(Request::create('sdc-test-component'));
    $crawler = new Crawler($response->getContent());

    // Assert that even if the source is the same, the translations depend on
    // the enum context.
    $this->assertStringContainsString('Abre er bânnêh en una nueba bentana', $crawler->filter('#sdc-wrapper [data-component-id="sdc_test:my-banner"]')->outerHtml());
    $this->assertStringContainsString('Abre er CTA en una nueba bentana', $crawler->filter('#sdc-wrapper a[data-component-id="sdc_test:my-cta"]:nth-of-type(1)')->outerHtml());
    $this->assertStringContainsString('Abre er CTA en la mîmma bentana', $crawler->filter('#sdc-wrapper a[data-component-id="sdc_test:my-cta"]:nth-of-type(2)')->outerHtml());
  }

  /**
   * Creates random source string object.
   *
   * @param array $values
   *   The values array.
   *
   * @return \Drupal\locale\StringInterface
   *   A locale string.
   */
  protected function buildSourceString(array $values = []): StringInterface {
    return $this->storage->createString($values += [
      'source' => $this->randomMachineName(100),
      'context' => $this->randomMachineName(20),
    ]);
  }

  /**
   * Creates single translation for source string.
   *
   * @param \Drupal\locale\StringInterface $source
   *   The source string.
   * @param string $langcode
   *   The language code.
   * @param array $values
   *   The values array.
   *
   * @return \Drupal\locale\StringInterface
   *   The translated string object.
   */
  protected function createTranslation(StringInterface $source, $langcode, array $values = []): StringInterface {
    return $this->storage->createTranslation($values + [
      'lid' => $source->lid,
      'language' => $langcode,
      'translation' => $this->randomMachineName(100),
    ])->save();
  }

  /**
   * Passes a request to the HTTP kernel and returns a response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  protected function request(Request $request): Response {
    // @todo We should replace this when https://drupal.org/i/3390193 lands.
    // Reset the request stack.
    // \Drupal\KernelTests\KernelTestBase::bootKernel() pushes a bogus request
    // to boot the kernel, but it is also needed for any URL generation in tests
    // to work. We also need to reset the request stack every time we make a
    // request.
    $request_stack = $this->container->get('request_stack');
    while ($request_stack->getCurrentRequest() !== NULL) {
      $request_stack->pop();
    }

    $http_kernel = $this->container->get('http_kernel');
    self::assertInstanceOf(HttpKernelInterface::class, $http_kernel);
    $response = $http_kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, FALSE);
    $content = $response->getContent();
    self::assertNotFalse($content);
    $this->setRawContent($content);

    self::assertInstanceOf(TerminableInterface::class, $http_kernel);
    $http_kernel->terminate($request, $response);

    return $response;
  }

}
