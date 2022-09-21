<?php
/**
 * @author Yenier Jimenez <yjmorales86@gmail.com>
 */

namespace App\Service;

use ArrayObject;

/**
 * Class acting as an input validator.
 */
class UploadImageValidator
{
    /**
     * Validates the given base64 value is valid.
     *
     * @param mixed       $base64 Holds the value to validate
     * @param ArrayObject $errors If any error is got then this will hold the respective error message.
     *
     * @return bool True if valid, otherwise false.
     */
    public function validate($base64, ArrayObject $errors): bool
    {
        $valid = true;

        if (empty($base64)) {
            $valid = false;
            $errors->append('The image base64 is required.');
        }
        if (!is_string($base64)) {
            $valid = false;
            $errors->append('The image base64 should be a valid string.');
        }

        return $valid;
    }
}