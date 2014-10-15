# Gliph

[![Build Status](https://travis-ci.org/sdboyer/gliph.png?branch=php53)](https://travis-ci.org/sdboyer/gliph)
[![Latest Stable Version](https://poser.pugx.org/sdboyer/gliph/v/stable.png)](https://packagist.org/packages/sdboyer/gliph)
[![Coverage Status](https://coveralls.io/repos/sdboyer/gliph/badge.png?branch=php53)](https://coveralls.io/r/sdboyer/gliph?branch=php53)

Gliph is a **g**raph **li**brary for **PH**P. It provides graph building blocks and datastructures for use by other PHP applications. It is (currently) designed for use with in-memory graphs, not for interaction with a graph database like [Neo4J](http://neo4j.org/).

Gliph aims for both sane interfaces and performant implementation - at least, as performant as can be hoped for a PHP graph library. This does require knowing enough about graphs to know what type is appropriate for your use case, but we are aiming to provide helpers that simplify those choices.

## Core Concepts

Gliph has several components that work together: graph classes, algorithms, and visitors. Generally speaking, Gliph is patterned after the [C++ Boost Graph Library](http://www.boost.org/libs/graph/doc); reading their documentation can yield a lot of insight into how Gliph is intended to work.

Note that Gliph is currently written for compatibility with PHP 5.3, but it is intended to port the library to PHP 5.5. The availability of traits, non-scalar keys in iterators, and generators will considerably change and improve both the internal and public-facing implementations.

### Graphs

There are a number of different strategies for representing graphs; these strategies are more or less efficient depending on certain properties the graph, and what needs to be done to the graph. The approach taken in Gliph is to offer a roughly consistent 'Graph' interface that is common to all these different strategies. The strategies will have varying levels of efficiency at meeting this common interface, so it is the responsibility of the user to select a graph implementation that is appropriate for their use case. This approach draws heavily from the [taxonomy of graphs](http://www.boost.org/doc/libs/1_54_0/libs/graph/doc/graph_concepts.html) established by the BGL.

Gliph currently implements only an adjacency list graph strategy, in both directed and undirected flavors. Adjacency lists offer efficient access to out-edges, but inefficient access to in-edges (in a directed graph - in an undirected graph, in-edges and out-edges are the same). Adjacency lists and are generally more space-efficient for sparse graphs.

### Algorithms

Gliph provides various algorithms that can be run on graph objects. These algorithms interact with the graph by making calls to methods defined in the assorted Graph interfaces. If a graph implements the interface type-hinted by a particular algorithm, then the algorithm can run on that graph. But the efficiency of the algorithm will be largely determined by how efficiently that graph implementation can meet the requirements of the interface. Adjacency lists, for example, are not terribly efficient at providing a list of all edges in a graph, but are very good at single-vertex-centric operations.

Gliph's algorithms are typically implemented quite sparsely (especially traversers) - they seek to implement the simplest, most generic version of an algorithm. They also may not return any output, as that work is left to Visitors.

### Visitors

Most algorithms require a visitor object to be provided. The visitor conforms to an interface specified by the algorithm, and the algorithm will call the visitor at certain choice points during its execution. This allows the algorithms to stay highly generic, while visitors can be tailored to a more specific purpose.

For example, a ```DepthFirst``` visitor might be used to calculate vertex reach, or generate a topologically sorted list. Each of these are things that a depth-first graph traversal can do. But the work of doing so is left to the visitor so that only one traversal algorithm is needed, and that algorithm is as cheap (memory and cycles) as possible.

## TODOs

Lots. But, to start with:

- Port to, or provide a parallel implementation in, PHP 5.5. Generators and non-scalar keys from iterators make this all SO much better. In doing that, also shift as much over to traits as possible.
- Implement a generic breadth-first algorithm and its corresponding visitors.
- Implement a generic iterative deepening depth-first algorithm, and its corresponding visitors.
- Implement other popular connected components algorithms, as well as some shortest path algorithms (starting with Dijkstra)
- Write up some examples showing how to actually use the library.
- Extend the ```DepthFirst::traverse()``` algorithm and ```DepthFirstVisitorInterface``` to allow the visitor to stop the traversal. useful if, e.g., a search is being performed and the desired vertex has been found.

## Acknowledgements

This library draws heavy inspiration from the [C++ Boost Graph Library](http://www.boost.org/libs/graph/doc).

## License

MIT
