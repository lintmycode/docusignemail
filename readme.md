# DocuSignEmail
A wrapper for DocuSign SDK; 90% of the time you just need to push some pdf files and request their signature by email. That exactly what this package does.

Requires `php7.4` or later.

## Getting started
You'll need to create a new JWT application with your DocuSign developer account. There you need to:
1. Get your user id
2. Get your integration key
3. Create a RSA key pair
4. Add your callback url


See `demo` dir for a working example; copy these files and fill them appropriately.

````
cd demo
cp config/ds_config.example.php /config/ds_config.php
cp config/signers.example.php /config/signers.php
cp config/ccs.example.php /config/ccs.php
````

## Usage
See `demo/index.php`.

## Running the demo
````
composer install
docker-compose up
````

(You can also run `php -S localhost:8080` locally if you have php installed)

Open `http://localhost:8080/demo` in the browser.
