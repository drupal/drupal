/*Copyright (c) 2011 Henri Bergius, IKS Consortium
Copyright (c) 2011 Sebastian Germesin, IKS Consortium

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*/(function(){//     VIE - Vienna IKS Editables
//     (c) 2011 Henri Bergius, IKS Consortium
//     (c) 2011 Sebastian Germesin, IKS Consortium
//     (c) 2011 Szaby Grünwald, IKS Consortium
//     VIE may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://viejs.org/

/*global console:false exports:false require:false */

var root = this,
    jQuery = root.jQuery,
    Backbone = root.Backbone,
    _ = root._;


// ## VIE constructor
//
// The VIE constructor is the way to initialize VIE for your
// application. The instance of VIE handles all management of
// semantic interaction, including keeping track of entities,
// changes to them, the possible RDFa views on the page where
// the entities are displayed, and connections to external
// services like Stanbol and DBPedia.
//
// To get a VIE instance, simply run:
//
//     var vie = new VIE();
//
// You can also pass configurations to the VIE instance through
// the constructor. For example, to set a different default
// namespace to be used for names that don't have a namespace
// specified, do:
//
//     var vie = new VIE({
//         baseNamespace: 'http://example.net'
//     });
//
// ### Differences with VIE 1.x
//
// VIE 1.x used singletons for managing entities and views loaded
// from a page. This has been changed with VIE 2.x, and now all
// data managed by VIE is tied to the instance of VIE being used.
//
// This means that VIE needs to be instantiated before using. So,
// when previously you could get entities from page with:
//
//     VIE.RDFaEntities.getInstances();
//
// Now you need to instantiate VIE first. This example uses the
// Classic API compatibility layer instead of the `load` method:
//
//     var vie = new VIE();
//     vie.RDFaEntities.getInstances();
//
// Currently the Classic API is enabled by default, but it is
// recommended to ensure it is enabled before using it. So:
//
//     var vie = new VIE({classic: true});
//     vie.RDFaEntities.getInstances();
var VIE = root.VIE = function(config) {
    this.config = (config) ? config : {};
    this.services = {};
    this.jQuery = jQuery;
    this.entities = new this.Collection([], {
        vie: this
    });

    this.Entity.prototype.entities = this.entities;
    this.Entity.prototype.entityCollection = this.Collection;
    this.Entity.prototype.vie = this;

    this.Namespaces.prototype.vie = this;
// ### Namespaces in VIE
// VIE supports different ontologies and an easy use of them.
// Namespace prefixes reduce the amount of code you have to
// write. In VIE, it does not matter if you access an entitie's
// property with
// `entity.get('<http://dbpedia.org/property/capitalOf>')` or
// `entity.get('dbprop:capitalOf')` or even
// `entity.get('capitalOf')` once the corresponding namespace
// is registered as *baseNamespace*.
// By default `"http://viejs.org/ns/"`is set as base namespace.
// For more information about how to set, get and list all
// registered namespaces, refer to the
// <a href="Namespace.html">Namespaces documentation</a>.
    this.namespaces = new this.Namespaces(
        (this.config.baseNamespace) ? this.config.baseNamespace : "http://viejs.org/ns/",

// By default, VIE is shipped with common namespace prefixes:

// +    owl    : "http://www.w3.org/2002/07/owl#"
// +    rdfs   : "http://www.w3.org/2000/01/rdf-schema#"
// +    rdf    : "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
// +    schema : 'http://schema.org/'
// +    foaf   : 'http://xmlns.com/foaf/0.1/'
// +    geo    : 'http://www.w3.org/2003/01/geo/wgs84_pos#'
// +    dbpedia: "http://dbpedia.org/ontology/"
// +    dbprop : "http://dbpedia.org/property/"
// +    skos   : "http://www.w3.org/2004/02/skos/core#"
// +    xsd    : "http://www.w3.org/2001/XMLSchema#"
// +    sioc   : "http://rdfs.org/sioc/ns#"
// +    dcterms: "http://purl.org/dc/terms/"
        {
            owl    : "http://www.w3.org/2002/07/owl#",
            rdfs   : "http://www.w3.org/2000/01/rdf-schema#",
            rdf    : "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
            schema : 'http://schema.org/',
            foaf   : 'http://xmlns.com/foaf/0.1/',
            geo    : 'http://www.w3.org/2003/01/geo/wgs84_pos#',
            dbpedia: "http://dbpedia.org/ontology/",
            dbprop : "http://dbpedia.org/property/",
            skos   : "http://www.w3.org/2004/02/skos/core#",
            xsd    : "http://www.w3.org/2001/XMLSchema#",
            sioc   : "http://rdfs.org/sioc/ns#",
            dcterms: "http://purl.org/dc/terms/"
        }
    );


    this.Type.prototype.vie = this;
    this.Types.prototype.vie = this;
    this.Attribute.prototype.vie = this;
    this.Attributes.prototype.vie = this;
// ### Type hierarchy in VIE
// VIE takes care about type hierarchy of entities
// (aka. *schema* or *ontology*).
// Once a type hierarchy is known to VIE, we can leverage
// this information, to easily ask, whether an entity
// is of type, e.g., *foaf:Person* or *schema:Place*.
// For more information about how to generate such a type
// hierarchy, refer to the
// <a href="Type.html">Types documentation</a>.
    this.types = new this.Types();
// By default, there is a parent type in VIE, called
// *owl:Thing*. All types automatically inherit from this
// type and all registered entities, are of this type.
    this.types.add("owl:Thing");

// As described above, the Classic API of VIE 1.x is loaded
// by default. As this might change in the future, it is
// recommended to ensure it is enabled before using it. So:
//
//     var vie = new VIE({classic: true});
//     vie.RDFaEntities.getInstances();
    if (this.config.classic === true) {
        /* Load Classic API as well */
        this.RDFa = new this.ClassicRDFa(this);
        this.RDFaEntities = new this.ClassicRDFaEntities(this);
        this.EntityManager = new this.ClassicEntityManager(this);

        this.cleanup = function() {
            this.entities.reset();
        };
    }
};

// ### use(service, name)
// This method registers services within VIE.
// **Parameters**:
// *{string|object}* **service** The service to be registered.
// *{string}* **name** An optional name to register the service with. If this
// is not set, the default name that comes with the service is taken.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE}* : The current VIE instance.
// **Example usage**:
//
//     var vie = new VIE();
//     var conf1 = {...};
//     var conf2 = {...};
//     vie.use(new vie.StanbolService());
//     vie.use(new vie.StanbolService(conf1), "stanbol_1");
//     vie.use(new vie.StanbolService(conf2), "stanbol_2");
//     // <-- this means that there are now 3 services registered!
VIE.prototype.use = function(service, name) {
  if (!name && !service.name) {
    throw new Error("Please provide a name for the service!");
  }
  service.vie = this;
  service.name = (name)? name : service.name;
  if (service.init) {
      service.init();
  }
  this.services[service.name] = service;

  return this;
};

// ### service(name)
// This method returns the service object that is
// registered under the given name.
// **Parameters**:
// *{string}* **name** ...
// **Throws**:
// *{Error}* if no service could be found.
// **Returns**:
// *{object}* : The service to be queried.
// **Example usage**:
//
//     var vie = new VIE();
//     vie.use(new vie.StanbolService(), "stanbol");
//     var service = vie.service("stanbol");
VIE.prototype.service = function(name) {
  if (!this.hasService(name)) {
    throw "Undefined service " + name;
  }
  return this.services[name];
};

// ### hasService(name)
// This method returns a boolean telling whether VIE has a particular
// service loaded.
// **Parameters**:
// *{string}* **name**
// **Returns**:
// *{boolean}* whether service is available
VIE.prototype.hasService = function(name) {
  if (!this.services[name]) {
    return false;
  }
  return true;
};

// ### getServicesArray()
// This method returns an array of all registered services.
// **Parameters**:
// *nothing*
// **Throws**:
// *nothing*
// **Returns**:
// *{array}* : An array of service instances.
// **Example usage**:
//
//     var vie = new VIE();
//     vie.use(new vie.StanbolService(), "stanbol");
//     var services = vie.getServicesArray();
//     services.length; // <-- 1
VIE.prototype.getServicesArray = function() {
  return _.map(this.services, function (v) {return v;});
};

// ### load(options)
// This method instantiates a new VIE.Loadable in order to
// perform queries on the services.
// **Parameters**:
// *{object}* **options** Options to be set.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Loadable}* : A new instance of VIE.Loadable.
// **Example usage**:
//
//     var vie = new VIE();
//     vie.use(new vie.StanbolService(), "stanbol");
//     var loader = vie.load({...});
VIE.prototype.load = function(options) {
  if (!options) { options = {}; }
  options.vie = this;
  return new this.Loadable(options);
};

// ### save(options)
// This method instantiates a new VIE.Savable in order to
// perform queries on the services.
// **Parameters**:
// *{object}* **options** Options to be set.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Savable}* : A new instance of VIE.Savable.
// **Example usage**:
//
//     var vie = new VIE();
//     vie.use(new vie.StanbolService(), "stanbol");
//     var saver = vie.save({...});
VIE.prototype.save = function(options) {
  if (!options) { options = {}; }
  options.vie = this;
  return new this.Savable(options);
};

// ### remove(options)
// This method instantiates a new VIE.Removable in order to
// perform queries on the services.
// **Parameters**:
// *{object}* **options** Options to be set.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Removable}* : A new instance of VIE.Removable.
// **Example usage**:
//
//     var vie = new VIE();
//     vie.use(new vie.StanbolService(), "stanbol");
//     var remover = vie.remove({...});
VIE.prototype.remove = function(options) {
  if (!options) { options = {}; }
  options.vie = this;
  return new this.Removable(options);
};

// ### analyze(options)
// This method instantiates a new VIE.Analyzable in order to
// perform queries on the services.
// **Parameters**:
// *{object}* **options** Options to be set.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Analyzable}* : A new instance of VIE.Analyzable.
// **Example usage**:
//
//     var vie = new VIE();
//     vie.use(new vie.StanbolService(), "stanbol");
//     var analyzer = vie.analyze({...});
VIE.prototype.analyze = function(options) {
  if (!options) { options = {}; }
  options.vie = this;
  return new this.Analyzable(options);
};

// ### find(options)
// This method instantiates a new VIE.Findable in order to
// perform queries on the services.
// **Parameters**:
// *{object}* **options** Options to be set.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Findable}* : A new instance of VIE.Findable.
// **Example usage**:
//
//     var vie = new VIE();
//     vie.use(new vie.StanbolService(), "stanbol");
//     var finder = vie.find({...});
VIE.prototype.find = function(options) {
  if (!options) { options = {}; }
  options.vie = this;
  return new this.Findable(options);
};

// ### loadSchema(url, options)
// VIE only knows the *owl:Thing* type by default.
// You can use this method to import another
// schema (ontology) from an external resource.
// (Currently, this supports only the JSON format!!)
// As this method works asynchronously, you might want
// to register `success` and `error` callbacks via the
// options.
// **Parameters**:
// *{string}* **url** The url, pointing to the schema to import.
// *{object}* **options** Options to be set.
// (Set ```success``` and ```error``` as callbacks.).
// **Throws**:
// *{Error}* if the url is not set.
// **Returns**:
// *{VIE}* : The VIE instance itself.
// **Example usage**:
//
//     var vie = new VIE();
//     vie.loadSchema("http://schema.rdfs.org/all.json",
//        {
//          baseNS : "http://schema.org/",
//          success : function () {console.log("success");},
//          error  : function (msg) {console.warn(msg);}
//        });
VIE.prototype.loadSchema = function(url, options) {
    options = (!options)? {} : options;

    if (!url) {
        throw new Error("Please provide a proper URL");
    }
    else {
        var vie = this;
        jQuery.getJSON(url)
        .success(function(data) {
            try {
                VIE.Util.loadSchemaOrg(vie, data, options.baseNS);
                if (options.success) {
                    options.success.call(vie);
                }
            } catch (e) {
                options.error.call(vie, e);
                return;
            }
         })
        .error(function(data, textStatus, jqXHR) {
            if (options.error) {
                console.warn(data, textStatus, jqXHR);
                options.error.call(vie, "Could not load schema from URL (" + url + ")");
            }
         });
    }

    return this;
};

// ### getTypedEntityClass(type)
// This method generates a special type of `Entity` based on the given type.
// **Parameters**:
// *{string}* **type** The type.
// **Throws**:
// *{Error}* if the type is unknown to VIE.
// **Returns**:
// *{VIE.Entity}* : A subclass of `VIE.Entity`.
// **Example usage**:
//
//     var vie = new VIE();
//     vie.types.add("Person");
//     var PersonClass = vie.getTypedEntityClass("Person");
//     var Person = new PersonClass({"name", "Sebastian"});
VIE.prototype.getTypedEntityClass = function (type) {
  var typeType = this.types.get(type);
  if (!typeType) {
    throw new Error("Unknown type " + type);
  }
  var TypedEntityClass = function (attrs, opts) {
    if (!attrs) {
      attrs = {};
    }
    attrs["@type"] = type;
    this.set(attrs, opts);
  };
  TypedEntityClass.prototype = new this.Entity();
  TypedEntityClass.prototype.schema = function () {
    return VIE.Util.getFormSchemaForType(typeType);
  };
  return TypedEntityClass;
};

// ## Running VIE on Node.js
//
// When VIE is running under Node.js we can use the CommonJS
// require interface to load our dependencies automatically.
//
// This means Node.js users don't need to care about dependencies
// and can just run VIE with:
//
//     var VIE = require('vie');
//
// In browser environments the dependencies have to be included
// before including VIE itself.
if (typeof exports === 'object') {
    exports.VIE = VIE;

    if (!jQuery) {
        jQuery = require('jquery');
    }
    if (!Backbone) {
        Backbone = require('backbone');
        Backbone.setDomLibrary(jQuery);
    }
    if (!_) {
        _ = require('underscore')._;
    }
}
//     VIE - Vienna IKS Editables
//     (c) 2011 Henri Bergius, IKS Consortium
//     (c) 2011 Sebastian Germesin, IKS Consortium
//     (c) 2011 Szaby Grünwald, IKS Consortium
//     VIE may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://viejs.org/

// ## VIE.Able
// VIE implements asynchronius service methods through
// [jQuery.Deferred](http://api.jquery.com/category/deferred-object/) objects.
// Loadable, Analysable, Savable, etc. are part of the VIE service API and
// are implemented with the generic VIE.Able class.
// Example:
//
//      VIE.prototype.Loadable = function (options) {
//          this.init(options,"load");
//      };
//      VIE.prototype.Loadable.prototype = new VIE.prototype.Able();
//
// This defines
//
//     someVIEService.load(options)
//     .using(...)
//     .execute()
//     .success(...)
//     .fail(...)
// which will run the asynchronius `load` function of the service with the created Loadable
// object.

// ### VIE.Able()
// This is the constructor of a VIE.Able. This should not be called
// globally but using the inherited classes below.
// **Parameters**:
// *nothing*
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Able}* : A **new** VIE.Able object.
// Example:
//
//      VIE.prototype.Loadable = function (options) {
//          this.init(options,"load");
//      };
//      VIE.prototype.Loadable.prototype = new VIE.prototype.Able();
VIE.prototype.Able = function(){

// ### init(options, methodName)
// Internal method, called during initialization.
// **Parameters**:
// *{object}* **options** the *able* options coming from the API call
// *{string}* **methodName** the service method called on `.execute`.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Able}* : The current instance.
// **Example usage**:
//
//      VIE.prototype.Loadable = function (options) {
//          this.init(options,"load");
//      };
//      VIE.prototype.Loadable.prototype = new VIE.prototype.Able();
    this.init = function(options, methodName) {
        this.options = options;
        this.services = options.from || options.using || options.to || [];
        this.vie = options.vie;

        this.methodName = methodName;

        // Instantiate the deferred object
        this.deferred = jQuery.Deferred();

// In order to get more information and documentation about the passed-through
// deferred methods and their synonyms, please see the documentation of
// the [jQuery.Deferred object](http://api.jquery.com/category/deferred-object/)
        /* Public deferred-methods */
        this.resolve = this.deferred.resolve;
        this.resolveWith = this.deferred.resolveWith;
        this.reject = this.deferred.reject;
        this.rejectWith = this.deferred.rejectWith;
        this.success = this.done = this.deferred.done;
        this.fail = this.deferred.fail;
        this.then = this.deferred.then;
        this.always = this.deferred.always;
        this.from = this.using;
        this.to = this.using;

        return this;
    };


// ### using(services)
// This method registers services with the current able instance.
// **Parameters**:
// *{string|array}* **services** An id of a service or an array of strings.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Able}* : The current instance.
// **Example usage**:
//
//     var loadable = vie.load({id: "http://example.com/entity/1234"});
//     able.using("myService");
    this.using = function(services) {
        var self = this;
        services = (_.isArray(services))? services : [ services ];
        _.each (services, function (s) {
            var obj = (typeof s === "string")? self.vie.service(s) : s;
            self.services.push(obj);
        });
        return this;
    };

// ### execute()
// This method runs the actual method on all registered services.
// **Parameters**:
// *nothing*
// **Throws**:
// *nothing* ...
// **Returns**:
// *{VIE.Able}* : The current instance.
// **Example usage**:
//
//     var able = new vie.Able().init();
//     able.using("stanbol")
//     .done(function () {alert("finished");})
//     .execute();
    this.execute = function() {
        /* call service[methodName] */
        var able = this;
        _(this.services).each(function(service){
            service[able.methodName](able);
        });
        return this;
    };
};

// ## VIE.Loadable
// A ```VIE.Loadable``` is a wrapper around the deferred object
// to **load** semantic data from a semantic web service.
VIE.prototype.Loadable = function (options) {
    this.init(options,"load");
};
VIE.prototype.Loadable.prototype = new VIE.prototype.Able();

// ## VIE.Savable
// A ```VIE.Savable``` is a wrapper around the deferred object
// to **save** entities by a VIE service. The RDFaService would write the data
// in the HTML as RDFa, the StanbolService stores the data in its Entityhub, etc.
VIE.prototype.Savable = function(options){
    this.init(options, "save");
};
VIE.prototype.Savable.prototype = new VIE.prototype.Able();

// ## VIE.Removable
// A ```VIE.Removable``` is a wrapper around the deferred object
// to **remove** semantic data from a semantic web service.
VIE.prototype.Removable = function(options){
    this.init(options, "remove");
};
VIE.prototype.Removable.prototype = new VIE.prototype.Able();

// ## VIE.Analyzable
// A ```VIE.Analyzable``` is a wrapper around the deferred object
// to **analyze** data and extract semantic information with the
// help of a semantic web service.
VIE.prototype.Analyzable = function (options) {
    this.init(options, "analyze");
};
VIE.prototype.Analyzable.prototype = new VIE.prototype.Able();

// ## VIE.Findable
// A ```VIE.Findable``` is a wrapper around the deferred object
// to **find** semantic data on a semantic storage.
VIE.prototype.Findable = function (options) {
    this.init(options, "find");
};
VIE.prototype.Findable.prototype = new VIE.prototype.Able();

//     VIE - Vienna IKS Editables
//     (c) 2011 Henri Bergius, IKS Consortium
//     (c) 2011 Sebastian Germesin, IKS Consortium
//     (c) 2011 Szaby Grünwald, IKS Consortium
//     VIE may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://viejs.org/

// ## VIE Utils
//
// The here-listed methods are utility methods for the day-to-day
// VIE.js usage. All methods are within the static namespace ```VIE.Util```.
VIE.Util = {

// ### VIE.Util.toCurie(uri, safe, namespaces)
// This method converts a given
// URI into a CURIE (or SCURIE), based on the given ```VIE.Namespaces``` object.
// If the given uri is already a URI, it is left untouched and directly returned.
// If no prefix could be found, an ```Error``` is thrown.
// **Parameters**:
// *{string}* **uri** The URI to be transformed.
// *{boolean}* **safe** A flag whether to generate CURIEs or SCURIEs.
// *{VIE.Namespaces}* **namespaces** The namespaces to be used for the prefixes.
// **Throws**:
// *{Error}* If no prefix could be found in the passed namespaces.
// **Returns**:
// *{string}* The CURIE or SCURIE.
// **Example usage**:
//
//     var ns = new myVIE.Namespaces(
//           "http://viejs.org/ns/",
//           { "dbp": "http://dbpedia.org/ontology/" }
//     );
//     var uri = "<http://dbpedia.org/ontology/Person>";
//     VIE.Util.toCurie(uri, false, ns); // --> dbp:Person
//     VIE.Util.toCurie(uri, true, ns); // --> [dbp:Person]
    toCurie : function (uri, safe, namespaces) {
        if (VIE.Util.isCurie(uri, namespaces)) {
            return uri;
        }
        var delim = ":";
        for (var k in namespaces.toObj()) {
            if (uri.indexOf(namespaces.get(k)) === 1) {
                var pattern = new RegExp("^" + "<?" + namespaces.get(k));
                if (k === '') {
                    delim = '';
                }
                return ((safe)? "[" : "") +
                        uri.replace(pattern, k + delim).replace(/>$/, '') +
                        ((safe)? "]" : "");
            }
        }
        throw new Error("No prefix found for URI '" + uri + "'!");
    },

// ### VIE.Util.isCurie(curie, namespaces)
// This method checks, whether
// the given string is a CURIE and returns ```true``` if so and ```false```otherwise.
// **Parameters**:
// *{string}* **curie** The CURIE (or SCURIE) to be checked.
// *{VIE.Namespaces}* **namespaces** The namespaces to be used for the prefixes.
// **Throws**:
// *nothing*
// **Returns**:
// *{boolean}* ```true``` if the given curie is a CURIE or SCURIE and ```false``` otherwise.
// **Example usage**:
//
//     var ns = new myVIE.Namespaces(
//           "http://viejs.org/ns/",
//           { "dbp": "http://dbpedia.org/ontology/" }
//     );
//     var uri = "<http://dbpedia.org/ontology/Person>";
//     var curie = "dbp:Person";
//     var scurie = "[dbp:Person]";
//     var text = "This is some text.";
//     VIE.Util.isCurie(uri, ns);    // --> false
//     VIE.Util.isCurie(curie, ns);  // --> true
//     VIE.Util.isCurie(scurie, ns); // --> true
//     VIE.Util.isCurie(text, ns);   // --> false
    isCurie : function (curie, namespaces) {
        if (VIE.Util.isUri(curie)) {
            return false;
        } else {
            try {
                VIE.Util.toUri(curie, namespaces);
                return true;
            } catch (e) {
                return false;
            }
        }
    },

// ### VIE.Util.toUri(curie, namespaces)
// This method converts a
// given CURIE (or save CURIE) into a URI, based on the given ```VIE.Namespaces``` object.
// **Parameters**:
// *{string}* **curie** The CURIE to be transformed.
// *{VIE.Namespaces}* **namespaces** The namespaces object
// **Throws**:
// *{Error}* If no URI could be assembled.
// **Returns**:
// *{string}* : A string, representing the URI.
// **Example usage**:
//
//     var ns = new myVIE.Namespaces(
//           "http://viejs.org/ns/",
//           { "dbp": "http://dbpedia.org/ontology/" }
//     );
//     var curie = "dbp:Person";
//     var scurie = "[dbp:Person]";
//     VIE.Util.toUri(curie, ns);
//          --> <http://dbpedia.org/ontology/Person>
//     VIE.Util.toUri(scurie, ns);
//          --> <http://dbpedia.org/ontology/Person>
    toUri : function (curie, namespaces) {
        if (VIE.Util.isUri(curie)) {
            return curie;
        }
        var delim = ":";
        for (var prefix in namespaces.toObj()) {
            if (prefix !== "" && (curie.indexOf(prefix + ":") === 0 || curie.indexOf("[" + prefix + ":") === 0)) {
                var pattern = new RegExp("^" + "\\[{0,1}" + prefix + delim);
                return "<" + curie.replace(pattern, namespaces.get(prefix)).replace(/\]{0,1}$/, '') + ">";
            }
        }
        /* check for the default namespace */
        if (curie.indexOf(delim) === -1) {
            return "<" + namespaces.base() + curie + ">";
        }
        throw new Error("No prefix found for CURIE '" + curie + "'!");
    },

// ### VIE.Util.isUri(something)
// This method checks, whether the given string is a URI.
// **Parameters**:
// *{string}* **something** : The string to be checked.
// **Throws**:
// *nothing*
// **Returns**:
// *{boolean}* : ```true``` if the string is a URI, ```false``` otherwise.
// **Example usage**:
//
//     var uri = "<http://dbpedia.org/ontology/Person>";
//     var curie = "dbp:Person";
//     VIE.Util.isUri(uri);   // --> true
//     VIE.Util.isUri(curie); // --> false
    isUri : function (something) {
        return (typeof something === "string" && something.search(/^<.+>$/) === 0);
    },

// ### VIE.Util.mapAttributeNS(attr, ns)
// This method maps an attribute of an entity into namespaces if they have CURIEs.
// **Parameters**:
// *{string}* **attr** : The attribute to be transformed.
// *{VIE.Namespaces}* **ns** : The namespaces.
// **Throws**:
// *nothing*
// **Returns**:
// *{string}* : The transformed attribute's name.
// **Example usage**:
//
//      var attr = "name";
//      var ns = myVIE.namespaces;
//      VIE.Util.mapAttributeNS(attr, ns); // '<' + ns.base() + attr + '>';
    mapAttributeNS : function (attr, ns) {
        var a = attr;
        if (ns.isUri (attr) || attr.indexOf('@') === 0) {
            //ignore
        } else if (ns.isCurie(attr)) {
            a = ns.uri(attr);
        } else if (!ns.isUri(attr)) {
            if (attr.indexOf(":") === -1) {
                a = '<' + ns.base() + attr + '>';
            } else {
                a = '<' + attr + '>';
            }
        }
        return a;
    },

// ### VIE.Util.rdf2Entities(service, results)
// This method converts *rdf/json* data from an external service
// into VIE.Entities.
// **Parameters**:
// *{object}* **service** The service that retrieved the data.
// *{object}* **results** The data to be transformed.
// **Throws**:
// *nothing*
// **Returns**:
// *{[VIE.Entity]}* : An array, containing VIE.Entity instances which have been transformed from the given data.
    rdf2Entities: function (service, results) {
        if (typeof jQuery.rdf !== 'function') {
            /* fallback if no rdfQuery has been loaded */
            return VIE.Util._rdf2EntitiesNoRdfQuery(service, results);
        }
        try {
            var rdf = (results instanceof jQuery.rdf)?
                    results.base(service.vie.namespaces.base()) :
                        jQuery.rdf().base(service.vie.namespaces.base()).load(results, {});

            /* if the service contains rules to apply special transformation, they are executed here.*/
            if (service.rules) {
                var rules = jQuery.rdf.ruleset();
                for (var prefix in service.vie.namespaces.toObj()) {
                    if (prefix !== "") {
                        rules.prefix(prefix, service.vie.namespaces.get(prefix));
                    }
                }
                for (var i = 0; i < service.rules.length; i++)if(service.rules.hasOwnProperty(i)) {
                    var rule = service.rules[i];
                    rules.add(rule.left, rule.right);
                }
                rdf = rdf.reason(rules, 10); /* execute the rules only 10 times to avoid looping */
            }
            var entities = {};
            rdf.where('?subject ?property ?object').each(function() {
                var subject = this.subject.toString();
                if (!entities[subject]) {
                    entities[subject] = {
                        '@subject': subject,
                        '@context': service.vie.namespaces.toObj(true),
                        '@type': []
                    };
                }
                var propertyUri = this.property.toString();
                var propertyCurie;

                try {
                    propertyCurie = service.vie.namespaces.curie(propertyUri);
                    //jQuery.createCurie(propertyUri, {namespaces: service.vie.namespaces.toObj(true)});
                } catch (e) {
                    propertyCurie = propertyUri;
                    // console.warn(propertyUri + " doesn't have a namespace definition in '", service.vie.namespaces.toObj());
                }
                entities[subject][propertyCurie] = entities[subject][propertyCurie] || [];

                function getValue(rdfQueryLiteral){
                    if(typeof rdfQueryLiteral.value === "string"){
                        if (rdfQueryLiteral.lang){
                            var literal = {
                                toString: function(){
                                    return this["@value"];
                                },
                                "@value": rdfQueryLiteral.value.replace(/^"|"$/g, ''),
                                "@language": rdfQueryLiteral.lang
                            };
                            return literal;
                        }
                        else
                            return rdfQueryLiteral.value;
                        return rdfQueryLiteral.value.toString();
                    } else if (rdfQueryLiteral.type === "uri"){
                        return rdfQueryLiteral.toString();
                    } else {
                        return rdfQueryLiteral.value;
                    }
                }
                entities[subject][propertyCurie].push(getValue(this.object));
            });

            _(entities).each(function(ent){
                ent["@type"] = ent["@type"].concat(ent["rdf:type"]);
                delete ent["rdf:type"];
                _(ent).each(function(value, property){
                    if(value.length === 1){
                        ent[property] = value[0];
                    }
                });
            });

            var vieEntities = [];
            jQuery.each(entities, function() {
                var entityInstance = new service.vie.Entity(this);
                entityInstance = service.vie.entities.addOrUpdate(entityInstance);
                vieEntities.push(entityInstance);
            });
            return vieEntities;
        } catch (e) {
            console.warn("Something went wrong while parsing the returned results!", e);
            return [];
        }
    },

    /*
    VIE.Util.getPreferredLangForPreferredProperty(entity, preferredFields, preferredLanguages)
    looks for specific ranking fields and languages. It calculates all possibilities and gives them
    a score. It returns the value with the best score.
    */
    getPreferredLangForPreferredProperty: function(entity, preferredFields, preferredLanguages) {
      var l, labelArr, lang, p, property, resArr, valueArr, _len, _len2,
        _this = this;
      resArr = [];
      /* Try to find a label in the preferred language
      */
      _.each(preferredLanguages, function (lang) {
        _.each(preferredFields, function (property) {
          labelArr = null;
          /* property can be a string e.g. "skos:prefLabel"
          */
          if (typeof property === "string" && entity.get(property)) {
            labelArr = _.flatten([entity.get(property)]);
            _(labelArr).each(function(label) {
              /*
              The score is a natural number with 0 for the
              best candidate with the first preferred language
              and first preferred property
              */
              var labelLang, score, value;
              score = p;
              labelLang = label["@language"];
              /*
                                      legacy code for compatibility with uotdated stanbol,
                                      to be removed after may 2012
              */
              if (typeof label === "string" && (label.indexOf("@") === label.length - 3 || label.indexOf("@") === label.length - 5)) {
                labelLang = label.replace(/(^\"*|\"*@)..(..)?$/g, "");
              }
              /* end of legacy code
              */
              if (labelLang) {
                if (labelLang === lang) {
                  score += l;
                } else {
                  score += 20;
                }
              } else {
                score += 10;
              }
              value = label.toString();
              /* legacy code for compatibility with uotdated stanbol, to be removed after may 2012
              */
              value = value.replace(/(^\"*|\"*@..$)/g, "");
              /* end of legacy code
              */
              return resArr.push({
                score: score,
                value: value
              });
            });
            /*
            property can be an object like
            {
              property: "skos:broader",
              makeLabel: function(propertyValueArr) { return "..."; }
            }
            */
          } else if (typeof property === "object" && entity.get(property.property)) {
            valueArr = _.flatten([entity.get(property.property)]);
            valueArr = _(valueArr).map(function(termUri) {
              if (termUri.isEntity) {
                return termUri.getSubject();
              } else {
                return termUri;
              }
            });
            resArr.push({
              score: p,
              value: property.makeLabel(valueArr)
            });
          }
        });
      });
      /*
              take the result with the best score
      */
      resArr = _(resArr).sortBy(function(a) {
        return a.score;
      });
      if(resArr.length) {
        return resArr[0].value;
      } else {
        return "n/a";
      }
    },


// ### VIE.Util._rdf2EntitiesNoRdfQuery(service, results)
// This is a **private** method which should
// only be accessed through ```VIE.Util._rdf2Entities()``` and is a helper method in case there is no
// rdfQuery loaded (*not recommended*).
// **Parameters**:
// *{object}* **service** The service that retrieved the data.
// *{object}* **results** The data to be transformed.
// **Throws**:
// *nothing*
// **Returns**:
// *{[VIE.Entity]}* : An array, containing VIE.Entity instances which have been transformed from the given data.
    _rdf2EntitiesNoRdfQuery: function (service, results) {
        var jsonLD = [];
        _.forEach(results, function(value, key) {
            var entity = {};
            entity['@subject'] = '<' + key + '>';
            _.forEach(value, function(triples, predicate) {
                predicate = '<' + predicate + '>';
                _.forEach(triples, function(triple) {
                    if (triple.type === 'uri') {
                        triple.value = '<' + triple.value + '>';
                    }

                    if (entity[predicate] && !_.isArray(entity[predicate])) {
                        entity[predicate] = [entity[predicate]];
                    }

                    if (_.isArray(entity[predicate])) {
                        entity[predicate].push(triple.value);
                        return;
                    }
                    entity[predicate] = triple.value;
                });
            });
            jsonLD.push(entity);
        });
        return jsonLD;
    },

// ### VIE.Util.loadSchemaOrg(vie, SchemaOrg, baseNS)
// This method is a wrapper around
// the <a href="http://schema.org/">schema.org</a> ontology. It adds all the
// given types and properties as ```VIE.Type``` instances to the given VIE instance.
// If the paramenter **baseNS** is set, the method automatically sets the namespace
// to the provided one. If it is not set, it will keep the base namespace of VIE untouched.
// **Parameters**:
// *{VIE}* **vie** The instance of ```VIE```.
// *{object}* **SchemaOrg** The data imported from schema.org.
// *{string|undefined}* **baseNS** If set, this will become the new baseNamespace within the given ```VIE``` instance.
// **Throws**:
// *{Error}* If the parameter was not given.
// **Returns**:
// *nothing*
    loadSchemaOrg : function (vie, SchemaOrg, baseNS) {

        if (!SchemaOrg) {
            throw new Error("Please load the schema.json file.");
        }
        vie.types.remove("<http://schema.org/Thing>");

        var baseNSBefore = (baseNS)? baseNS : vie.namespaces.base();
        vie.namespaces.base(baseNS);

        var datatypeMapping = {
            'DataType': 'xsd:anyType',
            'Boolean' : 'xsd:boolean',
            'Date'    : 'xsd:date',
            'DateTime': 'xsd:dateTime',
            'Time'    : 'xsd:time',
            'Float'   : 'xsd:float',
            'Integer' : 'xsd:integer',
            'Number'  : 'xsd:anySimpleType',
            'Text'    : 'xsd:string',
            'URL'     : 'xsd:anyURI'
        };

        var dataTypeHelper = function (ancestors, id) {
            var type = vie.types.add(id, [{'id' : 'value', 'range' : datatypeMapping[id]}]);

            for (var i = 0; i < ancestors.length; i++) {
                var supertype = (vie.types.get(ancestors[i]))? vie.types.get(ancestors[i]) :
                    dataTypeHelper.call(vie, SchemaOrg.datatypes[ancestors[i]].supertypes, ancestors[i]);
                type.inherit(supertype);
            }
            return type;
        };

        for (var dt in SchemaOrg.datatypes) {
            if (!vie.types.get(dt)) {
                var ancestors = SchemaOrg.datatypes[dt].supertypes;
                dataTypeHelper.call(vie, ancestors, dt);
            }
        }

        var metadataHelper = function (definition) {
            var metadata = {};

            if (definition.label) {
              metadata.label = definition.label;
            }

            if (definition.url) {
              metadata.url = definition.url;
            }

            if (definition.comment) {
              metadata.comment = definition.comment;
            }

            if (definition.metadata) {
              metadata = _.extend(metadata, definition.metadata);
            }
            return metadata;
        };

        var typeProps = function (id) {
            var props = [];
            _.each(SchemaOrg.types[id].specific_properties, function (pId) {
                var property = SchemaOrg.properties[pId];
                props.push({
                    'id'    : property.id,
                    'range' : property.ranges,
                    'min'   : property.min,
                    'max'   : property.max,
                    'metadata': metadataHelper(property)
                });
            });
            return props;
        };

        var typeHelper = function (ancestors, id, props, metadata) {
            var type = vie.types.add(id, props, metadata);

            for (var i = 0; i < ancestors.length; i++) {
                var supertype = (vie.types.get(ancestors[i]))? vie.types.get(ancestors[i]) :
                    typeHelper.call(vie, SchemaOrg.types[ancestors[i]].supertypes, ancestors[i], typeProps.call(vie, ancestors[i]));
                type.inherit(supertype);
            }
            if (id === "Thing" && !type.isof("owl:Thing")) {
                type.inherit("owl:Thing");
            }
            return type;
        };

        _.each(SchemaOrg.types, function (typeDef) {
            if (vie.types.get(typeDef.id)) {
                return;
            }
            var ancestors = typeDef.supertypes;
            var metadata = metadataHelper(typeDef);
            typeHelper.call(vie, ancestors, typeDef.id, typeProps.call(vie, typeDef.id), metadata);
        });

        /* set the namespace to either the old value or the provided baseNS value */
        vie.namespaces.base(baseNSBefore);
    },

// ### VIE.Util.getEntityTypeUnion(entity)
// This generates a entity-specific VIE type that is a subtype of all the
// types of the entity. This makes it easier to deal with attribute definitions
// specific to an entity because they're merged to a single list. This custom
// type is transient, meaning that it won't be automatilly added to the entity
// or the VIE type registry.
    getEntityTypeUnion : function(entity) {
      var vie = entity.vie;
      return new vie.Type('Union').inherit(entity.get('@type'));
    },

// ### VIE.Util.getFormSchemaForType(type)
// This creates a [Backbone Forms](https://github.com/powmedia/backbone-forms)
// -compatible form schema for any VIE Type.
    getFormSchemaForType : function(type, allowNested) {
      var schema = {};

      // Generate a schema
      _.each(type.attributes.toArray(), function (attribute) {
        var key = VIE.Util.toCurie(attribute.id, false, attribute.vie.namespaces);
        schema[key] = VIE.Util.getFormSchemaForAttribute(attribute);
      });

      // Clean up unknown attribute types
      _.each(schema, function (field, id) {
        if (!field.type) {
          delete schema[id];
        }

        if (field.type === 'URL') {
          field.type = 'Text';
          field.dataType = 'url';
        }

        if (field.type === 'List' && !field.listType) {
          delete schema[id];
        }

        if (!allowNested) {
          if (field.type === 'NestedModel' || field.listType === 'NestedModel') {
            delete schema[id];
          }
        }
      });

      return schema;
    },

/// ### VIE.Util.getFormSchemaForAttribute(attribute)
    getFormSchemaForAttribute : function(attribute) {
      var primaryType = attribute.range[0];
      var schema = {};

      var getWidgetForType = function (type) {
        switch (type) {
          case 'xsd:anySimpleType':
          case 'xsd:float':
          case 'xsd:integer':
            return 'Number';
          case 'xsd:string':
            return 'Text';
          case 'xsd:date':
            return 'Date';
          case 'xsd:dateTime':
            return 'DateTime';
          case 'xsd:boolean':
            return 'Checkbox';
          case 'xsd:anyURI':
            return 'URL';
          default:
            var typeType = attribute.vie.types.get(type);
            if (!typeType) {
              return null;
            }
            if (typeType.attributes.get('value')) {
              // Convert to proper xsd type
              return getWidgetForType(typeType.attributes.get('value').range[0]);
            }
            return 'NestedModel';
        }
      };

      // TODO: Generate a nicer label
      schema.title = VIE.Util.toCurie(attribute.id, false, attribute.vie.namespaces);

      // TODO: Handle attributes linking to other VIE entities

      if (attribute.min > 0) {
        schema.validators = ['required'];
      }

      if (attribute.max > 1) {
        schema.type = 'List';
        schema.listType = getWidgetForType(primaryType);
        if (schema.listType === 'NestedModel') {
          schema.nestedModelType = primaryType;
        }
        return schema;
      }

      schema.type = getWidgetForType(primaryType);
      if (schema.type === 'NestedModel') {
        schema.nestedModelType = primaryType;
      }
      return schema;
    },

// ### VIE.Util.getFormSchema(entity)
// This creates a [Backbone Forms](https://github.com/powmedia/backbone-forms)
// -compatible form schema for any VIE Entity. The form schema creation
// utilizes type information attached to the entity.
// **Parameters**:
// *{```Entity```}* **entity** An instance of VIE ```Entity```.
// **Throws**:
// *nothing*..
// **Returns**:
// *{object}* a JavaScript object representation of the form schema
    getFormSchema : function(entity) {
      if (!entity || !entity.isEntity) {
        return {};
      }

      var unionType = VIE.Util.getEntityTypeUnion(entity);
      var schema = VIE.Util.getFormSchemaForType(unionType, true);

      // Handle nested models
      _.each(schema, function (property, id) {
        if (property.type !== 'NestedModel' && property.listType !== 'NestedModel') {
          return;
        }
        schema[id].model = entity.vie.getTypedEntityClass(property.nestedModelType);
      });

      return schema;
    },

// ### VIE.Util.xsdDateTime(date)
// This transforms a ```Date``` instance into an xsd:DateTime format.
// **Parameters**:
// *{```Date```}* **date** An instance of a javascript ```Date```.
// **Throws**:
// *nothing*..
// **Returns**:
// *{string}* A string representation of the dateTime in the xsd:dateTime format.
    xsdDateTime : function(date) {
        function pad(n) {
            var s = n.toString();
            return s.length < 2 ? '0'+s : s;
        }

        var yyyy = date.getFullYear();
        var mm1  = pad(date.getMonth()+1);
        var dd   = pad(date.getDate());
        var hh   = pad(date.getHours());
        var mm2  = pad(date.getMinutes());
        var ss   = pad(date.getSeconds());

        return yyyy +'-' +mm1 +'-' +dd +'T' +hh +':' +mm2 +':' +ss;
    },

// ### VIE.Util.extractLanguageString(entity, attrs, langs)
// This method extracts a literal string from an entity, searching through the given attributes and languages.
// **Parameters**:
// *{```VIE.Entity```}* **entity** An instance of a VIE.Entity.
// *{```array|string```}* **attrs** Either a string or an array of possible attributes.
// *{```array|string```}* **langs** Either a string or an array of possible languages.
// **Throws**:
// *nothing*..
// **Returns**:
// *{string|undefined}* The string that was found at the attribute with the wanted language, undefined if nothing could be found.
// **Example usage**:
//
//          var attrs = ["name", "rdfs:label"];
//          var langs = ["en", "de"];
//          VIE.Util.extractLanguageString(someEntity, attrs, langs); // "Barack Obama";
    extractLanguageString : function(entity, attrs, langs) {
        var p, attr, name, i, n;
        if (entity && typeof entity !== "string") {
            attrs = (_.isArray(attrs))? attrs : [ attrs ];
            langs = (_.isArray(langs))? langs : [ langs ];
            for (p = 0; p < attrs.length; p++) {
                for (var l = 0; l < langs.length; l++) {
                    var lang = langs[l];
                    attr = attrs[p];
                    if (entity.has(attr)) {
                        name = entity.get(attr);
                        name = (_.isArray(name))? name : [ name ];
                        for (i = 0; i < name.length; i++) {
                            n = name[i];
                            if (n.isEntity) {
                                n = VIE.Util.extractLanguageString(n, attrs, lang);
                            } else if (typeof n === "string") {
                                n = n;
                            } else {
                                n = "";
                            }
                            if (n && n.indexOf('@' + lang) > -1) {
                                return n.replace(/"/g, "").replace(/@[a-z]+/, '').trim();
                            }
                        }
                    }
                }
            }
            /* let's do this again in case we haven't found a name but are dealing with
            broken data where no language is given */
            for (p = 0; p < attrs.length; p++) {
                attr = attrs[p];
                if (entity.has(attr)) {
                    name = entity.get(attr);
                    name = (_.isArray(name))? name : [ name ];
                    for (i = 0; i < name.length; i++) {
                        n = name[i];
                        if (n.isEntity) {
                            n = VIE.Util.extractLanguageString(n, attrs, []);
                        }
                        if (n && (typeof n === "string") && n.indexOf('@') === -1) {
                            return n.replace(/"/g, "").replace(/@[a-z]+/, '').trim();
                        }
                    }
                }
            }
        }
        return undefined;
    },

// ### VIE.Util.transformationRules(service)
// This returns a default set of rdfQuery rules that transform semantic data into the
// VIE entity types.
// **Parameters**:
// *{object}* **service** An instance of a vie.service.
// **Throws**:
// *nothing*..
// **Returns**:
// *{array}* An array of rules with 'left' and 'right' side.
    transformationRules : function (service) {
        var res = [
            // rule(s) to transform a dbpedia:Person into a VIE:Person
             {
                'left' : [
                    '?subject a dbpedia:Person',
                    '?subject rdfs:label ?label'
                 ],
                 'right': function(ns){
                     return function(){
                         return [
                             jQuery.rdf.triple(this.subject.toString(),
                                 'a',
                                 '<' + ns.base() + 'Person>', {
                                     namespaces: ns.toObj()
                                 }),
                             jQuery.rdf.triple(this.subject.toString(),
                                 '<' + ns.base() + 'name>',
                                 this.label, {
                                     namespaces: ns.toObj()
                                 })
                             ];
                     };
                 }(service.vie.namespaces)
             },
             // rule(s) to transform a foaf:Person into a VIE:Person
             {
             'left' : [
                     '?subject a foaf:Person',
                     '?subject rdfs:label ?label'
                  ],
                  'right': function(ns){
                      return function(){
                          return [
                              jQuery.rdf.triple(this.subject.toString(),
                                  'a',
                                  '<' + ns.base() + 'Person>', {
                                      namespaces: ns.toObj()
                                  }),
                              jQuery.rdf.triple(this.subject.toString(),
                                  '<' + ns.base() + 'name>',
                                  this.label, {
                                      namespaces: ns.toObj()
                                  })
                              ];
                      };
                  }(service.vie.namespaces)
              },
             // rule(s) to transform a dbpedia:Place into a VIE:Place
             {
                 'left' : [
                     '?subject a dbpedia:Place',
                     '?subject rdfs:label ?label'
                  ],
                  'right': function(ns) {
                      return function() {
                          return [
                          jQuery.rdf.triple(this.subject.toString(),
                              'a',
                              '<' + ns.base() + 'Place>', {
                                  namespaces: ns.toObj()
                              }),
                          jQuery.rdf.triple(this.subject.toString(),
                                  '<' + ns.base() + 'name>',
                              this.label.toString(), {
                                  namespaces: ns.toObj()
                              })
                          ];
                      };
                  }(service.vie.namespaces)
              },
             // rule(s) to transform a dbpedia:City into a VIE:City
              {
                 'left' : [
                     '?subject a dbpedia:City',
                     '?subject rdfs:label ?label',
                     '?subject dbpedia:abstract ?abs',
                     '?subject dbpedia:country ?country'
                  ],
                  'right': function(ns) {
                      return function() {
                          return [
                          jQuery.rdf.triple(this.subject.toString(),
                              'a',
                              '<' + ns.base() + 'City>', {
                                  namespaces: ns.toObj()
                              }),
                          jQuery.rdf.triple(this.subject.toString(),
                                  '<' + ns.base() + 'name>',
                              this.label.toString(), {
                                  namespaces: ns.toObj()
                              }),
                          jQuery.rdf.triple(this.subject.toString(),
                                  '<' + ns.base() + 'description>',
                              this.abs.toString(), {
                                  namespaces: ns.toObj()
                              }),
                          jQuery.rdf.triple(this.subject.toString(),
                                  '<' + ns.base() + 'containedIn>',
                              this.country.toString(), {
                                  namespaces: ns.toObj()
                              })
                          ];
                      };
                  }(service.vie.namespaces)
              }
        ];
        return res;
    },

    getAdditionalRules : function (service) {

        var mapping = {
            Work : "CreativeWork",
            Film : "Movie",
            TelevisionEpisode : "TVEpisode",
            TelevisionShow : "TVSeries", // not listed as equivalent class on dbpedia.org
            Website : "WebPage",
            Painting : "Painting",
            Sculpture : "Sculpture",

            Event : "Event",
            SportsEvent : "SportsEvent",
            MusicFestival : "Festival",
            FilmFestival : "Festival",

            Place : "Place",
            Continent : "Continent",
            Country : "Country",
            City : "City",
            Airport : "Airport",
            Station : "TrainStation", // not listed as equivalent class on dbpedia.org
            Hospital : "GovernmentBuilding",
            Mountain : "Mountain",
            BodyOfWater : "BodyOfWater",

            Company : "Organization",
            Person : "Person"
        };

        var additionalRules = [];
        _.each(mapping, function (map, key) {
            var tripple = {
                'left' : [ '?subject a dbpedia:' + key, '?subject rdfs:label ?label' ],
                'right' : function(ns) {
                    return function() {
                        return [ jQuery.rdf.triple(this.subject.toString(), 'a', '<' + ns.base() + map + '>', {
                            namespaces : ns.toObj()
                        }), jQuery.rdf.triple(this.subject.toString(), '<' + ns.base() + 'name>', this.label.toString(), {
                            namespaces : ns.toObj()
                        }) ];
                    };
                }(service.vie.namespaces)
            };
            additionalRules.push(tripple);
        });
        return additionalRules;
    }
};
//     VIE - Vienna IKS Editables
//     (c) 2011 Henri Bergius, IKS Consortium
//     (c) 2011 Sebastian Germesin, IKS Consortium
//     (c) 2011 Szaby Grünwald, IKS Consortium
//     VIE may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://viejs.org/

// ## VIE Entities
//
// In VIE there are two low-level model types for storing data.
// **Collections** and **Entities**. Considering `var v = new VIE();` a VIE instance,
// `v.entities` is a Collection with `VIE Entity` objects in it.
// VIE internally uses JSON-LD to store entities.
//
// Each Entity has a few special attributes starting with an `@`. VIE has an API
// for correctly using these attributes, so in order to stay compatible with later
// versions of the library, possibly using a later version of JSON-LD, use the API
// to interact with your entities.
//
// * `@subject` stands for the identifier of the entity. Use `e.getSubject()`
// * `@type` stores the explicit entity types. VIE internally handles Type hierarchy,
// which basically enables to define subtypes and supertypes. Every entity has
// the type 'owl:Thing'. Read more about Types in <a href="Type.html">VIE.Type</a>.
// * `@context` stores namespace definitions used in the entity. Read more about
// Namespaces in <a href="Namespace.html">VIE Namespaces</a>.
VIE.prototype.Entity = function(attrs, opts) {

    attrs = (attrs)? attrs : {};
    opts = (opts)? opts : {};

    var self = this;

    if (attrs['@type'] !== undefined) {
        attrs['@type'] = (_.isArray(attrs['@type']))? attrs['@type'] : [ attrs['@type'] ];
        attrs['@type'] = _.map(attrs['@type'], function(val){
            if (!self.vie.types.get(val)) {
                //if there is no such type -> add it and let it inherit from "owl:Thing"
                self.vie.types.add(val).inherit("owl:Thing");
            }
            return self.vie.types.get(val).id;
        });
        attrs['@type'] = (attrs['@type'].length === 1)? attrs['@type'][0] : attrs['@type'];
    } else {
        // provide "owl:Thing" as the default type if none was given
        attrs['@type'] = self.vie.types.get("owl:Thing").id;
    }

    //the following provides full seamless namespace support
    //for attributes. It should not matter, if you
    //query for `model.get('name')` or `model.get('foaf:name')`
    //or even `model.get('http://xmlns.com/foaf/0.1/name');`
    //However, if we just overwrite `set()` and `get()`, this
    //raises a lot of side effects, so we need to expand
    //the attributes before we create the model.
    _.each (attrs, function (value, key) {
        var newKey = VIE.Util.mapAttributeNS(key, this.namespaces);
        if (key !== newKey) {
            delete attrs[key];
            attrs[newKey] = value;
        }
    }, self.vie);

    var Model = Backbone.Model.extend({
        idAttribute: '@subject',

        initialize: function(attributes, options) {
            if (attributes['@subject']) {
                this.id = this['@subject'] = this.toReference(attributes['@subject']);
            } else {
                this.id = this['@subject'] = attributes['@subject'] = this.cid.replace('c', '_:bnode');
            }
            return this;
        },

        schema: function() {
          return VIE.Util.getFormSchema(this);
        },

        // ### Getter, Has, Setter
        // #### `.get(attr)`
        // To be able to communicate to a VIE Entity you can use a simple get(property)
        // command as in `entity.get('rdfs:label')` which will give you one or more literals.
        // If the property points to a collection, its entities can be browsed further.
        get: function (attr) {
            attr = VIE.Util.mapAttributeNS(attr, self.vie.namespaces);
            var value = Backbone.Model.prototype.get.call(this, attr);
            value = (_.isArray(value))? value : [ value ];

            value = _.map(value, function(v) {
                if (v !== undefined && attr === '@type' && self.vie.types.get(v)) {
                    return self.vie.types.get(v);
                } else if (v !== undefined && self.vie.entities.get(v)) {
                    return self.vie.entities.get(v);
                } else {
                    return v;
                }
            }, this);
            if(value.length === 0) {
                return undefined;
            }
            // if there is only one element, just return that one
            value = (value.length === 1)? value[0] : value;
            return value;
        },

        // #### `.has(attr)`
        // Sometimes you'd like to determine if a specific attribute is set
        // in an entity. For this reason you can call for example `person.has('friend')`
        // to determine if a person entity has friends.
        has: function(attr) {
            attr = VIE.Util.mapAttributeNS(attr, self.vie.namespaces);
            return Backbone.Model.prototype.has.call(this, attr);
        },

        // #### `.set(attrName, value, opts)`,
        // The `options` parameter always refers to a `Backbone.Model.set` `options` object.
        //
        // **`.set(attributes, options)`** is the most universal way of calling the
        // `.set` method. In this case the `attributes` object is a map of all
        // attributes to be changed.
        set : function(attrs, options, opts) {
            if (!attrs) {
                return this;
            }

            if (attrs['@subject']) {
                attrs['@subject'] = this.toReference(attrs['@subject']);
            }

            // Use **`.set(attrName, value, options)`** for setting or changing exactly one
            // entity attribute.
            if (typeof attrs === "string") {
                var obj = {};
                obj[attrs] = options;
                return this.set(obj, opts);
            }
            // **`.set(entity)`**: In case you'd pass a VIE entity,
            // the passed entities attributes are being set for the entity.
            if (attrs.attributes) {
                attrs = attrs.attributes;
            }
            var self = this;
            var coll;
            // resolve shortened URIs like rdfs:label..
            _.each (attrs, function (value, key) {
                var newKey = VIE.Util.mapAttributeNS(key, self.vie.namespaces);
                if (key !== newKey) {
                    delete attrs[key];
                    attrs[newKey] = value;
                }
            }, this);
            // Finally iterate through the *attributes* to be set and prepare
            // them for the Backbone.Model.set method.
            _.each (attrs, function (value, key) {
               if (!value) { return; }
               if (key.indexOf('@') === -1) {
                   if (value.isCollection) {
                       // ignore
                       value.each(function (child) {
                           self.vie.entities.addOrUpdate(child);
                       });
                   } else if (value.isEntity) {
                       self.vie.entities.addOrUpdate(value);
                       coll = new self.vie.Collection(value, {
                         vie: self.vie,
                         predicate: key
                       });
                       attrs[key] = coll;
                   } else if (_.isArray(value)) {
                       if (this.attributes[key] && this.attributes[key].isCollection) {
                         var newEntities = this.attributes[key].addOrUpdate(value);
                         attrs[key] = this.attributes[key];
                         attrs[key].reset(newEntities);
                       }
                   } else if (value["@value"]) {
                       // The value is a literal object, ignore
                   } else if (_.isObject(value) && !_.isDate(value)) {
                       // The value is another VIE Entity
                       var child = new self.vie.Entity(value, options);
                       // which is being stored in `v.entities`
                       self.vie.entities.addOrUpdate(child);
                       // and set as VIE Collection attribute on the original entity
                       coll = new self.vie.Collection(value, {
                         vie: self.vie,
                         predicate: key
                       });
                       attrs[key] = coll;
                   } else {
                       // ignore
                   }
               }
            }, this);
            return Backbone.Model.prototype.set.call(this, attrs, options);
        },

        // **`.unset(attr, opts)` ** removes an attribute from the entity.
        unset: function (attr, opts) {
            attr = VIE.Util.mapAttributeNS(attr, self.vie.namespaces);
            return Backbone.Model.prototype.unset.call(this, attr, opts);
        },

        // Validation based on type rules.
        //
        // There are two ways to skip validation for entity operations:
        //
        // * `options.silent = true`
        // * `options.validate = false`
        validate: function (attrs, opts) {
            if (opts && opts.validate === false) {
                return;
            }
            var types = this.get('@type');
            if (_.isArray(types)) {
                var results = [];
                _.each(types, function (type) {
                    var res = this.validateByType(type, attrs, opts);
                    if (res) {
                        results.push(res);
                    }
                }, this);
                if (_.isEmpty(results)) {
                  return;
                }
                return _.flatten(results);
            }

            return this.validateByType(types, attrs, opts);
        },

        validateByType: function (type, attrs, opts) {
            var messages = {
              max: '<%= property %> cannot contain more than <%= num %> items',
              min: '<%= property %> must contain at least <%= num %> items',
              required: '<%= property %> is required'
            };

            if (!type.attributes) {
                return;
            }

            var toError = function (definition, constraint, messageValues) {
                return {
                    property: definition.id,
                    constraint: constraint,
                    message: _.template(messages[constraint], _.extend({
                        property: definition.id
                    }, messageValues))
                };
            };

            var checkMin = function (definition, attrs) {
                if (!attrs[definition.id] || _.isEmpty(attrs[definition.id])) {
                    return toError(definition, 'required', {});
                }
            };

            // Check the number of items in attr against max
            var checkMax = function (definition, attrs) {
                if (!attrs[definition.id]) {
                    return;
                }

                if (!attrs[definition.id].isCollection && !_.isArray(attrs[definition.id])) {
                    return;
                }

                if (attrs[definition.id].length > definition.max) {
                    return toError(definition, 'max', {
                        num: definition.max
                    });
                }
            };

            var results = [];
            _.each(type.attributes.list(), function (definition) {
                var res;
                if (definition.max && definition.max != -1) {
                    res = checkMax(definition, attrs);
                    if (res) {
                        results.push(res);
                    }
                }

                if (definition.min && definition.min > 0) {
                    res = checkMin(definition, attrs);
                    if (res) {
                        results.push(res);
                    }
                }
            });

            if (_.isEmpty(results)) {
              return;
            }
            return results;
        },

        isNew: function() {
            if (this.getSubjectUri().substr(0, 7) === '_:bnode') {
                return true;
            }
            return false;
        },

        hasChanged: function(attr) {
            if (this.markedChanged) {
                return true;
            }

            return Backbone.Model.prototype.hasChanged.call(this, attr);
        },

        // Force hasChanged to return true
        forceChanged: function(changed) {
            this.markedChanged = changed ? true : false;
        },

        // **`getSubject()`** is the getter for the entity identifier.
        getSubject: function(){
            if (typeof this.id === "undefined") {
                this.id = this.attributes[this.idAttribute];
            }
            if (typeof this.id === 'string') {
                if (this.id.substr(0, 7) === 'http://' || this.id.substr(0, 4) === 'urn:') {
                    return this.toReference(this.id);
                }
                return this.id;
            }
            return this.cid.replace('c', '_:bnode');
        },

        // TODO describe
        getSubjectUri: function(){
            return this.fromReference(this.getSubject());
        },

        isReference: function(uri){
            var matcher = new RegExp("^\\<([^\\>]*)\\>$");
            if (matcher.exec(uri)) {
                return true;
            }
            return false;
        },

        toReference: function(uri){
            if (_.isArray(uri)) {
              var self = this;
              return _.map(uri, function(part) {
                 return self.toReference(part);
              });
            }
            var ns = this.vie.namespaces;
            var ret = uri;
            if (uri.substring(0, 2) === "_:") {
                ret = uri;
            }
            else if (ns.isCurie(uri)) {
                ret = ns.uri(uri);
                if (ret === "<" + ns.base() + uri + ">") {
                    /* no base namespace extension with IDs */
                    ret = '<' + uri + '>';
                }
            } else if (!ns.isUri(uri)) {
                ret = '<' + uri + '>';
            }
            return ret;
        },

        fromReference: function(uri){
            var ns = this.vie.namespaces;
            if (!ns.isUri(uri)) {
                return uri;
            }
            return uri.substring(1, uri.length - 1);
        },

        as: function(encoding){
            if (encoding === "JSON") {
                return this.toJSON();
            }
            if (encoding === "JSONLD") {
                return this.toJSONLD();
            }
            throw new Error("Unknown encoding " + encoding);
        },

        toJSONLD: function(){
            var instanceLD = {};
            var instance = this;
            _.each(instance.attributes, function(value, name){
                var entityValue = value; //instance.get(name);

                if (value instanceof instance.vie.Collection) {
                    entityValue = value.map(function(instance) {
                        return instance.getSubject();
                    });
                }

                // TODO: Handle collections separately
                instanceLD[name] = entityValue;
            });

            instanceLD['@subject'] = instance.getSubject();

            return instanceLD;
        },

        // **`.setOrAdd(arg1, arg2)`** similar to `.set(..)`, `.setOrAdd(..)` can
        // be used for setting one or more attributes of an entity, but in
        // this case it's a collection of values, not just one. That means, if the
        // entity already has the attribute set, make the value to a VIE Collection
        // and use the collection as value. The collection can contain entities
        // or literals, but not both at the same time.
        setOrAdd: function (arg1, arg2, option) {
            var entity = this;
            if (typeof arg1 === "string" && arg2) {
                // calling entity.setOrAdd("rdfs:type", "example:Musician")
                entity._setOrAddOne(arg1, arg2, option);
            }
            else
                if (typeof arg1 === "object") {
                    // calling entity.setOrAdd({"rdfs:type": "example:Musician", ...})
                    _(arg1).each(function(val, key){
                        entity._setOrAddOne(key, val, arg2);
                    });
                }
            return this;
        },


        /* attr is always of type string */
        /* value can be of type: string,int,double,object,VIE.Entity,VIE.Collection */
       /*  val can be of type: undefined,string,int,double,array,VIE.Collection */

        /* depending on the type of value and the type of val, different actions need to be made */
        _setOrAddOne: function (attr, value, options) {
            if (!attr || !value)
                return;
            options = (options)? options : {};
            var v;

            attr = VIE.Util.mapAttributeNS(attr, self.vie.namespaces);

            if (_.isArray(value)) {
                for (v = 0; v < value.length; v++) {
                    this._setOrAddOne(attr, value[v], options);
                }
                return;
            }

            if (attr === "@type" && value instanceof self.vie.Type) {
                value = value.id;
            }

            var obj = {};
            var existing = Backbone.Model.prototype.get.call(this, attr);

            if (!existing) {
                obj[attr] = value;
                this.set(obj, options);
            } else if (existing.isCollection) {
                if (value.isCollection) {
                    value.each(function (model) {
                        existing.add(model);
                    });
                } else if (value.isEntity) {
                    existing.add(value);
                } else if (typeof value === "object") {
                    value = new this.vie.Entity(value);
                    existing.add(value);
                } else {
                    throw new Error("you cannot add a literal to a collection of entities!");
                }
                this.trigger('change:' + attr, this, value, {});
                this.change({});
            } else if (_.isArray(existing)) {
                if (value.isCollection) {
                    for (v = 0; v < value.size(); v++) {
                        this._setOrAddOne(attr, value.at(v).getSubject(), options);
                    }
                } else if (value.isEntity) {
                    this._setOrAddOne(attr, value.getSubject(), options);
                } else if (typeof value === "object") {
                    value = new this.vie.Entity(value);
                    this._setOrAddOne(attr, value, options);
                } else {
                    /* yes, we (have to) allow multiple equal values */
                    existing.push(value);
                    obj[attr] = existing;
                    this.set(obj);
                }
            } else {
                var arr = [ existing ];
                arr.push(value);
                obj[attr] = arr;
                return this.set(obj, options);
            }
        },

        // **`.hasType(type)`** determines if the entity has the explicit `type` set.
        hasType: function(type){
            type = self.vie.types.get(type);
            return this.hasPropertyValue("@type", type);
        },

        // TODO describe
        hasPropertyValue: function(property, value) {
            var t = this.get(property);
            if (!(value instanceof Object)) {
                value = self.vie.entities.get(value);
            }
            if (t instanceof Array) {
                return t.indexOf(value) !== -1;
            }
            else {
                return t === value;
            }
        },

        // **`.isof(type)`** determines if the entity is of `type` by explicit or implicit
        // declaration. E.g. if Employee is a subtype of Person and e Entity has
        // explicitly set type Employee, e.isof(Person) will evaluate to true.
        isof: function (type) {
            var types = this.get('@type');

            if (types === undefined) {
                return false;
            }
            types = (_.isArray(types))? types : [ types ];

            type = (self.vie.types.get(type))? self.vie.types.get(type) : new self.vie.Type(type);
            for (var t = 0; t < types.length; t++) {
                if (self.vie.types.get(types[t])) {
                    if (self.vie.types.get(types[t]).isof(type)) {
                        return true;
                    }
                } else {
                    var typeTmp = new self.vie.Type(types[t]);
                    if (typeTmp.id === type.id) {
                        return true;
                    }
                }
            }
            return false;
        },
        // TODO describe
        addTo : function (collection, update) {
            var self = this;
            if (collection instanceof self.vie.Collection) {
                if (update) {
                    collection.addOrUpdate(self);
                } else {
                    collection.add(self);
                }
                return this;
            }
            throw new Error("Please provide a proper collection of type VIE.Collection as argument!");
        },

        isEntity: true,

        vie: self.vie
    });

    return new Model(attrs, opts);
};
//     VIE - Vienna IKS Editables
//     (c) 2011 Henri Bergius, IKS Consortium
//     (c) 2011 Sebastian Germesin, IKS Consortium
//     (c) 2011 Szaby Grünwald, IKS Consortium
//     VIE may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://viejs.org/
VIE.prototype.Collection = Backbone.Collection.extend({
    model: VIE.prototype.Entity,

    initialize: function (models, options) {
      if (!options || !options.vie) {
        throw new Error('Each collection needs a VIE reference');
      }
      this.vie = options.vie;
      this.predicate = options.predicate;
    },

    canAdd: function (type) {
      return true;
    },

    get: function(id) {
        if (id === null) {
            return null;
        }

        id = (id.getSubject)? id.getSubject() : id;
        if (typeof id === "string" && id.indexOf("_:") === 0) {
            if (id.indexOf("bnode") === 2) {
                //bnode!
                id = id.replace("_:bnode", 'c');
                return this._byCid[id];
            } else {
                return this._byId["<" + id + ">"];
            }
        } else {
            id = this.toReference(id);
            return this._byId[id];
        }
    },

    addOrUpdate: function(model, options) {
        options = options || {};

        var collection = this;
        var existing;
        if (_.isArray(model)) {
            var entities = [];
            _.each(model, function(item) {
                entities.push(collection.addOrUpdate(item, options));
            });
            return entities;
        }

        if (model === undefined) {
            throw new Error("No model given");
        }

        if (_.isString(model)) {
          model = {
            '@subject': model,
            id: model
          };
        }

        if (!model.isEntity) {
            model = new this.model(model);
        }

        if (model.id && this.get(model.id)) {
            existing = this.get(model.id);
        }
        if (this.getByCid(model.cid)) {
            existing = this.getByCid(model.cid);
        }
        if (existing) {
            var newAttribs = {};
            _.each(model.attributes, function(value, attribute) {
                if (!existing.has(attribute)) {
                    newAttribs[attribute] = value;
                    return true;
                }

                if (attribute === '@subject') {
                    if (model.isNew() && !existing.isNew()) {
                        // Save order issue, skip
                        return true;
                    }
                }

                if (existing.get(attribute) === value) {
                    return true;
                }
                //merge existing attribute values with new ones!
                //not just overwrite 'em!!
                var oldVals = existing.attributes[attribute];
                var newVals = value;
                if (oldVals instanceof collection.vie.Collection) {
                    // TODO: Merge collections
                    return true;
                }
                if (options.overrideAttributes) {
                   newAttribs[attribute] = value;
                   return true;
                }
                if (attribute === '@context') {
                    newAttribs[attribute] = jQuery.extend(true, {}, oldVals, newVals);
                } else {
                    oldVals = (jQuery.isArray(oldVals))? oldVals : [ oldVals ];
                    newVals = (jQuery.isArray(newVals))? newVals : [ newVals ];
                    newAttribs[attribute] = _.uniq(oldVals.concat(newVals));
                    newAttribs[attribute] = (newAttribs[attribute].length === 1)? newAttribs[attribute][0] : newAttribs[attribute];
                }
            });

            if (!_.isEmpty(newAttribs)) {
                existing.set(newAttribs, options.updateOptions);
            }
            return existing;
        }
        this.add(model, options.addOptions);
        return model;
    },

    isReference: function(uri){
        var matcher = new RegExp("^\\<([^\\>]*)\\>$");
        if (matcher.exec(uri)) {
            return true;
        }
        return false;
    },

    toReference: function(uri){
        if (this.isReference(uri)) {
            return uri;
        }
        return '<' + uri + '>';
    },

    fromReference: function(uri){
        if (!this.isReference(uri)) {
            return uri;
        }
        return uri.substring(1, uri.length - 1);
    },

    isCollection: true
});
//     VIE - Vienna IKS Editables
//     (c) 2011 Henri Bergius, IKS Consortium
//     (c) 2011 Sebastian Germesin, IKS Consortium
//     (c) 2011 Szaby Grünwald, IKS Consortium
//     VIE may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://viejs.org/
//

// ## VIE.Types
// Within VIE, we provide special capabilities of handling types of entites. This helps
// for example to query easily for certain entities (e.g., you only need to query for *Person*s
// and not for all subtypes).
if (VIE.prototype.Type) {
    throw new Error("ERROR: VIE.Type is already defined. Please check your installation!");
}
if (VIE.prototype.Types) {
    throw new Error("ERROR: VIE.Types is already defined. Please check your installation!");
}

// ### VIE.Type(id, attrs, metadata)
// This is the constructor of a VIE.Type.
// **Parameters**:
// *{string}* **id** The id of the type.
// *{string|array|VIE.Attribute}* **attrs** A string, proper ```VIE.Attribute``` or an array of these which
// *{object}* **metadata** Possible metadata about the type
// are the possible attributes of the type
// **Throws**:
// *{Error}* if one of the given paramenters is missing.
// **Returns**:
// *{VIE.Type}* : A **new** VIE.Type object.
// **Example usage**:
//
//     var person = new vie.Type("Person", ["name", "knows"]);
VIE.prototype.Type = function (id, attrs, metadata) {
    if (id === undefined || typeof id !== 'string') {
        throw "The type constructor needs an 'id' of type string! E.g., 'Person'";
    }

// ### id
// This field stores the id of the type's instance.
// **Parameters**:
// nothing
// **Throws**:
// nothing
// **Returns**:
// *{string}* : The id of the type as a URI.
// **Example usage**:
//
//     console.log(person.id);
//      // --> "<http://viejs.org/ns/Person>"
    this.id = this.vie.namespaces.isUri(id) ? id : this.vie.namespaces.uri(id);

    /* checks whether such a type is already defined. */
    if (this.vie.types.get(this.id)) {
        throw new Error("The type " + this.id + " is already defined!");
    }

// ### supertypes
// This field stores all parent types of the type's instance. This
// is set if the current type inherits from another type.
// **Parameters**:
// nothing
// **Throws**:
// nothing
// **Returns**:
// *{VIE.Types}* : The supertypes (parents) of the type.
// **Example usage**:
//
//     console.log(person.supertypes);
    this.supertypes = new this.vie.Types();

// ### subtypes
// This field stores all children types of the type's instance. This
// will be set if another type inherits from the current type.
// **Parameters**:
// nothing
// **Throws**:
// nothing
// **Returns**:
// *{VIE.Types}* : The subtypes (parents) of the type.
// **Example usage**:
//
//     console.log(person.subtypes);
    this.subtypes = new this.vie.Types();

// ### attributes
// This field stores all attributes of the type's instance as
// a proper ```VIE.Attributes``` class. (see also <a href="Attribute.html">VIE.Attributes</a>)
// **Parameters**:
// nothing
// **Throws**:
// nothing
// **Returns**:
// *{VIE.Attributes}* : The attributes of the type.
// **Example usage**:
//
//     console.log(person.attributes);
    this.attributes = new this.vie.Attributes(this, (attrs)? attrs : []);

// ### metadata
// This field stores possible additional information about the type, like
// a human-readable label.
    this.metadata = metadata ? metadata : {};

// ### isof(type)
// This method checks whether the current type is a child of the given type.
// **Parameters**:
// *{string|VIE.Type}* **type** The type (or the id of that type) to be checked.
// **Throws**:
// *{Error}* If the type is not valid.
// **Returns**:
// *{boolean}* : ```true``` if the current type inherits from the type, ```false``` otherwise.
// **Example usage**:
//
//     console.log(person.isof("owl:Thing"));
//     // <-- true
    this.isof = function (type) {
        type = this.vie.types.get(type);
        if (type) {
            return type.subsumes(this.id);
        } else {
            throw new Error("No valid type given");
        }
    };

// ### subsumes(type)
// This method checks whether the current type is a parent of the given type.
// **Parameters**:
// *{string|VIE.Type}* **type** The type (or the id of that type) to be checked.
// **Throws**:
// *{Error}* If the type is not valid.
// **Returns**:
// *{boolean}* : ```true``` if the current type is a parent of the type, ```false``` otherwise.
// **Example usage**:
//
//     var x = new vie.Type(...);
//     var y = new vie.Type(...).inherit(x);
//     y.isof(x) === x.subsumes(y);
    this.subsumes = function (type) {
        type = this.vie.types.get(type);
        if (type) {
            if (this.id === type.id) {
                return true;
            }
            var subtypes = this.subtypes.list();
            for (var c = 0; c < subtypes.length; c++) {
                var childObj = subtypes[c];
                if (childObj) {
                     if (childObj.id === type.id || childObj.subsumes(type)) {
                         return true;
                     }
                }
            }
            return false;
        } else {
            throw new Error("No valid type given");
        }
    };

// ### inherit(supertype)
// This method invokes inheritance throught the types. This adds the current type to the
// subtypes of the supertype and vice versa.
// **Parameters**:
// *{string|VIE.Type|array}* **supertype** The type to be inherited from. If this is an array
// the inherit method is called sequentially on all types.
// **Throws**:
// *{Error}* If the type is not valid.
// **Returns**:
// *{VIE.Type}* : The instance itself.
// **Example usage**:
//
//     var x = new vie.Type(...);
//     var y = new vie.Type(...).inherit(x);
//     y.isof(x) // <-- true
    this.inherit = function (supertype) {
        if (typeof supertype === "string") {
            this.inherit(this.vie.types.get(supertype));
        }
        else if (supertype instanceof this.vie.Type) {
            supertype.subtypes.addOrOverwrite(this);
            this.supertypes.addOrOverwrite(supertype);
            try {
                /* only for validation of attribute-inheritance!
                   if this throws an error (inheriting two attributes
                   that cannot be combined) we reverse all changes. */
                this.attributes.list();
            } catch (e) {
                supertype.subtypes.remove(this);
                this.supertypes.remove(supertype);
                throw e;
            }
        } else if (jQuery.isArray(supertype)) {
            for (var i = 0, slen = supertype.length; i < slen; i++) {
                this.inherit(supertype[i]);
            }
        } else {
            throw new Error("Wrong argument in VIE.Type.inherit()");
        }
        return this;
    };

// ### hierarchy()
// This method serializes the hierarchy of child types into an object.
// **Parameters**:
// *nothing*
// **Throws**:
// *nothing*
// **Returns**:
// *{object}* : The hierachy of child types as an object.
// **Example usage**:
//
//     var x = new vie.Type(...);
//     var y = new vie.Type(...).inherit(x);
//     x.hierarchy();
    this.hierarchy = function () {
        var obj = {id : this.id, subtypes: []};
        var list = this.subtypes.list();
        for (var c = 0, llen = list.length; c < llen; c++) {
            var childObj = this.vie.types.get(list[c]);
            obj.subtypes.push(childObj.hierarchy());
        }
        return obj;
    };

// ### instance()
// This method creates a ```VIE.Entity``` instance from this type.
// **Parameters**:
// *{object}* **attrs**  see <a href="Entity.html">constructor of VIE.Entity</a>
// *{object}* **opts**  see <a href="Entity.html">constructor of VIE.Entity</a>
// **Throws**:
// *{Error}* if the instance could not be built
// **Returns**:
// *{VIE.Entity}* : A **new** instance of a ```VIE.Entity``` with the current type.
// **Example usage**:
//
//     var person = new vie.Type("person");
//     var sebastian = person.instance(
//         {"@subject" : "#me",
//          "name" : "Sebastian"});
//     console.log(sebastian.get("name")); // <-- "Sebastian"
    this.instance = function (attrs, opts) {
        attrs = (attrs)? attrs : {};
        opts = (opts)? opts : {};

        /* turn type/attribute checking on by default! */
        if (opts.typeChecking !== false) {
            for (var a in attrs) {
                if (a.indexOf('@') !== 0 && !this.attributes.get(a)) {
                    throw new Error("Cannot create an instance of " + this.id + " as the type does not allow an attribute '" + a + "'!");
                }
            }
        }

        if (attrs['@type']) {
            attrs['@type'].push(this.id);
        } else {
            attrs['@type'] = this.id;
        }

        return new this.vie.Entity(attrs, opts);
    };

// ### toString()
// This method returns the id of the type.
// **Parameters**:
// *nothing*
// **Throws**:
// *nothing*
// **Returns**:
// *{string}* : The id of the type.
// **Example usage**:
//
//     var x = new vie.Type(...);
//     x.toString() === x.id;
    this.toString = function () {
        return this.id;
    };
};

// ### VIE.Types()
// This is the constructor of a VIE.Types. This is a convenience class
// to store ```VIE.Type``` instances properly.
// **Parameters**:
// *nothing*
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Types}* : A **new** VIE.Types object.
// **Example usage**:
//
//     var types = new vie.Types();
VIE.prototype.Types = function () {

    this._types = {};

// ### add(id, attrs, metadata)
// This method adds a `VIE.Type` to the types.
// **Parameters**:
// *{string|VIE.Type}* **id** If this is a string, the type is created and directly added.
// *{string|object}* **attrs** Only used if ```id``` is a string.
// *{object}* **metadata** potential additional metadata about the type.
// **Throws**:
// *{Error}* if a type with the given id already exists a ```VIE.Entity``` instance from this type.
// **Returns**:
// *{VIE.Types}* : The instance itself.
// **Example usage**:
//
//     var types = new vie.Types();
//     types.add("Person", ["name", "knows"]);
    this.add = function (id, attrs, metadata) {
        if (_.isArray(id)) {
           _.each(id, function (type) {
             this.add(type);
           }, this);
           return this;
        }

        if (this.get(id)) {
            throw new Error("Type '" + id + "' already registered.");
        }  else {
            if (typeof id === "string") {
                var t = new this.vie.Type(id, attrs, metadata);
                this._types[t.id] = t;
                return t;
            } else if (id instanceof this.vie.Type) {
                this._types[id.id] = id;
                return id;
            } else {
                throw new Error("Wrong argument to VIE.Types.add()!");
            }
        }
        return this;
    };

// ### addOrOverwrite(id, attrs)
// This method adds or overwrites a `VIE.Type` to the types. This is the same as
// ``this.remove(id); this.add(id, attrs);``
// **Parameters**:
// *{string|VIE.Type}* **id** If this is a string, the type is created and directly added.
// *{string|object}* **attrs** Only used if ```id``` is a string.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Types}* : The instance itself.
// **Example usage**:
//
//     var types = new vie.Types();
//     types.addOrOverwrite("Person", ["name", "knows"]);
    this.addOrOverwrite = function(id, attrs){
        if (this.get(id)) {
            this.remove(id);
        }
        return this.add(id, attrs);
    };

// ### get(id)
// This method retrieves a `VIE.Type` from the types by it's id.
// **Parameters**:
// *{string|VIE.Type}* **id** The id or the type itself.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Type}* : The instance of the type or ```undefined```.
// **Example usage**:
//
//     var types = new vie.Types();
//     types.addOrOverwrite("Person", ["name", "knows"]);
//     types.get("Person");
    this.get = function (id) {
        if (!id) {
            return undefined;
        }
        if (typeof id === 'string') {
            var lid = this.vie.namespaces.isUri(id) ? id : this.vie.namespaces.uri(id);
            return this._types[lid];
        } else if (id instanceof this.vie.Type) {
            return this.get(id.id);
        }
        return undefined;
    };

// ### remove(id)
// This method removes a type of given id from the type. This also
// removes all children if their only parent were this
// type. Furthermore, this removes the link from the
// super- and subtypes.
// **Parameters**:
// *{string|VIE.Type}* **id** The id or the type itself.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Type}* : The removed type.
// **Example usage**:
//
//     var types = new vie.Types();
//     types.addOrOverwrite("Person", ["name", "knows"]);
//     types.remove("Person");
    this.remove = function (id) {
        var t = this.get(id);
        /* test whether the type actually exists in VIE
         * and prevents removing *owl:Thing*.
         */
        if (!t) {
            return this;
        }
        if (!t || t.subsumes("owl:Thing")) {
            console.warn("You are not allowed to remove 'owl:Thing'.");
            return this;
        }
        delete this._types[t.id];

        var subtypes = t.subtypes.list();
        for (var c = 0; c < subtypes.length; c++) {
            var childObj = subtypes[c];
            if (childObj.supertypes.list().length === 1) {
                /* recursively remove all children
                   that inherit only from this type */
                this.remove(childObj);
            } else {
                childObj.supertypes.remove(t.id);
            }
        }
        return t;
    };

// ### toArray() === list()
// This method returns an array of all types.
// **Parameters**:
// *nothing*
// **Throws**:
// *nothing*
// **Returns**:
// *{array}* : An array of ```VIE.Type``` instances.
// **Example usage**:
//
//     var types = new vie.Types();
//     types.addOrOverwrite("Person", ["name", "knows"]);
//     types.list();
    this.toArray = this.list = function () {
        var ret = [];
        for (var i in this._types) {
            ret.push(this._types[i]);
        }
        return ret;
    };

// ### sort(types, desc)
// This method sorts an array of types in their order, given by the
// inheritance. This returns a copy and leaves the original array untouched.
// **Parameters**:
// *{array|VIE.Type}* **types** The array of ```VIE.Type``` instances or ids of types to be sorted.
// *{boolean}* **desc** If 'desc' is given and 'true', the array will be sorted
// in descendant order.
// *nothing*
// **Throws**:
// *nothing*
// **Returns**:
// *{array}* : A sorted copy of the array.
// **Example usage**:
//
//     var types = new vie.Types();
//     types.addOrOverwrite("Person", ["name", "knows"]);
//     types.sort(types.list(), true);
    this.sort = function (types, desc) {
        var self = this;
        types = (jQuery.isArray(types))? types : [ types ];
        desc = (desc)? true : false;

        if (types.length === 0) return [];
        var copy = [ types[0] ];
        var x, tlen;
        for (x = 1, tlen = types.length; x < tlen; x++) {
            var insert = types[x];
            var insType = self.get(insert);
            if (insType) {
                for (var y = 0; y < copy.length; y++) {
                    if (insType.subsumes(copy[y])) {
                        copy.splice(y,0,insert);
                        break;
                    } else if (y === copy.length - 1) {
                        copy.push(insert);
                    }
                }
            }
        }

        //unduplicate
        for (x = 0; x < copy.length; x++) {
            if (copy.lastIndexOf(copy[x]) !== x) {
                copy.splice(x, 1);
                x--;
            }
        }

        if (!desc) {
            copy.reverse();
        }
        return copy;
    };
};
//     VIE - Vienna IKS Editables
//     (c) 2011 Henri Bergius, IKS Consortium
//     (c) 2011 Sebastian Germesin, IKS Consortium
//     (c) 2011 Szaby Grünwald, IKS Consortium
//     VIE may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://viejs.org/
//

// ## VIE.Attributes
// Within VIE, we provide special capabilities of handling attributes of types of entites. This
// helps first of all to list all attributes of an entity type, but furthermore fully supports
// inheritance of attributes from the type-class to inherit from.
if (VIE.prototype.Attribute) {
  throw new Error("ERROR: VIE.Attribute is already defined. Please check your VIE installation!");
}
if (VIE.prototype.Attributes) {
  throw new Error("ERROR: VIE.Attributes is already defined. Please check your VIE installation!");
}

// ### VIE.Attribute(id, range, domain, minCount, maxCount, metadata)
// This is the constructor of a VIE.Attribute.
// **Parameters**:
// *{string}* **id** The id of the attribute.
// *{string|array}* **range** A string or an array of strings of the target range of
// the attribute.
// *{string}* **domain** The domain of the attribute.
// *{number}* **minCount** The minimal number this attribute can occur. (needs to be >= 0)
// *{number}* **maxCount** The maximal number this attribute can occur. (needs to be >= minCount, use `-1` for unlimited)
// *{object}* **metadata** Possible metadata about the attribute
// **Throws**:
// *{Error}* if one of the given paramenters is missing.
// **Returns**:
// *{VIE.Attribute}* : A **new** VIE.Attribute object.
// **Example usage**:
//
//     var knowsAttr = new vie.Attribute("knows", ["Person"], "Person", 0, 10);
//      // Creates an attribute to describe a *knows*-relationship
//      // between persons. Each person can only have
VIE.prototype.Attribute = function (id, range, domain, minCount, maxCount, metadata) {
    if (id === undefined || typeof id !== 'string') {
        throw new Error("The attribute constructor needs an 'id' of type string! E.g., 'Person'");
    }
    if (range === undefined) {
        throw new Error("The attribute constructor of " + id + " needs 'range'.");
    }
    if (domain === undefined) {
        throw new Error("The attribute constructor of " + id + " needs a 'domain'.");
    }

    this._domain = domain;

// ### id
// This field stores the id of the attribute's instance.
// **Parameters**:
// nothing
// **Throws**:
// nothing
// **Returns**:
// *{string}* : A URI, representing the id of the attribute.
// **Example usage**:
//
//     var knowsAttr = new vie.Attribute("knows", ["Person"], "Person");
//     console.log(knowsAttr.id);
//     // --> <http://viejs.org/ns/knows>
    this.id = this.vie.namespaces.isUri(id) ? id : this.vie.namespaces.uri(id);

// ### range
// This field stores the ranges of the attribute's instance.
// **Parameters**:
// nothing
// **Throws**:
// nothing
// **Returns**:
// *{array}* : An array of strings which represent the types.
// **Example usage**:
//
//     var knowsAttr = new vie.Attribute("knows", ["Person"], "Person");
//     console.log(knowsAttr.range);
//      // --> ["Person"]
    this.range = (_.isArray(range))? range : [ range ];

// ### min
// This field stores the minimal amount this attribute can occur in the type's instance. The number
// needs to be greater or equal to zero.
// **Parameters**:
// nothing
// **Throws**:
// nothing
// **Returns**:
// *{int}* : The minimal amount this attribute can occur.
// **Example usage**:
//
//     console.log(person.min);
//      // --> 0
    minCount = minCount ? minCount : 0;
    this.min = (minCount > 0) ? minCount : 0;

// ### max
// This field stores the maximal amount this attribute can occur in the type's instance.
// This number cannot be smaller than min
// **Parameters**:
// nothing
// **Throws**:
// nothing
// **Returns**:
// *{int}* : The maximal amount this attribute can occur.
// **Example usage**:
//
//     console.log(person.max);
//      // --> 1.7976931348623157e+308
    maxCount = maxCount ? maxCount : 1;
    if (maxCount === -1) {
      maxCount = Number.MAX_VALUE;
    }
    this.max = (maxCount >= this.min)? maxCount : this.min;

// ### metadata
// This field holds potential metadata about the attribute.
    this.metadata = metadata ? metadata : {};

// ### applies(range)
// This method checks, whether the current attribute applies in the given range.
// If ```range``` is a string and cannot be transformed into a ```VIE.Type```,
// this performs only string comparison, if it is a VIE.Type
// or an ID of a VIE.Type, then inheritance is checked as well.
// **Parameters**:
// *{string|VIE.Type}* **range** The ```VIE.Type``` (or it's string representation) to be checked.
// **Throws**:
// nothing
// **Returns**:
// *{boolean}* : ```true``` if the given type applies to this attribute and ```false``` otherwise.
// **Example usage**:
//
//     var knowsAttr = new vie.Attribute("knows", ["Person"], "Person");
//     console.log(knowsAttr.applies("Person")); // --> true
//     console.log(knowsAttr.applies("Place")); // --> false
    this.applies = function (range) {
        if (this.vie.types.get(range)) {
            range = this.vie.types.get(range);
        }
        for (var r = 0, len = this.range.length; r < len; r++) {
            var x = this.vie.types.get(this.range[r]);
            if (x === undefined && typeof range === "string") {
                if (range === this.range[r]) {
                    return true;
                }
            }
            else {
                if (range.isof(this.range[r])) {
                    return true;
                }
            }
        }
        return false;
    };

};

// ## VIE.Attributes(domain, attrs)
// This is the constructor of a VIE.Attributes. Basically a convenience class
// that represents a list of ```VIE.Attribute```. As attributes are part of a
// certain ```VIE.Type```, it needs to be passed for inheritance checks.
// **Parameters**:
// *{string}* **domain** The domain of the attributes (the type they will be part of).
// *{string|VIE.Attribute|array}* **attrs** Either a string representation of an attribute,
// a proper instance of ```VIE.Attribute``` or an array of both.
// *{string}* **domain** The domain of the attribute.
// **Throws**:
// *{Error}* if one of the given paramenters is missing.
// **Returns**:
// *{VIE.Attribute}* : A **new** VIE.Attribute instance.
// **Example usage**:
//
//     var knowsAttr = new vie.Attribute("knows", ["Person"], "Person");
//     var personAttrs = new vie.Attributes("Person", knowsAttr);
VIE.prototype.Attributes = function (domain, attrs) {

    this._local = {};
    this._attributes = {};

// ### domain
// This field stores the domain of the attributes' instance.
// **Parameters**:
// nothing
// **Throws**:
// nothing
// **Returns**:
// *{string}* : The string representation of the domain.
// **Example usage**:
//
//     console.log(personAttrs.domain);
//     // --> ["Person"]
    this.domain = domain;

// ### add(id, range, min, max, metadata)
// This method adds a ```VIE.Attribute``` to the attributes instance.
// **Parameters**:
// *{string|VIE.Attribute}* **id** The string representation of an attribute, or a proper
// instance of a ```VIE.Attribute```.
// *{string|array}* **range** An array representing the target range of the attribute.
// *{number}* **min** The minimal amount this attribute can appear.
// instance of a ```VIE.Attribute```.
// *{number}* **max** The maximal amount this attribute can appear.
// *{object}* **metadata** Additional metadata for the attribute.
// **Throws**:
// *{Error}* If an atribute with the given id is already registered.
// *{Error}* If the ```id``` parameter is not a string, nor a ```VIE.Type``` instance.
// **Returns**:
// *{VIE.Attribute}* : The generated or passed attribute.
// **Example usage**:
//
//     personAttrs.add("name", "Text", 0, 1);
    this.add = function (id, range, min, max, metadata) {
        if (_.isArray(id)) {
          _.each(id, function (attribute) {
            this.add(attribute);
          }, this);
          return this;
        }

        if (this.get(id)) {
            throw new Error("Attribute '" + id + "' already registered for domain " + this.domain.id + "!");
        } else {
            if (typeof id === "string") {
                var a = new this.vie.Attribute(id, range, this.domain, min, max, metadata);
                this._local[a.id] = a;
                return a;
            } else if (id instanceof this.vie.Attribute) {
                id.domain = this.domain;
                id.vie = this.vie;
                this._local[id.id] = id;
                return id;
            } else {
                throw new Error("Wrong argument to VIE.Types.add()!");
            }
        }
    };

// ### remove(id)
// This method removes a ```VIE.Attribute``` from the attributes instance.
// **Parameters**:
// *{string|VIE.Attribute}* **id** The string representation of an attribute, or a proper
// instance of a ```VIE.Attribute```.
// **Throws**:
// *{Error}* When the attribute is inherited from a parent ```VIE.Type``` and thus cannot be removed.
// **Returns**:
// *{VIE.Attribute}* : The removed attribute.
// **Example usage**:
//
//     personAttrs.remove("knows");
    this.remove = function (id) {
        var a = this.get(id);
        if (a.id in this._local) {
            delete this._local[a.id];
            return a;
        }
        throw new Error("The attribute " + id + " is inherited and cannot be removed from the domain " + this.domain.id + "!");
    };

// ### get(id)
// This method returns a ```VIE.Attribute``` from the attributes instance by it's id.
// **Parameters**:
// *{string|VIE.Attribute}* **id** The string representation of an attribute, or a proper
// instance of a ```VIE.Attribute```.
// **Throws**:
// *{Error}* When the method is called with an unknown datatype.
// **Returns**:
// *{VIE.Attribute}* : The attribute.
// **Example usage**:
//
//     personAttrs.get("knows");
    this.get = function (id) {
        if (typeof id === 'string') {
            var lid = this.vie.namespaces.isUri(id) ? id : this.vie.namespaces.uri(id);
            return this._inherit()._attributes[lid];
        } else if (id instanceof this.vie.Attribute) {
            return this.get(id.id);
        } else {
            throw new Error("Wrong argument in VIE.Attributes.get()");
        }
    };

// ### _inherit()
// The private method ```_inherit``` creates a full list of all attributes. This includes
// local attributes as well as inherited attributes from the parents. The ranges of attributes
// with the same id will be merged. This method is called everytime an attribute is requested or
// the list of all attributes. Usually this method should not be invoked outside of the class.
// **Parameters**:
// *nothing*
// instance of a ```VIE.Attribute```.
// **Throws**:
// *nothing*
// **Returns**:
// *nothing*
// **Example usage**:
//
//     personAttrs._inherit();
    this._inherit = function () {
        var a, x, id;
        var attributes = jQuery.extend(true, {}, this._local);

        var inherited = _.map(this.domain.supertypes.list(),
            function (x) {
               return x.attributes;
            }
        );

        var add = {};
        var merge = {};
        var ilen, alen;
        for (a = 0, ilen = inherited.length; a < ilen; a++) {
            var attrs = inherited[a].list();
            for (x = 0, alen = attrs.length; x < alen; x++) {
                id = attrs[x].id;
                if (!(id in attributes)) {
                    if (!(id in add) && !(id in merge)) {
                        add[id] = attrs[x];
                    }
                    else {
                        if (!merge[id]) {
                            merge[id] = {range : [], mins : [], maxs: [], metadatas: []};
                        }
                        if (id in add) {
                            merge[id].range = jQuery.merge(merge[id].range, add[id].range);
                            merge[id].mins = jQuery.merge(merge[id].mins, [ add[id].min ]);
                            merge[id].maxs = jQuery.merge(merge[id].maxs, [ add[id].max ]);
                            merge[id].metadatas = jQuery.merge(merge[id].metadatas, [ add[id].metadata ]);
                            delete add[id];
                        }
                        merge[id].range = jQuery.merge(merge[id].range, attrs[x].range);
                        merge[id].mins = jQuery.merge(merge[id].mins, [ attrs[x].min ]);
                        merge[id].maxs = jQuery.merge(merge[id].maxs, [ attrs[x].max ]);
                        merge[id].metadatas = jQuery.merge(merge[id].metadatas, [ attrs[x].metadata ]);
                        merge[id].range = _.uniq(merge[id].range);
                        merge[id].mins = _.uniq(merge[id].mins);
                        merge[id].maxs = _.uniq(merge[id].maxs);
                        merge[id].metadatas = _.uniq(merge[id].metadatas);
                    }
                }
            }
        }

        /* adds inherited attributes that do not need to be merged */
        jQuery.extend(attributes, add);

        /* merges inherited attributes */
        for (id in merge) {
            var mranges = merge[id].range;
            var mins = merge[id].mins;
            var maxs = merge[id].maxs;
            var metadatas = merge[id].metadatas;
            var ranges = [];
            //merging ranges
            for (var r = 0, mlen = mranges.length; r < mlen; r++) {
                var p = this.vie.types.get(mranges[r]);
                var isAncestorOf = false;
                if (p) {
                    for (x = 0; x < mlen; x++) {
                        if (x === r) {
                            continue;
                        }
                        var c = this.vie.types.get(mranges[x]);
                        if (c && c.isof(p)) {
                            isAncestorOf = true;
                            break;
                        }
                    }
                }
                if (!isAncestorOf) {
                    ranges.push(mranges[r]);
                }
            }

            var maxMin = _.max(mins);
            var minMax = _.min(maxs);
            if (maxMin <= minMax && minMax >= 0 && maxMin >= 0) {
                attributes[id] = new this.vie.Attribute(id, ranges, this, maxMin, minMax, metadatas[0]);
            } else {
                throw new Error("This inheritance is not allowed because of an invalid minCount/maxCount pair!");
            }
        }

        this._attributes = attributes;
        return this;
    };

// ### toArray() === list()
// This method return an array of ```VIE.Attribute```s from the attributes instance.
// **Parameters**:
// *nothing.
// **Throws**:
// *nothing*
// **Returns**:
// *{array}* : An array of ```VIE.Attribute```.
// **Example usage**:
//
//     personAttrs.list();
    this.toArray = this.list = function (range) {
        var ret = [];
        var attributes = this._inherit()._attributes;
        for (var a in attributes) {
            if (!range || attributes[a].applies(range)) {
                ret.push(attributes[a]);
            }
        }
        return ret;
    };

    attrs = _.isArray(attrs) ? attrs : [ attrs ];
    _.each(attrs, function (attr) {
        this.add(attr.id, attr.range, attr.min, attr.max, attr.metadata);
    }, this);
};
//     VIE - Vienna IKS Editables
//     (c) 2011 Henri Bergius, IKS Consortium
//     (c) 2011 Sebastian Germesin, IKS Consortium
//     (c) 2011 Szaby Grünwald, IKS Consortium
//     VIE may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://viejs.org/
if (VIE.prototype.Namespaces) {
    throw new Error("ERROR: VIE.Namespaces is already defined. " +
        "Please check your VIE installation!");
}

// ## VIE Namespaces
//
// In general, a namespace is a container that provides context for the identifiers.
// Within VIE, namespaces are used to distinguish different ontolgies or vocabularies
// of identifiers, types and attributes. However, because of their verbosity, namespaces
// tend to make their usage pretty circuitous. The ``VIE.Namespaces(...)`` class provides VIE
// with methods to maintain abbreviations (akak **prefixes**) for namespaces in order to
// alleviate their usage. By default, every VIE instance is equipped with a main instance
// of the namespaces in ``myVIE.namespaces``. Furthermore, VIE uses a **base namespace**,
// which is used if no prefix is given (has an empty prefix).
// In the upcoming sections, we will explain the
// methods to add, access and remove prefixes.



// ## VIE.Namespaces(base, namespaces)
// This is the constructor of a VIE.Namespaces. The constructor initially
// needs a *base namespace* and can optionally be initialised with an
// associative array of prefixes and namespaces. The base namespace is used in a way
// that every non-prefixed, non-expanded attribute or type is assumed to be of that
// namespace. This helps, e.g., in an environment where only one namespace is given.
// **Parameters**:
// *{string}* **base** The base namespace.
// *{object}* **namespaces** Initial namespaces to bootstrap the namespaces. (optional)
// **Throws**:
// *{Error}* if the base namespace is missing.
// **Returns**:
// *{VIE.Attribute}* : A **new** VIE.Attribute object.
// **Example usage**:
//
//     var ns = new myVIE.Namespaces("http://viejs.org/ns/",
//           {
//            "foaf": "http://xmlns.com/foaf/0.1/"
//           });
VIE.prototype.Namespaces = function (base, namespaces) {

    if (!base) {
        throw new Error("Please provide a base namespace!");
    }
    this._base = base;

    this._namespaces = (namespaces)? namespaces : {};
    if (typeof this._namespaces !== "object" || _.isArray(this._namespaces)) {
        throw new Error("If you want to initialise VIE namespace prefixes, " +
            "please provide a proper object!");
    }
};


// ### base(ns)
// This is a **getter** and **setter** for the base
// namespace. If called like ``base();`` it
// returns the actual base namespace as a string. If provided
// with a string, e.g., ``base("http://viejs.org/ns/");``
// it sets the current base namespace and retuns the namespace object
// for the purpose of chaining. If provided with anything except a string,
// it throws an Error.
// **Parameters**:
// *{string}* **ns** The namespace to be set. (optional)
// **Throws**:
// *{Error}* if the namespace is not of type string.
// **Returns**:
// *{string}* : The current base namespace.
// **Example usage**:
//
//     var namespaces = new vie.Namespaces("http://base.ns/");
//     console.log(namespaces.base()); // <-- "http://base.ns/"
//     namespaces.base("http://viejs.org/ns/");
//     console.log(namespaces.base()); // <-- "http://viejs.org/ns/"
VIE.prototype.Namespaces.prototype.base = function (ns) {
    if (!ns) {
        return this._base;
    }
    else if (typeof ns === "string") {
        /* remove another mapping */
        this.removeNamespace(ns);
        this._base = ns;
        return this._base;
    } else {
        throw new Error("Please provide a valid namespace!");
    }
};

// ### add(prefix, namespace)
// This method adds new prefix mappings to the
// current instance. If a prefix or a namespace is already
// present (in order to avoid ambiguities), an Error is thrown.
// ``prefix`` can also be an object in which case, the method
// is called sequentially on all elements.
// **Parameters**:
// *{string|object}* **prefix** The prefix to be set. If it is an object, the
// method will be applied to all key,value pairs sequentially.
// *{string}* **namespace** The namespace to be set.
// **Throws**:
// *{Error}* If a prefix or a namespace is already
// present (in order to avoid ambiguities).
// **Returns**:
// *{VIE.Namespaces}* : The current namespaces instance.
// **Example usage**:
//
//     var namespaces = new vie.Namespaces("http://base.ns/");
//     namespaces.add("", "http://...");
//     // is always equal to
//     namespaces.base("http://..."); // <-- setter of base namespace
VIE.prototype.Namespaces.prototype.add = function (prefix, namespace) {
    if (typeof prefix === "object") {
        for (var k1 in prefix) {
            this.add(k1, prefix[k1]);
        }
        return this;
    }
    if (prefix === "") {
        this.base(namespace);
        return this;
    }
    /* checking if we overwrite existing mappings */
    else if (this.contains(prefix) && namespace !== this._namespaces[prefix]) {
        throw new Error("ERROR: Trying to register namespace prefix mapping (" + prefix + "," + namespace + ")!" +
              "There is already a mapping existing: '(" + prefix + "," + this.get(prefix) + ")'!");
    } else {
        jQuery.each(this._namespaces, function (k1,v1) {
            if (v1 === namespace && k1 !== prefix) {
                throw new Error("ERROR: Trying to register namespace prefix mapping (" + prefix + "," + namespace + ")!" +
                      "There is already a mapping existing: '(" + k1 + "," + namespace + ")'!");
            }
        });
    }
    /* if not, just add them */
    this._namespaces[prefix] = namespace;
    return this;
};

// ### addOrReplace(prefix, namespace)
// This method adds new prefix mappings to the
// current instance. This will overwrite existing mappings.
// **Parameters**:
// *{string|object}* **prefix** The prefix to be set. If it is an object, the
// method will be applied to all key,value pairs sequentially.
// *{string}* **namespace** The namespace to be set.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Namespaces}* : The current namespaces instance.
// **Example usage**:
//
//     var namespaces = new vie.Namespaces("http://base.ns/");
//     namespaces.addOrReplace("", "http://...");
//     // is always equal to
//     namespaces.base("http://..."); // <-- setter of base namespace
VIE.prototype.Namespaces.prototype.addOrReplace = function (prefix, namespace) {
    if (typeof prefix === "object") {
        for (var k1 in prefix) {
            this.addOrReplace(k1, prefix[k1]);
        }
        return this;
    }
    this.remove(prefix);
    this.removeNamespace(namespace);
    return this.add(prefix, namespace);
};

// ### get(prefix)
// This method retrieves a namespaces, given a prefix. If the
// prefix is the empty string, the base namespace is returned.
// **Parameters**:
// *{string}* **prefix** The prefix to be retrieved.
// **Throws**:
// *nothing*
// **Returns**:
// *{string|undefined}* : The namespace or ```undefined``` if no namespace could be found.
// **Example usage**:
//
//     var namespaces = new vie.Namespaces("http://base.ns/");
//     namespaces.addOrReplace("test", "http://test.ns");
//     console.log(namespaces.get("test")); // <-- "http://test.ns"
VIE.prototype.Namespaces.prototype.get = function (prefix) {
    if (prefix === "") {
        return this.base();
    }
    return this._namespaces[prefix];
};

// ### getPrefix(namespace)
// This method retrieves a prefix, given a namespace.
// **Parameters**:
// *{string}* **namespace** The namespace to be retrieved.
// **Throws**:
// *nothing*
// **Returns**:
// *{string|undefined}* : The prefix or ```undefined``` if no prefix could be found.
// **Example usage**:
//
//     var namespaces = new vie.Namespaces("http://base.ns/");
//     namespaces.addOrReplace("test", "http://test.ns");
//     console.log(namespaces.getPrefix("http://test.ns")); // <-- "test"
VIE.prototype.Namespaces.prototype.getPrefix = function (namespace) {
    var prefix;
    if (namespace.indexOf('<') === 0) {
        namespace = namespace.substring(1, namespace.length - 1);
    }
    jQuery.each(this._namespaces, function (k1,v1) {
        if (namespace.indexOf(v1) === 0) {
            prefix = k1;
        }

        if (namespace.indexOf(k1 + ':') === 0) {
            prefix = k1;
        }
    });
    return prefix;
};

// ### contains(prefix)
// This method checks, whether a prefix is stored in the instance.
// **Parameters**:
// *{string}* **prefix** The prefix to be checked.
// **Throws**:
// *nothing*
// **Returns**:
// *{boolean}* : ```true``` if the prefix could be found, ```false``` otherwise.
// **Example usage**:
//
//     var namespaces = new vie.Namespaces("http://base.ns/");
//     namespaces.addOrReplace("test", "http://test.ns");
//     console.log(namespaces.contains("test")); // <-- true
VIE.prototype.Namespaces.prototype.contains = function (prefix) {
    return (prefix in this._namespaces);
};

// ### containsNamespace(namespace)
// This method checks, whether a namespace is stored in the instance.
// **Parameters**:
// *{string}* **namespace** The namespace to be checked.
// **Throws**:
// *nothing*
// **Returns**:
// *{boolean}* : ```true``` if the namespace could be found, ```false``` otherwise.
// **Example usage**:
//
//     var namespaces = new vie.Namespaces("http://base.ns/");
//     namespaces.addOrReplace("test", "http://test.ns");
//     console.log(namespaces.containsNamespace("http://test.ns")); // <-- true
VIE.prototype.Namespaces.prototype.containsNamespace = function (namespace) {
    return this.getPrefix(namespace) !== undefined;
};

// ### update(prefix, namespace)
// This method overwrites the namespace that is stored under the
// prefix ``prefix`` with the new namespace ``namespace``.
// If a namespace is already bound to another prefix, an Error is thrown.
// **Parameters**:
// *{string}* **prefix** The prefix.
// *{string}* **namespace** The namespace.
// **Throws**:
// *{Error}* If a namespace is already bound to another prefix.
// **Returns**:
// *{VIE.Namespaces}* : The namespace instance.
// **Example usage**:
//
//     ...
VIE.prototype.Namespaces.prototype.update = function (prefix, namespace) {
    this.remove(prefix);
    return this.add(prefix, namespace);
};

// ### updateNamespace(prefix, namespace)
// This method overwrites the prefix that is bound to the
// namespace ``namespace`` with the new prefix ``prefix``. If another namespace is
// already registered with the given ``prefix``, an Error is thrown.
// **Parameters**:
// *{string}* **prefix** The prefix.
// *{string}* **namespace** The namespace.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Namespaces}* : The namespace instance.
// **Example usage**:
//
//     var namespaces = new vie.Namespaces("http://base.ns/");
//     namespaces.add("test", "http://test.ns");
//     namespaces.updateNamespace("test2", "http://test.ns");
//     namespaces.get("test2"); // <-- "http://test.ns"
VIE.prototype.Namespaces.prototype.updateNamespace = function (prefix, namespace) {
    this.removeNamespace(prefix);
    return this.add(prefix, namespace);
};

// ### remove(prefix)
// This method removes the namespace that is stored under the prefix ``prefix``.
// **Parameters**:
// *{string}* **prefix** The prefix to be removed.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Namespaces}* : The namespace instance.
// **Example usage**:
//
//     var namespaces = new vie.Namespaces("http://base.ns/");
//     namespaces.add("test", "http://test.ns");
//     namespaces.get("test"); // <-- "http://test.ns"
//     namespaces.remove("test");
//     namespaces.get("test"); // <-- undefined
VIE.prototype.Namespaces.prototype.remove = function (prefix) {
    if (prefix) {
        delete this._namespaces[prefix];
    }
    return this;
};

// ### removeNamespace(namespace)
// This method removes removes the namespace ``namespace`` from the instance.
// **Parameters**:
// *{string}* **namespace** The namespace to be removed.
// **Throws**:
// *nothing*
// **Returns**:
// *{VIE.Namespaces}* : The namespace instance.
// **Example usage**:
//
//     var namespaces = new vie.Namespaces("http://base.ns/");
//     namespaces.add("test", "http://test.ns");
//     namespaces.get("test"); // <-- "http://test.ns"
//     namespaces.removeNamespace("http://test.ns");
//     namespaces.get("test"); // <-- undefined
VIE.prototype.Namespaces.prototype.removeNamespace = function (namespace) {
    var prefix = this.getPrefix(namespace);
    if (prefix) {
        delete this._namespaces[prefix];
    }
    return this;
};

// ### toObj()
// This method serializes the namespace instance into an associative
// array representation. The base namespace is given an empty
// string as key.
// **Parameters**:
// *{boolean}* **omitBase** If set to ```true``` this omits the baseNamespace.
// **Throws**:
// *nothing*
// **Returns**:
// *{object}* : A serialization of the namespaces as an object.
// **Example usage**:
//
//     var namespaces = new vie.Namespaces("http://base.ns/");
//     namespaces.add("test", "http://test.ns");
//     console.log(namespaces.toObj());
//     // <-- {""    : "http://base.ns/",
//             "test": "http://test.ns"}
//     console.log(namespaces.toObj(true));
//     // <-- {"test": "http://test.ns"}
VIE.prototype.Namespaces.prototype.toObj = function (omitBase) {
    if (omitBase) {
        return jQuery.extend({}, this._namespaces);
    }
    return jQuery.extend({'' : this._base}, this._namespaces);
};

// ### curie(uri, safe)
// This method converts a given
// URI into a CURIE (or SCURIE), based on the given ```VIE.Namespaces``` object.
// If the given uri is already a URI, it is left untouched and directly returned.
// If no prefix could be found, an ```Error``` is thrown.
// **Parameters**:
// *{string}* **uri** The URI to be transformed.
// *{boolean}* **safe** A flag whether to generate CURIEs or SCURIEs.
// **Throws**:
// *{Error}* If no prefix could be found in the passed namespaces.
// **Returns**:
// *{string}* The CURIE or SCURIE.
// **Example usage**:
//
//     var ns = new myVIE.Namespaces(
//           "http://viejs.org/ns/",
//           { "dbp": "http://dbpedia.org/ontology/" }
//     );
//     var uri = "<http://dbpedia.org/ontology/Person>";
//     ns.curie(uri, false); // --> dbp:Person
//     ns.curie(uri, true); // --> [dbp:Person]
VIE.prototype.Namespaces.prototype.curie = function(uri, safe){
    return VIE.Util.toCurie(uri, safe, this);
};

// ### isCurie(curie)
// This method checks, whether
// the given string is a CURIE and returns ```true``` if so and ```false```otherwise.
// **Parameters**:
// *{string}* **curie** The CURIE (or SCURIE) to be checked.
// **Throws**:
// *nothing*
// **Returns**:
// *{boolean}* ```true``` if the given curie is a CURIE or SCURIE and ```false``` otherwise.
// **Example usage**:
//
//     var ns = new myVIE.Namespaces(
//           "http://viejs.org/ns/",
//           { "dbp": "http://dbpedia.org/ontology/" }
//     );
//     var uri = "<http://dbpedia.org/ontology/Person>";
//     var curie = "dbp:Person";
//     var scurie = "[dbp:Person]";
//     var text = "This is some text.";
//     ns.isCurie(uri);    // --> false
//     ns.isCurie(curie);  // --> true
//     ns.isCurie(scurie); // --> true
//     ns.isCurie(text);   // --> false
VIE.prototype.Namespaces.prototype.isCurie = function (something) {
    return VIE.Util.isCurie(something, this);
};

// ### uri(curie)
// This method converts a
// given CURIE (or save CURIE) into a URI, based on the given ```VIE.Namespaces``` object.
// **Parameters**:
// *{string}* **curie** The CURIE to be transformed.
// **Throws**:
// *{Error}* If no URI could be assembled.
// **Returns**:
// *{string}* : A string, representing the URI.
// **Example usage**:
//
//     var ns = new myVIE.Namespaces(
//           "http://viejs.org/ns/",
//           { "dbp": "http://dbpedia.org/ontology/" }
//     );
//     var curie = "dbp:Person";
//     var scurie = "[dbp:Person]";
//     ns.uri(curie);
//          --> <http://dbpedia.org/ontology/Person>
//     ns.uri(scurie);
//          --> <http://dbpedia.org/ontology/Person>
VIE.prototype.Namespaces.prototype.uri = function (curie) {
    return VIE.Util.toUri(curie, this);
};

// ### isUri(something)
// This method checks, whether the given string is a URI.
// **Parameters**:
// *{string}* **something** : The string to be checked.
// **Throws**:
// *nothing*
// **Returns**:
// *{boolean}* : ```true``` if the string is a URI, ```false``` otherwise.
// **Example usage**:
//
//     var namespaces = new vie.Namespaces("http://base.ns/");
//     namespaces.addOrReplace("test", "http://test.ns");
//     var uri = "<http://test.ns/Person>";
//     var curie = "test:Person";
//     namespaces.isUri(uri);   // --> true
//     namespaces.isUri(curie); // --> false
VIE.prototype.Namespaces.prototype.isUri = VIE.Util.isUri;
})();