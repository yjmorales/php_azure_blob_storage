<?php
/**
 * @author Yenier Jimenez <yjmorales86@gmail.com>
 */

namespace App\Service;

use App\Exception\NotFoundException;
use Exception;

/**
 * Cache used to save temporary information used to manage images on Azure Blob Storage service.
 */
class UploadCache
{
    /**
     * The cache will save the information used to manage images. That information is keyed by
     * keys prefixed by the following value.
     */
    private const CACHE_PREFIX = 'upload_image';

    /**
     * Redis manager service used to save/get values into/from redis.
     *
     * @var CacheRegistry
     */
    private $_cache;

    /**
     * The number of seconds the data is held in cache if not pruned before.
     *
     * @var int
     */
    private $_ttl;

    /**
     * UploadCache constructor.
     *
     * @param CacheRegistry $cache Redis manager service used to save/get values into/from redis.
     * @param int           $ttl   The number of seconds the data is held in cache if not pruned before.
     */
    public function __construct(CacheRegistry $cache, int $ttl)
    {
        $this->_cache = $cache;
        $this->_ttl   = $ttl;
    }

    /**
     * Saves into the cache a base64 image representation.
     *
     * @param string $imgId     The id of the image.
     * @param string $imgBase64 Holds the image base64 representation.
     *
     * @return void
     * @throws Exception
     */
    public function saveImageBase64(string $imgId, string $imgBase64): void
    {
        $key = $this->_getImgBase64Key($imgId);
        $this->_cache->set($key, $imgBase64, $this->_ttl);
    }

    /**
     * Use this function to retrieve from cache an image base64 value. If under the key is not saved any record then
     * an exception is thrown.
     *
     * @param string $imgId Holds the image id.
     *
     * @return string
     *
     * @throws NotFoundException
     * @throws Exception
     */
    public function getImageBase64(string $imgId): string
    {
        $key       = $this->_getImgBase64Key($imgId);
        $found     = true;
        $imgBase64 = $this->_cache->get($key, $found);
        if (!$found) {
            throw new NotFoundException("Unable to load the image base64 value respective to image $imgId");
        }

        return $imgBase64;
    }

    /**
     * Use this function to remove a base64 image value from the service cache.
     *
     * @param string $imgId Image identifier.
     *
     * @return void
     *
     * @throws NotFoundException
     * @throws Exception
     */
    public function removeImageBase64(string $imgId): void
    {
        $key   = $this->_getImgBase64Key($imgId);
        $found = true;
        $this->_cache->get($key, $found);
        if (!$found) {
            throw new NotFoundException("Unable to load the image base64 value respective to image: $imgId");
        }
        if (!$this->_cache->purge($key)) {
            throw new Exception("Unable to remove the base64 image respective to image $imgId");
        }
    }

    /**
     * Use this function to get from cache the value of the azure active directory token to authenticate the
     * security principal for a time period
     *
     * @return string|null
     * @throws Exception
     */
    public function getAuthToken(): ?string
    {
        $found = true;
        $token = $this->_cache->get($this->_getAuthTokenKey(), $found);

        return $found ? $token : null;
    }

    /**
     * Use this function to save into cache the value of the azure active directory token to authenticate the
     * security principal for a time period
     *
     * @param string $token Token to be saved into the cache,
     * @param int    $ttl   The ttl in seconds the value will live in the cache.
     *
     * @return void
     * @throws Exception
     */
    public function saveAuthToken(string $token, int $ttl): void
    {
        if (!$this->_cache->set($this->_getAuthTokenKey(), $token, $ttl)) {
            throw new Exception('Error saving the azure active directory authentication token into redis cache.');
        }
    }

    /**
     * Helper function to build the key under the base64 image value is saved in cache.
     *
     * @param string $imgId Represents the image id.
     *
     * @return string
     */
    private function _getImgBase64Key(string $imgId): string
    {
        return self::CACHE_PREFIX . "_$imgId";
    }

    /**
     * Helper function to generate the key under it's saved the azure active directory token to authenticate the
     * security principal for a time period.
     *
     * @return string
     */
    private function _getAuthTokenKey(): string
    {
        return self::CACHE_PREFIX . '-azure_active_directory_token_key';
    }
}