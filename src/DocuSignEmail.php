<?php

/**
 * Manages and sends documents for electronic signature via DocuSign.
 *
 * The `DocuSignEmail` class provides a comprehensive interface for configuring
 * and sending documents for electronic signature using the DocuSign API. It includes
 * methods for setting up authentication via JWT tokens, adding documents to be signed,
 * specifying signers and carbon copy recipients, and sending the documents for signing.
 * This class acts as a wrapper around the DocuSign API to simplify the process of
 * electronic document signing within PHP applications.
 *
 * Usage requires a valid DocuSign developer account and appropriate API credentials,
 * including an integration key and a private key for JWT authentication. The class
 * is designed to be used in a server-side environment where PHP is installed and
 * configured to make outbound HTTPS requests.
 *
 * Key Features:
 * - JWT authentication setup for DocuSign API access.
 * - Adding documents to an envelope with support for multiple document formats.
 * - Configuring signers and CC recipients with flexible signing options.
 * - Sending documents for electronic signature and tracking their status.
 *
 * Note: This class requires the DocuSign eSignature API PHP client and is intended
 * to be used with proper error handling to manage exceptions thrown by API calls.
 *
 * @package DocuSignIntegration
 */

require __DIR__ . "/../vendor/autoload.php";

use DocuSign\eSign\Client\ApiClient;
use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Api\EnvelopesApi\CreateEnvelopeOptions;
use \DocuSign\eSign\Model\EnvelopeDefinition;
use \DocuSign\eSign\Model\Document;
use \DocuSign\eSign\Model\Signer;
use \DocuSign\eSign\Model\CarbonCopy;
use \DocuSign\eSign\Model\SignHere;
use \DocuSign\eSign\Model\Tabs;
use \DocuSign\eSign\Model\Recipients;
use \DocuSign\eSign\Configuration;

class DocuSignEmail 
{
  protected static string $accessToken;
  protected static string $accountId;
  protected static array $signers;
  protected static array $ccs;
  protected static ApiClient $apiClient;
  protected static EnvelopeDefinition $envelopeDefinition;

  /**
   * Constructor for initializing an instance of the class with an envelope definition.
   *
   * This constructor creates a new EnvelopeDefinition object. This envelope definition is then used for preparing
   * documents to be sent for signing via DocuSign.
   *
   * @param string $subject Optional. The subject line to be used in the email that will
   *                        be sent to recipients of the DocuSign envelope. Defaults to
   *                        'Please sign this document set' if not specified.
   */
  public function __construct($subject = 'Please sign this document set') 
  {
    self::$envelopeDefinition = new EnvelopeDefinition([
      'email_subject' => $subject
    ]);
  }

  /**
   * Sets the JWT token for authentication.
   * 
   * This function configures the JWT token using the specified integration key,
   * impersonated user ID, RSA private key, and redirect URI. It is used for
   * authenticating API requests that require secure access.
   *
   * @param string $integrationKey The integration key (also known as Client ID) provided by the service.
   * @param string $impersonatedUserId The user ID that is being impersonated for API access.
   * @param string $rsaPrivateKey The path to RSA private key used to sign the JWT, ensuring that the token is securely generated.
   * @param string $authServerURI The URI to the authentication server.
   * @param string $redirectURI The URI to redirect to after authentication.
   *
   * @return void
   */
  public function setJWTToken($integrationKey, $impersonatedUserId, $rsaPrivateKey, $authServerURI, $redirectURI) 
  {
    $scopes = "signature impersonation";
    $integrationKey = $integrationKey;
    $impersonatedUserId = $impersonatedUserId;
    $rsaPrivateKey = $rsaPrivateKey;

    try {
      self::$apiClient = new ApiClient();
      self::$apiClient->getOAuth()->setOAuthBasePath($authServerURI);
      $response = self::$apiClient->requestJWTUserToken(
        $integrationKey, $impersonatedUserId, file_get_contents($rsaPrivateKey), $scopes, 60);

      // retrieve token and our API account Id
      self::$accessToken = $response[0]['access_token'];
      $info = self::$apiClient->getUserInfo(self::$accessToken);
      self::$accountId = $info[0]["accounts"][0]["account_id"];

    } catch (\Throwable $th) {
      // we found consent_required in the response body meaning first time consent is needed
      if (strpos($th->getMessage(), "consent_required") !== false) {
        $authorizationURL = 'https://account-d.docusign.com/oauth/auth?' . http_build_query([
          'scope'         => $scopes,
          'redirect_uri'  => $redirectURI,
          'client_id'     => $integrationKey,
          'response_type' => 'code'
        ]);

        header("Location: " . $authorizationURL);
      }
      else {
        throw $th;
      }
    }
  }

