<?php
/**
 * @author Yenier Jimenez <yjmorales86@gmail.com>
 */

namespace App\Controller;

use App\Exception\AzureBlobStorageException;
use App\Model\ImageModel;
use App\Service\BlobStorageManager;
use App\Service\UploadImageValidator;
use ArrayObject;
use Exception;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class holding the endpoint route to upload the image to Azure Blob Storage.
 *
 * @Route("/upload")
 */
class UploadController extends AbstractController
{
    /**
     * Route definition to uploaded image to Azure.
     * @Route("/", name="upload_index")
     */
    public function indexUpload(): Response
    {
        return $this->render('upload/upload.html.twig');
    }

    /**
     * Route definition to upload an image to Azure.
     * @Route("/run", name="upload_run")
     */
    public function upload(Request $request, BlobStorageManager $bsManager, LoggerInterface $logger): JsonResponse
    {
        /*
         * Validating the base64 value.
         *
         * This is the way to retrieve the base64 value from the request.
         *      $imageBase64   = $request->query->get('imageBase64');
         *
         * In this example we will use a defined base64 image as example.
         */
        $imageBase64   = ImageModel::EXAMPLE_BASE64;

        $valid         = (new UploadImageValidator())->validate($imageBase64, $errors = new ArrayObject());
        $data          = new stdClass();
        $data->success = $valid;
        $data->errors  = $errors;
        $data->code    = $valid ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

        if (!$valid) {
            return new JsonResponse($data);
        }
        /*
         * Uploading the image to azure
         */
        try {
            $bsManager->uploadImage(ImageModel::IMAGE_ID, $imageBase64);
            $logger->notice('A new image has been uploaded to Azure Blob Storage Service');
        } catch (AzureBlobStorageException $e) {

            $logger->error('There was an error uploading the image to Azure Blob Storage', ['exception' => $e]);
        } catch (Exception $e) {
            $logger->error("Internal Server Error uploading the image to azure", ['exception' => $e]);
        }

        return new JsonResponse($data);
    }
}