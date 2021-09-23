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
  - The docblock for the class definition will be used as a description for the path
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

### Body

If the path accepts a body, it should be declared in the docblock via

    @body \App\Api\Component\Mybody $custom_body

The body-class is a component and needs to implement the specific methods
required for a component.

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
Components should implement the following interface:

    \Skeleton\Application\Api\ComponentInterface

For a skeleton-object this can be achieved by simply using the trait

    \Skeleton\Application\Api\Component

The trait will implement the following methods that can be overridden:

	public function get_openapi_media_type(): \Skeleton\Application\Api\Media\Type;

Returns the full Media\Type object for the object. By default it returns 
a media type with type 'object' and all properties returned by 
get_openapi_component_properties
	
	public function get_openapi_component_name(): string;

Returns a friendly name for the component. By default the name is extracted
from the component classname. The namespace from the app is ignored.
	
	public function get_openapi_component_properties(): array;

Returns an array with Media\Type objects. The key of each element in the array
becomes the name of the property.
	
	public function get_openapi_additional_properties();

If the component has optional properties, they can be returned in this method.
The array should have the same structure as the one returned by 
get_openapi_component_properties: an array of Media\Type objects.

	public function get_openapi_description(): string;

A description for the component.
	
	public function get_openapi_example(): array;

An example for the component.

	public function get_openapi_component_info(): array;

This method returns the actual object in its correct syntax described in
get_openapi_component_properties

### Skeleton-object

In case your component class is derived from an object which is a
'\Skeleton\Object\Model', you can use the already defined trait:

    \Skeleton\Application\Api\Component;

With this trait, the output will match the complete object such as defined in
your database.

### Media types

To define a component, an array of media types needs to be returned.

#### Integer

	$media_type = new \Skeleton\Application\Api\Media\Type();
	$media_type->type = 'integer';
	$media_type->format = 'int64'; // Optional, possible values 'int32', 'int64'

#### Number

	$media_type = new \Skeleton\Application\Api\Media\Type();
	$media_type->type = 'number';
	$media_type->format = 'float'; // Optional, possible values 'float', 'double'

#### String

	$media_type = new \Skeleton\Application\Api\Media\Type();
	$media_type->type = 'string';
	$media_type->format = 'date'; // Optional, possible values 'date', 'date-time', 'password', 'byte', 'binary', 'uuid', ...

#### Object

	$media_type = new \Skeleton\Application\Api\Media\Type();
	$media_type->type = 'object';
	$media_type->value_type = '\App\Api\Component\ClassA';

#### Array

	$media_type = new \Skeleton\Application\Api\Media\Type();
	$media_type->type = 'array';

	$value_type = new \Skeleton\Application\Api\Media\Type();
	$value_type->type = 'object';
	$value_type->value_type = '\App\Api\Component\MyObject';

	$media_type->value_type = $value_type;


#### Mixed media types

	$media_type_mixed = new \Skeleton\Application\Api\Media\Type\Mixed();

	$media_type1 = new \Skeleton\Application\Api\Media\Type();
	$media_type1->type = 'object';
	$media_type1->value_type = '\App\Api\Component\Class1';
	$media_type_mixed->add_media_type($media_type1);

	$media_type2 = new \Skeleton\Application\Api\Media\Type();
	$media_type2->type = 'object';
	$media_type2->value_type = '\App\Api\Component\Class2';
	$media_type_mixed->add_media_type($media_type2);

	$media_type3 = new \Skeleton\Application\Api\Media\Type();
	$media_type3->type = 'object';
	$media_type3->value_type = '\App\Api\Component\Class3';
	$media_type_mixed->add_media_type($media_type3);
	$media_type_mixed->criteria = 'anyOf'; // Possible values 'oneOf', 'allOf', 'anyOf'

#### Extra properties

Media types can have the following other properties:

	description: a description of the media type
	required: boolean, indicates if the media type is required
	nullable: boolean, can the value be null

