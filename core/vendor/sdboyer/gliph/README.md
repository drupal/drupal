# Gliph

[![Build Status](https://travis-ci.org/sdboyer/gliph.png?branch=php53)](https://travis-ci.org/sdboyer/gliph)
[![Latest Stable Version](https://poser.pugx.org/sdboyer/gliph/v/stable.png)](https://packagist.org/packages/sdboyer/gliph)

Gliph is a **g**raph **li**brary for **PH**P. It provides graph building blocks and datastructures for use by other PHP applications. It is (currently) designed for use with in-memory graphs, not for interaction with a graph database like [Neo4J](http://neo4j.org/).

Gliph is designed with performance in mind, but primarily to provide a sane interface. Graphs are hard enough without an arcane API making it worse.

## Core Concepts

Gliph has several components that work together: graph classes, algorithms, and visitors. Generally speaking, Gliph is patterned after the [C++ Boost Graph Library](http://www.boost.org/libs/graph/doc); reading their documentation can yield a lot of insight into how Gliph is intended to work.

Note that Gliph is currently written for compatibility with PHP 5.3, but it is intended to port the library to PHP 5.5. The availability of traits, non-scalar/object keys returnable from iterators, and generators will considerably change both the internal and public-facing implementations.

### Graphs

There are a number of different strategies for representing graphs; these strategies are more or less efficient depending on certain properties the graph, and what needs to be done to the graph. The approach taken in Gliph is to offer a roughly consistent 'Graph' interface that is common to all these different strategies. The strategies will have varying levels of efficiency at meeting this common interface, so it is the responsibility of the user to select a graph implementation that is appropriate for their use case. This approach draws heavily from the [taxonomy of graphs](http://www.boost.org/doc/libs/1_54_0/libs/graph/doc/graph_concepts.html) established by the BGL.

Gliph currently implements only an adjacency list graph strategy, in both directed and undirected flavors. Adjacency lists offer efficient access to out-edges, but inefficient access to in-edges (in a directed graph - in an undirected graph, in-edges and out-edges are the same). Adjacency lists and are generally more space-efficient for sparse graphs.

## TODOs

Lots. But, to start with:

- Port to, or provide a parallel implementation in, PHP 5.5. Generators and non-scalar keys from iterators make this all SO much better. In doing that, also shift as much over to traits as possible.
- Implement a generic breadth-first algorithm and its corresponding visitors.
- Implement a generic iterative deepening depth-first algorithm, and its corresponding visitors.
- Implement other popular connected components algorithms, as well as some shortest path algorithms (starting with Dijkstra)
- Write up some examples showing how to actually use the library.

## Acknowledgements

This library draws heavy inspiration from the [C++ Boost Graph Library](http://www.boost.org/libs/graph/doc).

## License

MIT
