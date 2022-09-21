<?php
/**
 * @author Yenier Jimenez <yjmorales86@gmail.com>
 */

namespace App\Exception;

use Exception;

/**
 * Exception thrown whenever an item is not found within an Azure Blog Storage Account, either a container or a blob.
 */
class AzureBlobStorageNotFoundException extends Exception
{

}