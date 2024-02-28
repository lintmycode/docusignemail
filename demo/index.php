<?php

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