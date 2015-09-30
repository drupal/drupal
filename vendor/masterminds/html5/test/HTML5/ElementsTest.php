<?php
namespace Masterminds\HTML5\Tests;

use Masterminds\HTML5\Elements;

class ElementsTest extends TestCase
{

    public $html5Elements = array(
        "a",
        "abbr",
        "address",
        "area",
        "article",
        "aside",
        "audio",
        "b",
        "base",
        "bdi",
        "bdo",
        "blockquote",
        "body",
        "br",
        "button",
        "canvas",
        "caption",
        "cite",
        "code",
        "col",
        "colgroup",
        "command",
        // "data",
        "datalist",
        "dd",
        "del",
        "details",
        "dfn",
        "dialog",
        "div",
        "dl",
        "dt",
        "em",
        "embed",
        "fieldset",
        "figcaption",
        "figure",
        "footer",
        "form",
        "h1",
        "h2",
        "h3",
        "h4",
        "h5",
        "h6",
        "head",
        "header",
        "hgroup",
        "hr",
        "html",
        "i",
        "iframe",
        "img",
        "input",
        "ins",
        "kbd",
        "keygen",
        "label",
        "legend",
        "li",
        "link",
        "map",
        "mark",
        "menu",
        "meta",
        "meter",
        "nav",
        "noscript",
        "object",
        "ol",
        "optgroup",
        "option",
        "output",
        "p",
        "param",
        "pre",
        "progress",
        "q",
        "rp",
        "rt",
        "ruby",
        "s",
        "samp",
        "script",
        "section",
        "select",
        "small",
        "source",
        "span",
        "strong",
        "style",
        "sub",
        "summary",
        "sup",
        "table",
        "tbody",
        "td",
        "textarea",
        "tfoot",
        "th",
        "thead",
        "time",
        "title",
        "tr",
        "track",
        "u",
        "ul",
        "var",
        "video",
        "wbr"
    );

    public $mathmlElements = array(
        "maction",
        "maligngroup",
        "malignmark",
        "math",
        "menclose",
        "merror",
        "mfenced",
        "mfrac",
        "mglyph",
        "mi",
        "mlabeledtr",
        "mlongdiv",
        "mmultiscripts",
        "mn",
        "mo",
        "mover",
        "mpadded",
        "mphantom",
        "mroot",
        "mrow",
        "ms",
        "mscarries",
        "mscarry",
        "msgroup",
        "msline",
        "mspace",
        "msqrt",
        "msrow",
        "mstack",
        "mstyle",
        "msub",
        "msup",
        "msubsup",
        "mtable",
        "mtd",
        "mtext",
        "mtr",
        "munder",
        "munderover"
    );

    public $svgElements = array(
        "a",
        "altGlyph",
        "altGlyphDef",
        "altGlyphItem",
        "animate",
        "animateColor",
        "animateMotion",
        "animateTransform",
        "circle",
        "clipPath",
        "color-profile",
        "cursor",
        "defs",
        "desc",
        "ellipse",
        "feBlend",
        "feColorMatrix",
        "feComponentTransfer",
        "feComposite",
        "feConvolveMatrix",
        "feDiffuseLighting",
        "feDisplacementMap",
        "feDistantLight",
        "feFlood",
        "feFuncA",
        "feFuncB",
        "feFuncG",
        "feFuncR",
        "feGaussianBlur",
        "feImage",
        "feMerge",
        "feMergeNode",
        "feMorphology",
        "feOffset",
        "fePointLight",
        "feSpecularLighting",
        "feSpotLight",
        "feTile",
        "feTurbulence",
        "filter",
        "font",
        "font-face",
        "font-face-format",
        "font-face-name",
        "font-face-src",
        "font-face-uri",
        "foreignObject",
        "g",
        "glyph",
        "glyphRef",
        "hkern",
        "image",
        "line",
        "linearGradient",
        "marker",
        "mask",
        "metadata",
        "missing-glyph",
        "mpath",
        "path",
        "pattern",
        "polygon",
        "polyline",
        "radialGradient",
        "rect",
        "script",
        "set",
        "stop",
        "style",
        "svg",
        "switch",
        "symbol",
        "text",
        "textPath",
        "title",
        "tref",
        "tspan",
        "use",
        "view",
        "vkern"
    );

    public function testIsHtml5Element()
    {
        foreach ($this->html5Elements as $element) {
            $this->assertTrue(Elements::isHtml5Element($element), 'html5 element test failed on: ' . $element);

            $this->assertTrue(Elements::isHtml5Element(strtoupper($element)), 'html5 element test failed on: ' . strtoupper($element));
        }

        $nonhtml5 = array(
            'foo',
            'bar',
            'baz'
        );
        foreach ($nonhtml5 as $element) {
            $this->assertFalse(Elements::isHtml5Element($element), 'html5 element test failed on: ' . $element);

            $this->assertFalse(Elements::isHtml5Element(strtoupper($element)), 'html5 element test failed on: ' . strtoupper($element));
        }
    }

    public function testIsMathMLElement()
    {
        foreach ($this->mathmlElements as $element) {
            $this->assertTrue(Elements::isMathMLElement($element), 'MathML element test failed on: ' . $element);

            // MathML is case sensetitive so these should all fail.
            $this->assertFalse(Elements::isMathMLElement(strtoupper($element)), 'MathML element test failed on: ' . strtoupper($element));
        }

        $nonMathML = array(
            'foo',
            'bar',
            'baz'
        );
        foreach ($nonMathML as $element) {
            $this->assertFalse(Elements::isMathMLElement($element), 'MathML element test failed on: ' . $element);
        }
    }

