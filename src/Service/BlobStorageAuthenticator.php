<?php
/**
 * @author Yenier Jimenez <yjmorales86@gmail.com>
 */

namespace App\Service;

use App\Exception\AzureBlobStorageException;
use Exception;
use GuzzleHttp\Psr7\Utils;
use MicrosoftAzure\Storage\Blob\BlobRestProxy as BlobStorageConnection;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

/**
 * This class is responsible to authenticate the Azure Blob Storage Account.
 * Once a connection is established it can be used to perform all operations.
 * The authentication is done by using Azure Directory Service (AD).
 *
 * @link https://docs.microsoft.com/en-us/azure/storage/common/storage-auth-aad-app?toc=%2Fazure%2Fstorage%2Fblobs%2Ftoc.json&tabs=dotnet
 */
class BlobStorageAuthenticator
{
    /**
     * Base URL used by Azure Active Directory to generate new security principal tokens.
     *
     * @link https://docs.microsoft.com/en-us/azure/app-service/configure-authentication-provider-aad
     */
    const AZURE_AD_TOKEN_GENERATOR_BASE_URL = 'https://login.microsoftonline.com/';

    /**
     * Represents the resource we want a token for plus `/.default` in order to get a token for the permissions that
     * have been granted in the tenant for this app on that resource.
     *
     * @link https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-permissions-and-consent
     */
    const AZURE_STORAGE_SCOPE = 'https://storage.azure.com/.default';

    /**
     * Azure Storage Account name used to manage the blobs and containers. Used to build the account connection.
     *
     * @var string
     */
    private $_accountName;

    /**
     * Protocol used to connect the Azure Storage Account. Used to build the account connection.
     *
     * @var string
     */
    private $_protocol;

    /**
     * Token used to authenticate the account in the Azure Blob Storage service.
     *
     * @var string
     */
    private $_token;

    /**
     * Holds the connection used to manage the Azure Storage Account.
     *
     * @var BlobStorageConnection
     */
    private $_bsConnection;

    /**
     * Used to get new tokens from Azure Active Directory to communicate with Blob Storage Service. The tokens are
     * requested by hitting the Azure AD Api.
     *
     * @var GuzzleClient
     */
    private $_guzzleClient;

    /**
     * Cache used to save/retrieve azure active directory authentication tokens.
     *
     * @var UploadCache
     */
    private $_cache;

    /**
     * Azure AD tenant. The tenant ID identifies the Azure AD tenant to use for authentication. This value is needed
     * to get a new OAuth token to get access to Blob Storage resources.
     *
     * @link https://docs.microsoft.com/en-us/azure/storage/common/storage-auth-aad-app?tabs=dotnet#register-your-application-with-an-azure-ad-tenant
     *
     * @var string
     */
    private $_tenantId;

    /**
     * Azure AD provides a client ID (also called an application ID) used to associate the application with Azure AD
     * at runtime.
     *
     * @link https://docs.microsoft.com/en-us/azure/active-directory/develop/app-objects-and-service-principals
     *
     * @var string
     */
    private $_clientId;

    /**
     * The application needs a client secret to prove its identity when requesting a token.
     * This value represents that argument.
     *
     * @link https://docs.microsoft.com/en-us/azure/storage/common/storage-auth-aad-app?tabs=dotnet#create-a-client-secret
     *
     * @var string
     */
    private $_clientSecret;

