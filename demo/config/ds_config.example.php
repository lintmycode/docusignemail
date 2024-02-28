<?php
$ds_config = [
  'ds_client_id' => 'xxx', // the app's DocuSign integration key
  'ds_impersonated_user_id' => 'xxx',  // the id of the user (https://admindemo.docusign.com/apps-and-keys)
  'authorization_server' => 'account-d.docusign.com',
  'private_key_file' => __DIR__ .'/../keys/private.key',
  'redirect_uri' => 'http://localhost:8080/demo/index.php', // likely to be current url
];
