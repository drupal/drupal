<?php

declare(strict_types=1);

namespace Drupal\Tests\big_pipe\Unit\Render;

use Drupal\big_pipe\Render\BigPipeResponse;
use Drupal\big_pipe\Render\BigPipeResponseAttachmentsProcessor;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\Random;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\big_pipe\Render\BigPipeResponseAttachmentsProcessor
 * @group big_pipe
 */
class BigPipeResponseAttachmentsProcessorTest extends UnitTestCase {

  /**
   * @covers ::processAttachments
   *
   * @dataProvider nonHtmlResponseProvider
   */
  public function testNonHtmlResponse($response_class): void {
    $big_pipe_response_attachments_processor = $this->createBigPipeResponseAttachmentsProcessor($this->prophesize(AttachmentsResponseProcessorInterface::class));

    $non_html_response = new $response_class();
    $this->expectException(\AssertionError::class);
    $big_pipe_response_attachments_processor->processAttachments($non_html_response);
  }

  public static function nonHtmlResponseProvider() {
    return [
      'AjaxResponse, which implements AttachmentsInterface' => [AjaxResponse::class],
      'A dummy that implements AttachmentsInterface' => [get_class((new Prophet())->prophesize(AttachmentsInterface::class)->reveal())],
    ];
  }

  /**
   * @covers ::processAttachments
   *
   * @dataProvider attachmentsProvider
   */
  public function testHtmlResponse(array $attachments): void {
    $big_pipe_response = new BigPipeResponse(new HtmlResponse('original'));
    $big_pipe_response->setAttachments($attachments);

    // This mock is the main expectation of this test: verify that the decorated
    // service (that is this mock) never receives BigPipe placeholder
    // attachments, because it doesn't know (nor should it) how to handle them.
    $html_response_attachments_processor = $this->prophesize(AttachmentsResponseProcessorInterface::class);
    $html_response_attachments_processor->processAttachments(Argument::that(function ($response) {
      return $response instanceof HtmlResponse && empty(array_intersect(['big_pipe_placeholders', 'big_pipe_nojs_placeholders'], array_keys($response->getAttachments())));
    }))
      ->will(function ($args) {
        /** @var \Symfony\Component\HttpFoundation\Response|\Drupal\Core\Render\AttachmentsInterface $response */
        $response = $args[0];
        // Simulate its actual behavior.
        $attachments = array_diff_key($response->getAttachments(), ['html_response_attachment_placeholders' => TRUE]);
        $response->setContent('processed');
        $response->setAttachments($attachments);
        return $response;
      })
      ->shouldBeCalled();

    $big_pipe_response_attachments_processor = $this->createBigPipeResponseAttachmentsProcessor($html_response_attachments_processor);
    $processed_big_pipe_response = $big_pipe_response_attachments_processor->processAttachments($big_pipe_response);

    // The secondary expectation of this test: the original (passed in) response
    // object remains unchanged, the processed (returned) response object has
    // the expected values.
    $this->assertSame($attachments, $big_pipe_response->getAttachments(), 'Attachments of original response object MUST NOT be changed.');
    $this->assertEquals('original', $big_pipe_response->getContent(), 'Content of original response object MUST NOT be changed.');
    $this->assertEquals(array_diff_key($attachments, ['html_response_attachment_placeholders' => TRUE]), $processed_big_pipe_response->getAttachments(), 'Attachments of returned (processed) response object MUST be changed.');
    $this->assertEquals('processed', $processed_big_pipe_response->getContent(), 'Content of returned (processed) response object MUST be changed.');
  }

  public static function attachmentsProvider() {
    $typical_cases = [
      'no attachments' => [[]],
      'libraries' => [['library' => ['core/drupal']]],
      'libraries + drupalSettings' => [['library' => ['core/drupal'], 'drupalSettings' => ['foo' => 'bar']]],
    ];

    $official_attachment_types = ['html_head', 'feed', 'html_head_link', 'http_header', 'library', 'placeholders', 'drupalSettings', 'html_response_attachment_placeholders'];
    $official_attachments_with_random_values = [];
    foreach ($official_attachment_types as $type) {
      $official_attachments_with_random_values[$type] = Random::machineName();
    }
    $random_attachments = ['random' . Random::machineName() => Random::machineName()];
    $edge_cases = [
      'all official attachment types, with random assigned values, even if technically not valid, to prove BigPipeResponseAttachmentsProcessor is a perfect decorator' => [$official_attachments_with_random_values],
      'random attachment type (unofficial), with random assigned value, to prove BigPipeResponseAttachmentsProcessor is a perfect decorator' => [$random_attachments],
    ];

    $big_pipe_placeholder_attachments = ['big_pipe_placeholders' => [Random::machineName()]];
    $big_pipe_nojs_placeholder_attachments = ['big_pipe_nojs_placeholders' => [Random::machineName()]];
    $big_pipe_cases = [
      'only big_pipe_placeholders' => [$big_pipe_placeholder_attachments],
      'only big_pipe_nojs_placeholders' => [$big_pipe_nojs_placeholder_attachments],
      'big_pipe_placeholders + big_pipe_nojs_placeholders' => [$big_pipe_placeholder_attachments + $big_pipe_nojs_placeholder_attachments],
    ];

    $combined_cases = [
      'all official attachment types + big_pipe_placeholders + big_pipe_nojs_placeholders' => [$official_attachments_with_random_values + $big_pipe_placeholder_attachments + $big_pipe_nojs_placeholder_attachments],
      'random attachment types + big_pipe_placeholders + big_pipe_nojs_placeholders' => [$random_attachments + $big_pipe_placeholder_attachments + $big_pipe_nojs_placeholder_attachments],
    ];

    return $typical_cases + $edge_cases + $big_pipe_cases + $combined_cases;
  }

  /**
   * Creates a BigPipeResponseAttachmentsProcessor with mostly dummies.
   *
   * @param \Prophecy\Prophecy\ObjectProphecy $decorated_html_response_attachments_processor
   *   An object prophecy implementing AttachmentsResponseProcessorInterface.
   *
   * @return \Drupal\big_pipe\Render\BigPipeResponseAttachmentsProcessor
   *   The BigPipeResponseAttachmentsProcessor to test.
   */
  protected function createBigPipeResponseAttachmentsProcessor(ObjectProphecy $decorated_html_response_attachments_processor) {
    return new BigPipeResponseAttachmentsProcessor(
      $decorated_html_response_attachments_processor->reveal(),
      $this->prophesize(AssetResolverInterface::class)->reveal(),
      $this->prophesize(ConfigFactoryInterface::class)->reveal(),
      $this->prophesize(AssetCollectionRendererInterface::class)->reveal(),
      $this->prophesize(AssetCollectionRendererInterface::class)->reveal(),
      $this->prophesize(RequestStack::class)->reveal(),
      $this->prophesize(RendererInterface::class)->reveal(),
      $this->prophesize(ModuleHandlerInterface::class)->reveal(),
      $this->prophesize(LanguageManagerInterface::class)->reveal()
    );
  }

}
