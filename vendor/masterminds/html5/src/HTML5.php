<?php
namespace Masterminds;

use Masterminds\HTML5\Parser\FileInputStream;
use Masterminds\HTML5\Parser\StringInputStream;
use Masterminds\HTML5\Parser\DOMTreeBuilder;
use Masterminds\HTML5\Parser\Scanner;
use Masterminds\HTML5\Parser\Tokenizer;
use Masterminds\HTML5\Serializer\OutputRules;
use Masterminds\HTML5\Serializer\Traverser;

/**
 * This class offers convenience methods for parsing and serializing HTML5.
 * It is roughly designed to mirror the \DOMDocument class that is
 * provided with most versions of PHP.
 *
 * EXPERIMENTAL. This may change or be completely replaced.
 */
class HTML5
{

    /**
     * Global options for the parser and serializer.
     *
     * @var array
     */
    protected $options = array(
        // If the serializer should encode all entities.
        'encode_entities' => false
    );

    protected $errors = array();

    public function __construct(array $options = array())
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Get the default options.
     *
     * @return array The default options.
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Load and parse an HTML file.
     *
     * This will apply the HTML5 parser, which is tolerant of many
     * varieties of HTML, including XHTML 1, HTML 4, and well-formed HTML
     * 3. Note that in these cases, not all of the old data will be
     * preserved. For example, XHTML's XML declaration will be removed.
     *
     * The rules governing parsing are set out in the HTML 5 spec.
     *
     * @param string $file
     *            The path to the file to parse. If this is a resource, it is
     *            assumed to be an open stream whose pointer is set to the first
     *            byte of input.
     * @param array $options
     *            Configuration options when parsing the HTML
     * @return \DOMDocument A DOM document. These object type is defined by the libxml
     *         library, and should have been included with your version of PHP.
     */
    public function load($file, array $options = array())
    {
        // Handle the case where file is a resource.
        if (is_resource($file)) {
            // FIXME: We need a StreamInputStream class.
            return $this->loadHTML(stream_get_contents($file), $options);
        }

        $input = new FileInputStream($file);

        return $this->parse($input, $options);
    }

    /**
     * Parse a HTML Document from a string.
     *
     * Take a string of HTML 5 (or earlier) and parse it into a
     * DOMDocument.
     *
     * @param string $string
     *            A html5 document as a string.
     * @param array $options
     *            Configuration options when parsing the HTML
     * @return \DOMDocument A DOM document. DOM is part of libxml, which is included with
     *         almost all distribtions of PHP.
     */
    public function loadHTML($string, array $options = array())
    {
        $input = new StringInputStream($string);

        return $this->parse($input, $options);
    }

    /**
     * Convenience function to load an HTML file.
     *
     * This is here to provide backwards compatibility with the
     * PHP DOM implementation. It simply calls load().
     *
     * @param string $file
     *            The path to the file to parse. If this is a resource, it is
     *            assumed to be an open stream whose pointer is set to the first
     *            byte of input.
     * @param array $options
     *            Configuration options when parsing the HTML
     *
     * @return \DOMDocument A DOM document. These object type is defined by the libxml
     *         library, and should have been included with your version of PHP.
     */
    public function loadHTMLFile($file, array $options = array())
    {
        return $this->load($file, $options);
    }

    /**
     * Parse a HTML fragment from a string.
     *
     * @param string $string
     *            The html5 fragment as a string.
     * @param array $options
     *            Configuration options when parsing the HTML
     *
     * @return \DOMDocumentFragment A DOM fragment. The DOM is part of libxml, which is included with
     *         almost all distributions of PHP.
     */
    public function loadHTMLFragment($string, array $options = array())
    {
        $input = new StringInputStream($string);

        return $this->parseFragment($input, $options);
    }

    /**
     * Return all errors encountered into parsing phase
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Return true it some errors were encountered into parsing phase
     *
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * Parse an input stream.
     *
     * Lower-level loading function. This requires an input stream instead
     * of a string, file, or resource.
     */
    public function parse(\Masterminds\HTML5\Parser\InputStream $input, array $options = array())
    {
        $this->errors = array();
        $events = new DOMTreeBuilder(false, array_merge($this->getOptions(), $options));
        $scanner = new Scanner($input);
        $parser = new Tokenizer($scanner, $events);

        $parser->parse();
        $this->errors = $events->getErrors();

        return $events->document();
    }

    /**
     * Parse an input stream where the stream is a fragment.
     *
     * Lower-level loading function. This requires an input stream instead
     * of a string, file, or resource.
     */
    public function parseFragment(\Masterminds\HTML5\Parser\InputStream $input, array $options = array())
    {
        $events = new DOMTreeBuilder(true, array_merge($this->getOptions(), $options));
        $scanner = new Scanner($input);
        $parser = new Tokenizer($scanner, $events);

        $parser->parse();
        $this->errors = $events->getErrors();

        return $events->fragment();
    }

    /**
     * Save a DOM into a given file as HTML5.
     *
     * @param mixed $dom
     *            The DOM to be serialized.
     * @param string $file
     *            The filename to be written.
     * @param array $options
     *            Configuration options when serializing the DOM. These include:
     *            - encode_entities: Text written to the output is escaped by default and not all
     *            entities are encoded. If this is set to true all entities will be encoded.
     *            Defaults to false.
     */
    public function save($dom, $file, $options = array())
    {
        $close = true;
        if (is_resource($file)) {
            $stream = $file;
            $close = false;
        } else {
            $stream = fopen($file, 'w');
        }
        $options = array_merge($this->getOptions(), $options);
        $rules = new OutputRules($stream, $options);
        $trav = new Traverser($dom, $stream, $rules, $options);

        $trav->walk();

        if ($close) {
            fclose($stream);
        }
    }

    /**
     * Convert a DOM into an HTML5 string.
     *
     * @param mixed $dom
     *            The DOM to be serialized.
     * @param array $options
     *            Configuration options when serializing the DOM. These include:
     *            - encode_entities: Text written to the output is escaped by default and not all
     *            entities are encoded. If this is set to true all entities will be encoded.
     *            Defaults to false.
     *
     * @return string A HTML5 documented generated from the DOM.
     */
    public function saveHTML($dom, $options = array())
    {
        $stream = fopen('php://temp', 'w');
        $this->save($dom, $stream, array_merge($this->getOptions(), $options));

        return stream_get_contents($stream, - 1, 0);
    }
}
