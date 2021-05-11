# skeleton-application-api
This skeleton applications automatically creates the openapi 3 specifications
and serves a SwaggerUI interface.
The specifications are created based on PHP code and its docblocks.

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
        - security

## Configuration 

The following optional configurations can be set:

|Configuration|Description|Default value|Example values|
|----|----|----|----|
|application_type|(required)Sets the application to the required type|\Skeleton\Application\Web|This must be set to \Skeleton\Application\Api|
|hostnames|(required)an array containing the hostnames to listen for. Wildcards can be used via `*`.| []| [ 'www.example.be, '*.example.be' ]|
|title|(required)The title of the API|''|'Sample Pet Store App'|
|description|A short description of the API. CommonMark syntax MAY be used for rich text representation|''|'This is a sample server for a pet store'|
|terms|A URL to the Terms of Service for the API. MUST be in the format of a URL.|''|'http://example.com/terms'|
|version|The version of the OpenAPI document (which is distinct from the OpenAPI Specification version or the API implementation version)|1.0.0||
|contact|Array with contact information. The following keys are supported: 'name', 'url', 'email'|[]|[ 'name' => 'My name', 'url' => 'http://company.website', 'email' => 'me@company.website']|
