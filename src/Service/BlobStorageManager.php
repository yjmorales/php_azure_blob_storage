<?php
/**
 * @author Yenier Jimenez <yjmorales86@gmail.com>
 */

namespace App\Service;

use App\Exception\AzureBlobStorageException;
use App\Exception\AzureBlobStorageNotFoundException;
use Exception;
use Symfony\Component\HttpKernel\KernelInterface;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\BlobRestProxy as BlobStorageConnection;

/**
 * Class responsible to manage the resources of the Azure Blob Storage account.
 */
class BlobStorageManager
{
    /**
     * The containers used to save the image in Azure Blob Storage service are prefixed by the
     * following string. The reason is to identify via name the reason of the container within the Blob Storage Account.
     */
    private const CONTAINER_PREFIX = 'general-images';

    /**
     * Holds the authenticator to open a connection with the Azure Blob Storage service.
     *
     * @var BlobStorageAuthenticator
     */
    private $_bsAuthenticator;

    /**
     * Holds the connection with the Azure Blob Storage service.
     *
     * @var BlobStorageConnection
     */
    private $_bsConnection;

    /**
     * Symfony kernel interface used to build the temporary directory where to download the images.
     *
     * @var KernelInterface
     */
    private $_kernel;

    /**
     * Holds the Azure BZLob container name used to save the images.
     *
     * @var string
     */
    private $_containerName;

    /**
     * BlobStorageManager constructor.
     *
     * @param BlobStorageAuthenticator $bsAuthenticator Holds the authenticator to open a connection with the Azure
     *                                                  Blob Storage service.
     * @param string                   $containerName   Holds the Azure BZLob container name used to save the images.
     * @param KernelInterface          $kernel          Symfony kernel interface used to build the temporary directory
     *                                                  where to download the images.
     */
    public function __construct(
        BlobStorageAuthenticator $bsAuthenticator,
        string $containerName,
        KernelInterface $kernel
    ) {
        $this->_bsAuthenticator = $bsAuthenticator;
        $this->_containerName   = $containerName;
        $this->_kernel          = $kernel;
    }

    /**
     * Use this method to obtain the image base64 value.
     *
     * @param string $imgId Value that identifies the image.
     *
     * @return string
     *
     * @throws AzureBlobStorageNotFoundException
     * @throws AzureBlobStorageException
     */
    public function getImage(string $imgId): string
    {
        /*
         * Getting the container information
         */

        $this->connect();
        if (!$this->_existsContainer($this->_containerName)) {
            throw new AzureBlobStorageNotFoundException("There is not defined the container");
        }
        $options = new ListBlobsOptions();
        $options->setPrefix($imgId);
        $blobs      = $this->_bsConnection->listBlobs($this->_containerName, $options)->getBlobs();
        $blobsCount = count($blobs);

        /*
         * Validating container content
         */
        if (!$blobsCount) {
            throw new AzureBlobStorageNotFoundException("There is not an image identifier by $imgId");
        }

        if ($blobsCount > 1) {
            throw new AzureBlobStorageException("There are more than one image identified by $imgId");
        }

        /*
         * Retrieving the content.
         */
        try {
            $blobName     = (head($blobs))->getName();
            $tmpDir       = $this->_initTmpDir();
            $fileName     = "$tmpDir/$blobName";
            $blobResource = $this->_bsConnection->getBlob($this->_containerName, $blobName)->getContentStream();
            file_put_contents($fileName, $blobResource);
        } catch (Exception $e) {
            throw new AzureBlobStorageException("Unable to obtain the image identifier by $imgId", 0, $e);
        }

        return base64_encode(file_get_contents($fileName)); // Finally returns the respective image base64 value for rendering purposes.
    }

    /**
     * Function to create a blob inside the specified container.
     *
     * @param string $imgId       Identifies the image.
     * @param string $imageBase64 Holds the content of the blob to be created.
     *
     * @return void
     * @throws AzureBlobStorageException
     */
    public function uploadImage(string $imgId, string $imageBase64): void
    {
        try {
            $this->connect();
            $this->_createContainer();
            $blobName = $this->_buildBlobName($imgId);
            $this->_bsConnection->createBlockBlob($this->_containerName, $blobName, base64_decode($imageBase64));
        } catch (Exception $e) {
            throw new AzureBlobStorageException("Unable to upload the image $imgId to Blob Storage Service", 0, $e);
        }
    }

    /**
     * Helper function to establish the connection with the Azure Blob Storage service. If the connection is already
     * established the process is skipped.
     *
     * @return void
     * @throws AzureBlobStorageException
     */
    private function connect(): void
    {
        if ($this->_bsConnection) {
            return;
        }
        $this->_bsConnection = $this->_bsAuthenticator->getBsConnection();
    }

    /**
     * Helper function to build and return the blob name respective to an image.
     *
     * @param string $imgId Image identifier.
     *
     * @return string
     */
    private function _buildBlobName(string $imgId): string
    {
        return "$imgId-" . self::CONTAINER_PREFIX;
    }

    /**
     * Use this function to create a container within the Azure Blob Storage account. If the container is already
     * created then the process is skipped.
     *
     * @return void
     * @throws AzureBlobStorageException
     */
    private function _createContainer(): void
    {
        $this->connect();

        if ($this->_existsContainer($this->_containerName)) {
            return;
        }
        $this->_bsConnection->createContainer($this->_containerName);
    }

    /**
     * Helper function to determinate that exists a container named with the given value.
     *
     * @param string $containerName Container name to compare the container existence.
     *
     * @return bool If the container exists `true` is returned, otherwise false.
     * @throws AzureBlobStorageException
     */
    private function _existsContainer(string $containerName): bool
    {
        $this->connect();

        $listContainers = $this->_bsConnection->listContainers();
        $containers     = $listContainers->getContainers();
        foreach ($containers as $container) {
            if ($container->getName() === $containerName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper function to obtain the temporary directory where to save the images for rendering.
     */
    private function _initTmpDir(): string
    {
        $tmpDir = "{$this->_kernel->getCacheDir()}/img_azure";
        if (!(file_exists($tmpDir))) {
            mkdir($tmpDir, 0770, true);
        }

        return $tmpDir;
    }
}