    /**
     * BlobStorageAuthenticator constructor.
     *
     * @param string      $accountName                           Azure Storage Account name used to manage the blobs
     *                                                           and
     *                                                           containers. Used to build the account connection.
     * @param string      $protocol                              Protocol used to connect the Azure Storage Account.
     *                                                           Used to build the account connection.
     * @param UploadCache $cache                                 Cache used to save/retrieve azure active directory
     *                                                           authentication tokens.
     * @param string      $clientId                              Azure AD provides a client ID (also called an
     *                                                           application ID) used to associate the application with
     *                                                           Azure AD at runtime.
     * @param string      $tenantId                              Azure AD tenant. The tenant ID identifies the Azure AD
     *                                                           tenant to use for authentication. This value is needed
     *                                                           to get a new OAuth token to get access to Blob Storage
     *                                                           resources.
     * @param string      $clientSecret                          The application needs a client secret to prove its
     *                                                           identity when requesting a token. This value
     *                                                           represents that argument.
     */
    public function __construct(
        string $accountName,
        string $protocol,
        UploadCache $cache,
        string $clientId,
        string $tenantId,
        string $clientSecret
    ) {
        $this->_accountName  = $accountName;
        $this->_protocol     = $protocol;
        $this->_guzzleClient = new GuzzleClient(['base_uri' => self::AZURE_AD_TOKEN_GENERATOR_BASE_URL]);
        $this->_cache        = $cache;
        $this->_clientId     = $clientId;
        $this->_tenantId     = $tenantId;
        $this->_clientSecret = $clientSecret;
    }

    /**
     * Use this function to get the Azure Blob Storage connection. If the connection is already created then it is
     * returned, otherwise it's created and returned.
     *
     * @return BlobStorageConnection
     * @throws AzureBlobStorageException
     */
    public function getBsConnection(): BlobStorageConnection
    {
        $this->_authenticate();

        return $this->_bsConnection;
    }

    /**
     * Helper function to authenticate access to Azure blobs using Azure Active Directory. If the authentication is
     * never done a fresh connection is built. If the token expired and a new one has been generated the connection is
     * refreshed.
     *
     * @return void
     * @throws AzureBlobStorageException
     */
    private function _authenticate(): void
    {
        if (!$this->_token) {
            $this->_refreshConnection($this->_getAuthToken());

            return;
        }

        $previousToken = $this->_token;
        $this->_token  = $this->_getAuthToken();
        $tokenChanged  = $previousToken !== $this->_token;
        if ($tokenChanged) {
            $this->_refreshConnection($this->_token);
        }
    }

    /**
     * Helper function to build the Azure Blob Storage connection using Azure Active Directory.
     * Please refer to @link https://docs.microsoft.com/en-us/azure/storage/common/storage-auth-aad
     * for authenticate access to Azure blobs using Azure Active Directory.
     *
     * @param string $token
     */
    private function _refreshConnection(string $token)
    {
        $connString          = "DefaultEndpointsProtocol={$this->_protocol};AccountName={$this->_accountName};";
        $this->_bsConnection = BlobStorageConnection::createBlobServiceWithTokenCredential($token, $connString);
    }

    /**
     * Use this function to get the token needed to authenticate the security principal on Azure Active Directory. That
     * authentication is necessary to perform jobs on Azure Blob Storage.
     * The token when generated is valid for a number of seconds. That value is used to save the token in cache.
     * That way it can be reused for future request. When that time passed then a new token is requested.
     *
     * @return string
     * @throws AzureBlobStorageException
     */
    private function _getAuthToken(): string
    {
        /*
         * If token had been saved in cache before and it's valid then use it.
         */
        if ($token = $this->_cache->getAuthToken()) {
            return $token;
        }

        /*
         * Otherwise generate a new token.
         */
        try {
            $endPoint = "{$this->_tenantId}/oauth2/v2.0/token";
            $response = $this->_guzzleClient->request('GET', $endPoint, [
                    'verify'      => false,
                    'headers'     => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'form_params' => [
                        'grant_type'    => 'client_credentials',
                        'client_id'     => $this->_clientId,
                        'scope'         => self::AZURE_STORAGE_SCOPE,
                        'client_secret' => $this->_clientSecret,
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            throw new AzureBlobStorageException('Unable to authenticate the security principal on Azure Active Directory',
                0, $e);
        }

        $responseData = json_decode(Utils::copyToString($response->getBody()), true) ?? [];
        $token        = $responseData['access_token'] ?? null;
        $expiresIn    = $responseData['expires_in'] ?? null;

        if (!$token || !$expiresIn) {
            throw new Exception('The response returned by Azure Active Directory is not a valid response.');
        }

        $this->_cache->saveAuthToken($token, $expiresIn);

        return $token;
    }
}