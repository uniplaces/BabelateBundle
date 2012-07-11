Local Installation
==================

### Prerequisites

Install:

* Apache 2.2
* MongoDB 1.6
* PHP 5.3
* Symfony 2.x

Tested on MacOS X 10.7.4

### Configuring Babelate

In BabelateBundle/Resources/config/parameters.yml.sample you can find the main parameters Babelate relies on.

You can also find the doctrine configuration in BabelateBundle/Resources/config/config.yml.sample

Define each bundle you will be translating along with it's directory as in the sample file.

Before you can use the web service be sure to run:
    
    app/console uniplaces:babelate:import
