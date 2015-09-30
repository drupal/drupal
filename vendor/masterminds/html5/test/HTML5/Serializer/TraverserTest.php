<?php
namespace Masterminds\HTML5\Tests\Serializer;

use Masterminds\HTML5\Serializer\OutputRules;
use Masterminds\HTML5\Serializer\Traverser;
use Masterminds\HTML5\Parser;

class TraverserTest extends \Masterminds\HTML5\Tests\TestCase
{

    protected $markup = '<!doctype html>
    <html lang="en">
      <head>
        <meta charset="utf-8">
        <title>Test</title>
      </head>
      <body>
        <p>This is a test.</p>
      </body>
    </html>';

    public function setUp()
    {
        $this->html5 = $this->getInstance();
    }

    /**
     * Using reflection we make a protected method accessible for testing.
     *
     * @param string $name
     *            The name of the method on the Traverser class to test.
     *
     * @return \ReflectionMethod \ReflectionMethod for the specified method
     */
    public function getProtectedMethod($name)
    {
        $class = new \ReflectionClass('\Masterminds\HTML5\Serializer\Traverser');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function getTraverser()
    {
        $stream = fopen('php://temp', 'w');

        $dom = $this->html5->loadHTML($this->markup);
        $t = new Traverser($dom, $stream, $html5->getOptions());

        // We return both the traverser and stream so we can pull from it.
        return array(
            $t,
            $stream
        );
    }

    public function testConstruct()
    {
        // The traverser needs a place to write the output to. In our case we
        // use a stream in temp space.
        $stream = fopen('php://temp', 'w');

        $html5 = $this->getInstance();

        $r = new OutputRules($stream, $this->html5->getOptions());
        $dom = $this->html5->loadHTML($this->markup);

        $t = new Traverser($dom, $stream, $r, $html5->getOptions());

        $this->assertInstanceOf('\Masterminds\HTML5\Serializer\Traverser', $t);
    }

    public function testFragment()
    {
        $html = '<span class="bar">foo</span><span></span><div>bar</div>';
        $input = new \Masterminds\HTML5\Parser\StringInputStream($html);
        $dom = $this->html5->parseFragment($input);

        $this->assertInstanceOf('\DOMDocumentFragment', $dom);

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $out = $t->walk();
        $this->assertEquals($html, stream_get_contents($stream, - 1, 0));
    }

    public function testProcessorInstruction()
    {
        $html = '<?foo bar ?>';
        $input = new \Masterminds\HTML5\Parser\StringInputStream($html);
        $dom = $this->html5->parseFragment($input);

        $this->assertInstanceOf('\DOMDocumentFragment', $dom);

        $stream = fopen('php://temp', 'w');
        $r = new OutputRules($stream, $this->html5->getOptions());
        $t = new Traverser($dom, $stream, $r, $this->html5->getOptions());

        $out = $t->walk();
        $this->assertEquals($html, stream_get_contents($stream, - 1, 0));
    }
}