    public function testIsSvgElement()
    {
        foreach ($this->svgElements as $element) {
            $this->assertTrue(Elements::isSvgElement($element), 'SVG element test failed on: ' . $element);

            // SVG is case sensetitive so these should all fail.
            $this->assertFalse(Elements::isSvgElement(strtoupper($element)), 'SVG element test failed on: ' . strtoupper($element));
        }

        $nonSVG = array(
            'foo',
            'bar',
            'baz'
        );
        foreach ($nonSVG as $element) {
            $this->assertFalse(Elements::isSvgElement($element), 'SVG element test failed on: ' . $element);
        }
    }

    public function testIsElement()
    {
        foreach ($this->html5Elements as $element) {
            $this->assertTrue(Elements::isElement($element), 'html5 element test failed on: ' . $element);

            $this->assertTrue(Elements::isElement(strtoupper($element)), 'html5 element test failed on: ' . strtoupper($element));
        }

        foreach ($this->mathmlElements as $element) {
            $this->assertTrue(Elements::isElement($element), 'MathML element test failed on: ' . $element);

            // MathML is case sensetitive so these should all fail.
            $this->assertFalse(Elements::isElement(strtoupper($element)), 'MathML element test failed on: ' . strtoupper($element));
        }

        foreach ($this->svgElements as $element) {
            $this->assertTrue(Elements::isElement($element), 'SVG element test failed on: ' . $element);

            // SVG is case sensetitive so these should all fail. But, there is duplication
            // html5 and SVG. Since html5 is case insensetitive we need to make sure
            // it's not a html5 element first.
            if (! in_array($element, $this->html5Elements)) {
                $this->assertFalse(Elements::isElement(strtoupper($element)), 'SVG element test failed on: ' . strtoupper($element));
            }
        }

        $nonhtml5 = array(
            'foo',
            'bar',
            'baz'
        );
        foreach ($nonhtml5 as $element) {
            $this->assertFalse(Elements::isElement($element), 'html5 element test failed on: ' . $element);

            $this->assertFalse(Elements::isElement(strtoupper($element)), 'html5 element test failed on: ' . strtoupper($element));
        }
    }

    public function testElement()
    {
        foreach ($this->html5Elements as $element) {
            $this->assertGreaterThan(0, Elements::element($element));
        }
        $nonhtml5 = array(
            'foo',
            'bar',
            'baz'
        );
        foreach ($nonhtml5 as $element) {
            $this->assertFalse(Elements::element($element));
        }
    }

    public function testIsA()
    {
        $this->assertTrue(Elements::isA('script', Elements::KNOWN_ELEMENT));
        $this->assertFalse(Elements::isA('scriptypoo', Elements::KNOWN_ELEMENT));
        $this->assertTrue(Elements::isA('script', Elements::TEXT_RAW));
        $this->assertFalse(Elements::isA('script', Elements::TEXT_RCDATA));

        $voidElements = array(
            'area',
            'base',
            'basefont',
            'bgsound',
            'br',
            'col',
            'command',
            'embed',
            'frame',
            'hr',
            'img'
        );

        foreach ($voidElements as $element) {
            $this->assertTrue(Elements::isA($element, Elements::VOID_TAG), 'Void element test failed on: ' . $element);
        }

        $nonVoid = array(
            'span',
            'a',
            'div'
        );
        foreach ($nonVoid as $tag) {
            $this->assertFalse(Elements::isA($tag, Elements::VOID_TAG), 'Void element test failed on: ' . $tag);
        }

        $blockTags = array(
            'address',
            'article',
            'aside',
            'audio',
            'blockquote',
            'canvas',
            'dd',
            'div',
            'dl',
            'fieldset',
            'figcaption',
            'figure',
            'footer',
            'form',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'header',
            'hgroup',
            'hr',
            'noscript',
            'ol',
            'output',
            'p',
            'pre',
            'section',
            'table',
            'tfoot',
            'ul',
            'video'
        );

        foreach ($blockTags as $tag) {
            $this->assertTrue(Elements::isA($tag, Elements::BLOCK_TAG), 'Block tag test failed on: ' . $tag);
        }

        $nonBlockTags = array(
            'span',
            'img',
            'label'
        );
        foreach ($nonBlockTags as $tag) {
            $this->assertFalse(Elements::isA($tag, Elements::BLOCK_TAG), 'Block tag test failed on: ' . $tag);
        }
    }

    public function testNormalizeSvgElement()
    {
        $tests = array(
            'foo' => 'foo',
            'altglyph' => 'altGlyph',
            'BAR' => 'bar',
            'fespecularlighting' => 'feSpecularLighting',
            'bAz' => 'baz',
            'foreignobject' => 'foreignObject'
        );

        foreach ($tests as $input => $expected) {
            $this->assertEquals($expected, Elements::normalizeSvgElement($input));
        }
    }

    public function testNormalizeSvgAttribute()
    {
        $tests = array(
            'foo' => 'foo',
            'attributename' => 'attributeName',
            'BAR' => 'bar',
            'limitingconeangle' => 'limitingConeAngle',
            'bAz' => 'baz',
            'patterncontentunits' => 'patternContentUnits'
        );

        foreach ($tests as $input => $expected) {
            $this->assertEquals($expected, Elements::normalizeSvgAttribute($input));
        }
    }

    public function testNormalizeMathMlAttribute()
    {
        $tests = array(
            'foo' => 'foo',
            'definitionurl' => 'definitionURL',
            'BAR' => 'bar'
        );

        foreach ($tests as $input => $expected) {
            $this->assertEquals($expected, Elements::normalizeMathMlAttribute($input));
        }
    }
}