  /**
   * Adds PDF documents to the envelope definition from provided files.
   *
   * This function takes an array of documents, each represented by an associative array
   * with details about the document's path and name.
   *
   * @param array $documents An array of associative arrays, each containing 'path'
   *                         and 'name' keys. 'path' should be the filesystem path to
   *                         the PDF document, and 'name' is the name to assign to the
   *                         document within the envelope.
   *
   * @throws \Throwable Throws an exception if reading a document fails or
   *                    if any other error occurs during the processing of documents.
   *
   * @return void 
   */
  public function addDocuments($documents = []) 
  {
    $ds_documents = [];
    for ($i = 0; $i < count($documents); $i++) {
      $document = $documents[$i];
      try {
        $ds_documents[] = new Document([
          'document_base64' => base64_encode(file_get_contents($document['path'])),
          'name' => $document['name'],
          'file_extension' => 'pdf',
          'document_id' => $i + 1
        ]);
      } catch (\Throwable $th) {
        throw $th;
      }
    }

    self::$envelopeDefinition->setDocuments($ds_documents);
  }

  /**
   * Sets the signers for a document signing request.
   *
   * This function initializes DS signer objects based on an array of signers' information,
   * setting up each signer with their email, name, recipient ID, routing order, and
   * sign here tab positions. If an error occurs during the signer setup, the function will rethrow
   * the caught exception.
   *
   * @param array $signers An array of arrays, each containing the details of a signer. Expected keys
   *                       in each array include 'email', 'name', and 'sign_anchor', with 'sign_anchor'
   *                       specifying the location for the signature within the document.
   *                       Default is an empty array.
   * @throws \Throwable Throws any throwable caught during the signer setup process.
   */
  public function setSigners($signers = []) 
  {
    for ($i = 0; $i < count($signers); $i++) {
      $signer = $signers[$i];
      try {
        $ds_signer = new Signer([
          'email' => $signer['email'],
          'name' => $signer['name'],
        ]);
        $ds_sign_here = new SignHere([
          'anchor_string' => $signer['sign_anchor'],
          'anchor_units' => 'pixels',
          'anchor_y_offset' => '10', 
          'anchor_x_offset' => '20'
        ]);
        $ds_signer->setTabs(new Tabs([
          'sign_here_tabs' => [$ds_sign_here]
        ]));

        self::$signers[] = $ds_signer;
      } catch (\Throwable $th) {
        throw $th;
      }
    }
  }

  /**
   * Sets the CC (Carbon Copy) recipients for a document signing request.
   *
   * This function iterates over an array of CC recipient information, creating a new
   * CC object for each with specified email and name. These CC are intended
   * to receive a copy of the document once the signing process is complete.
   *
   * @param array $ccs An array of arrays, with each inner array containing 'email' and 'name'
   *                   keys for a CC recipient. Default is an empty array.
   * @throws \Throwable Throws any throwable caught during the CC setup process.
   */
  public function setCC($ccs = []) 
  {
    for ($i = 0; $i < count($ccs); $i++) {
      $cc = $ccs[$i];
      try {
        $ds_ccs = new CarbonCopy([
          'email' => $cc['email'],
          'name' => $cc['name'],
        ]);
        self::$ccs[] = $ds_ccs;
      } catch (\Throwable $th) {
        throw $th;
      }
    }
  }

  /**
   * Sends the envelope with the specified recipients and document configuration.
   *
   * This function prepares the envelope by setting its recipients, including signers and CCs,
   * and configuring its status to 'sent'. It then initializes the API client with predefined
   * configuration settings, including the API host and authorization headers. Using the
   * EnvelopesApi, it creates and sends the envelope based on the defined `$envelopeDefinition`
   * and additional options. If successful, the function returns the unique envelope ID
   * of the sent envelope.
   *
   * It utilizes global static variables for envelope definition, signers, CCs, API access token,
   * and account ID, which should be set before calling this function.
   *
   * @return string The unique ID of the sent envelope.
   * @throws \Throwable Throws any throwable caught during the envelope sending process,
   *                    including API and configuration errors.
   */
  public function send() 
  {
    try {
      // set recipients
      $id = 1;
      foreach (self::$signers as $signer) {
        $signer->setRecipientId($id);
        $signer->setRoutingOrder($id);
        $id++;
      }
      foreach (self::$ccs as $cc) {
        $cc->setRecipientId($id);
        $cc->setRoutingOrder($id);
        $id++;
      }
      $recipients = new Recipients([
        'signers' => self::$signers, 
        'carbon_copies' => self::$ccs
      ]);
      
      self::$envelopeDefinition->setRecipients($recipients);
      self::$envelopeDefinition->setStatus('sent');

      // create config objects
      $config = new Configuration();
      $config->setHost("https://demo.docusign.net/restapi");
      $config->addDefaultHeader('Authorization', 'Bearer ' . self::$accessToken);

      $apiClient = new ApiClient($config);
      $envelopeApi = new EnvelopesApi($apiClient);

      $options = new CreateEnvelopeOptions();
      $options->setMergeRolesOnDraft("true");
      $options->setChangeRoutingOrder("true");
      
      // send and return on success
      $envelopeResponse = $envelopeApi->createEnvelope(self::$accountId, self::$envelopeDefinition, $options);
      return $envelopeResponse->getEnvelopeId();

    } catch (\Throwable $th) {
      throw $th;
    }
  }

}
