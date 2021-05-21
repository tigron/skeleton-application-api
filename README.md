# skeleton-application-api
This skeleton applications will create an openapi application in your skeleton
project. The openapi specification file will automatically created based on
the code (using reflection and docblock).
A SwaggerUI interface is automatically served so the API can be tested.

## Installation

Installation via composer:

    composer require tigron/skeleton-application-api

## Setup the application

Your Openapi application should follow the following directory structure:

    - App-directory from skeleton-core
      - Your app name
        - component
        - config
        - endpoint
        - exception (optional)
        - security (optional)

It is important to understand that every class that is created should be in 
their correct namespace. The following namespaces should be used:

    component: \App\{APP_NAME}\Component
    endpoint: \App\{APP_NAME}\Endpoint
    exception: \App\{APP_NAME}\Exception
    security: \App\{APP_NAME}\Security

## Configuration

The following configurations can be set:

|Configuration|Description|Default value|Example values|
|----|----|----|----|
|application_type|(required)Sets the application to the required type|\Skeleton\Application\Web|This must be set to \Skeleton\Application\Api|
|hostnames|(required)an array containing the hostnames to listen for. Wildcards can be used via `*`.| []| [ 'www.example.be, '*.example.be' ]|
|title|(required)The title of the API|''|'Sample Pet Store App'|
|description|A short description of the API. CommonMark syntax MAY be used for rich text representation|''|'This is a sample server for a pet store'|
|terms|A URL to the Terms of Service for the API. MUST be in the format of a URL.|''|'http://example.com/terms'|
|version|The version of the OpenAPI document (which is distinct from the OpenAPI Specification version or the API implementation version)|1.0.0||
|contact|Array with contact information. The following keys are supported: 'name', 'url', 'email'|[]|[ 'name' => 'My name', 'url' => 'http://company.website', 'email' => 'me@company.website']|

## Endpoints

Endpoints will determine the tags and paths that will be created in the openapi
application. Every endpoint needs to extend from \Skeleton\Application\Api\Endpoint
Let's look at an example:


    <?php
    namespace App\Openapi\Endpoint;
    
    /**
     * Operations on users
     */
    class User extends \Skeleton\Application\Api\Endpoint {

	    /**
    	 * Authenticate a user and initialise the session
    	 *
    	 * @access public
    	 * @param string $username The username
    	 * @param string $password The password
    	 * @return string Login successful
    	 */
    	public function post_login($username, $password) {
    		// Do your check here
    		return "Login successful";
    	}
	}


This endpoint will create a tag in the specification that contains 1 path:

	POST /user?action=login
	
Every method should follow this naming schema:

    {HTTP_OPERATION}_{ACTION}({REQUIRED_PARAM1}, {REQUIRED_PARAM2})	

In the class, docblock data is used to:
  - The docblock for the class definition will be used as a description for the tag
  - The docblock for the method will be used as a description for the tag
  - The docblock for the method will be used to specify the input en output parameters, the possible exceptions and the security mechanism that needs to be used

### Parameters

Every parameter that can be accepted should be defined via "@param {TYPE} {NAME} {DESCRIPTION}"
The type can be a primitive datatype (int, integer, boolean, float) or an 
array an array of primitive datatypes.

A definition of an array:
    @param string[] $names

### Return variables

It is required to specify a return variable. This return variable will be used
in a succesful HTTP 200 response. 
The return variable can be a primitive datatypes or a Component. (See 
Components for more information).
Arrays of primitive datatypes and arrays of components are also supported.

### Exceptions

If the path can cause an exception, it should be defined in the docblock via
the @throws docblock. The exception should be of type 
\Skeleton\Application\Api\Exception or an extending class. Ex

    @throws \Skeleton\Application\Api\Exception

### Security

If authentication is required for the path, it needs to be specified via 
docblock:

    @security \Skeleton\Application\Api\Security

### Headers

If the path will return a header, it can be specified via:

    @header X-My-Custom-Header

### Routes

The automatically generated url for your path is not always a clean url. An 
array with route information can clean this url. 
Ex:

	POST /user?action=edit&id={ID}

For this url, a "routes" configuration item can be made in the application
config:

    '\App\Openapi\Endpoint\User' => [
    	'/user/$action/$id',
    ],

By defining this route, the url will be rewritten as:

    POST /user/edit/{ID} 


### Advanced
    
All paths in the endpoint are all grouped under the same tag. The name of the
tag is automatically generated based on the classname of the endpoint. 
If for some reason you want to modify the name, this can be done by providing
the following method:

    /**
     * Overwrite automatic naming
     *
     * @access public
     * @return string $name
     */
    public function _get_name() {
    	return 'customName';
    }

## Component

Components are the equivalent of openapi schema's. They define objects that can
be reused in endpoints.
Components should implement the following 2 methods:

    /**
     * Get component_info
     *
     * @access public
     * @return array $info
     */	
    public function get_component_info() {
    	$info = [];
    	$info['id'] = $this->id;
    	$info['name'] = $this->name;
        return $info;
    }

    /**
     * Get properties
     *
     * @access public
     * @return array $properties
     */
    public function get_component_properties() {
    	$properties = [];

		// id
		$media_type = new \Skeleton\Application\Api\Media\Type();
		$media_type->type = 'string';
		$media_type->format = 'uuid';
		$properties['id'] = $media_type;

		// name
		$media_type = new \Skeleton\Application\Api\Media\Type();
		$media_type->type = 'string';
		$properties['name'] = $media_type;

		return $properties;    
    }

Method 'get_component_properties()' should return an array of 
\Skeleton\Application\Api\Media\Type to describe each property. The key of the
array is the name of the property.

Method 'get_component_info()' is used to return the actual content whenever 
this object needs to be returned. It is important that the structure of this
array matches the structure of 'get_component_properties()'.

### Skeleton-object

In case your component class is derived from an object which is a 
'\Skeleton\Object\Model', you can use the already defined trait:

    \Skeleton\Application\Api\Component;

With this trait, the output will match the complete object such as defined in
your database.

