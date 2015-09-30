EasyRdf
=======
EasyRdf is a PHP library designed to make it easy to consume and produce [RDF].
It was designed for use in mixed teams of experienced and inexperienced RDF
developers. It is written in Object Oriented PHP and has been tested
extensively using PHPUnit.

After parsing EasyRdf builds up a graph of PHP objects that can then be walked
around to get the data to be placed on the page. Dump methods are available to
inspect what data is available during development.

Data is typically loaded into a [EasyRdf_Graph] object from source RDF
documents, loaded from the web via HTTP. The [EasyRdf_GraphStore] class
simplifies loading and saving data to a SPARQL 1.1 Graph Store.

SPARQL queries can be made over HTTP to a Triplestore using the
[EasyRdf_Sparql_Client] class. SELECT and ASK queries will return an
[EasyRdf_Sparql_Result] object and CONSTRUCT and DESCRIBE queries will return
an [EasyRdf_Graph] object.

### Example ###

    $foaf = new EasyRdf_Graph("http://njh.me/foaf.rdf");
    $foaf->load();
    $me = $foaf->primaryTopic();
    echo "My name is: ".$me->get('foaf:name')."\n";


Downloads
---------

The latest _stable_ version of EasyRdf can be [downloaded from the EasyRdf website].


Links
-----

* [EasyRdf Homepage](http://www.easyrdf.org/)
* [API documentation](http://www.easyrdf.org/docs/api)
* [Change Log](http://github.com/njh/easyrdf/blob/master/CHANGELOG.md)
* Source Code: <http://github.com/njh/easyrdf>
* Issue Tracker: <http://github.com/njh/easyrdf/issues>


Requirements
------------

* PHP 5.2.8 or higher


Features
--------

* API documentation written in phpdoc
* Extensive unit tests written using phpunit
  * Automated testing against PHP 5.2, 5.3 and 5.4
* Built-in parsers and serialisers: RDF/JSON, N-Triples, RDF/XML, Turtle
* Optional parsing support for: [ARC2], [Redland Bindings], [rapper]
* Optional support for [Zend_Http_Client]
* No required external dependancies upon other libraries (PEAR, Zend, etc...)
* Complies with Zend Framework coding style.
* Type mapper - resources of type foaf:Person can be mapped into PHP object of class Foaf_Person
* Support for visualisation of graphs using [GraphViz]
* Comes with a number of examples


More Examples
-------------

* [artistinfo.php](https://github.com/njh/easyrdf/blob/master/examples/artistinfo.php#slider) - Example of mapping an RDF class type to a PHP Class
* [basic.php](https://github.com/njh/easyrdf/blob/master/examples/basic.php#slider) - Basic "Hello World" type example
* [basic_sparql.php](https://github.com/njh/easyrdf/blob/master/examples/basic_sparql.php#slider) - Example of making a SPARQL SELECT query
* [converter.php](https://github.com/njh/easyrdf/blob/master/examples/converter.php#slider) - Convert RDF from one format to another
* [dump.php](https://github.com/njh/easyrdf/blob/master/examples/dump.php#slider) - Display the contents of a graph
* [foafinfo.php](https://github.com/njh/easyrdf/blob/master/examples/foafinfo.php#slider) - Display the basic information in a FOAF document
* [foafmaker.php](https://github.com/njh/easyrdf/blob/master/examples/foafmaker.php#slider) - Construct a FOAF document with a choice of serialisations
* [graph_direct.php](https://github.com/njh/easyrdf/blob/master/examples/graph_direct.php#slider) - Example of using EasyRdf_Graph directly without EasyRdf_Resource
* [graphstore.php](https://github.com/njh/easyrdf/blob/master/examples/graphstore.php#slider) - Store and retrieve data from a SPARQL 1.1 Graph Store
* [graphviz.php](https://github.com/njh/easyrdf/blob/master/examples/graphviz.php#slider) - GraphViz rendering example
* [html_tag_helpers.php](https://github.com/njh/easyrdf/blob/master/examples/html_tag_helpers.php#slider) - Rails Style html tag helpers to make the EasyRdf examples simplier
* [httpget.php](https://github.com/njh/easyrdf/blob/master/examples/httpget.php#slider) - No RDF, just test EasyRdf_Http_Client
* [serialise.php](https://github.com/njh/easyrdf/blob/master/examples/serialise.php#slider) - Basic serialisation example
* [sparql_queryform.php](https://github.com/njh/easyrdf/blob/master/examples/sparql_queryform.php#slider) - Form to submit SPARQL queries and display the result
* [uk_postcode.php](https://github.com/njh/easyrdf/blob/master/examples/uk_postcode.php#slider) - Example of resolving UK postcodes using uk-postcodes.com
* [villages.php](https://github.com/njh/easyrdf/blob/master/examples/villages.php#slider) - Fetch and information about villages in Fife from dbpedialite.org
* [zend_framework.php](https://github.com/njh/easyrdf/blob/master/examples/zend_framework.php#slider) - Example of using Zend_Http_Client and Zend_Loader_Autoloader with EasyRdf



Licensing
---------

The EasyRdf library and tests are licensed under the [BSD-3-Clause] license.
The examples are in the public domain, for more information see [UNLICENSE].



[EasyRdf_Graph]:http://www.easyrdf.org/docs/api/EasyRdf_Graph.html
[EasyRdf_GraphStore]:http://www.easyrdf.org/docs/api/EasyRdf_GraphStore.html
[EasyRdf_Sparql_Client]:http://www.easyrdf.org/docs/api/EasyRdf_Sparql_Client.html
[EasyRdf_Sparql_Result]:http://www.easyrdf.org/docs/api/EasyRdf_Sparql_Result.html

[ARC2]:http://github.com/semsol/arc2/
[BSD-3-Clause]:http://www.opensource.org/licenses/BSD-3-Clause
[downloaded from the EasyRdf website]:http://www.easyrdf.org/downloads
[GraphViz]:http://www.graphviz.org/
[rapper]:http://librdf.org/raptor/rapper.html
[RDF]:http://en.wikipedia.org/wiki/Resource_Description_Framework
[Redland Bindings]:http://librdf.org/bindings/
[SPARQL 1.1 query language]:http://www.w3.org/TR/sparql11-query/
[UNLICENSE]:http://unlicense.org/
[Zend_Http_Client]:http://framework.zend.com/manual/en/zend.http.client.html
