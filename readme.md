# DocuSignEmail
A wrapper for DocuSign SDK; 90% of the time you just need to push some pdf files and request their signature by email. That exactly what this package does.

For `php7.0`. Uses older versions of everything. Somehow it works.

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
From `demo/index.php`:
````
require './config/ds_config.php';
require './config/signers.php';
require './config/ccs.php';
require '../src/DocuSignEmail.php';

try {
  $ds = new DocuSignEmail();

  $ds->setJWTToken(
    $ds_config['ds_client_id'], 
    $ds_config['ds_impersonated_user_id'], 
    $ds_config['private_key_file'], 
    $ds_config['authorization_server'],
    $ds_config['redirect_uri']
  );

  $ds->addDocuments([
    ['name' => 'demo 1', 'path' => 'docs/demo1.pdf'],
    ['name' => 'demo 2', 'path' => 'docs/demo2.pdf'],
  ]);

  $ds->setSigners($signers);
  $ds->setCC($ccs);

  $envelope_id = $ds->send();
  echo 'ok; <hr>';
  echo $envelope_id;

} catch(\Exception $ex) {
  echo 'error; <hr>';
  echo $ex->getMessage();
}
````

## Running the demo
````
docker-compose build --no-cache
docker-compose run app composer update
docker-compose up
````

(You can also run `php -S localhost:8080` locally if you have php installed)

Open `http://localhost:8080/demo` in the browser.
