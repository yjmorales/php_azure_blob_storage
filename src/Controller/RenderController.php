<?php
/**
 * @author Yenier Jimenez <yjmorales86@gmail.com>
 */

namespace App\Controller;

use App\Exception\AzureBlobStorageNotFoundException;
use App\Model\ImageModel;
use App\Service\BlobStorageManager;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class responsible to render the uploaded image to azure blob storage service
 *
 * @Route("/render")
 */
class RenderController extends AbstractController
{
    /**
     * Route definition to render the uploaded image to Blob Storage.
     * @Route("/", name="render_index")
     */
    public function renderImage(BlobStorageManager $bsManager, LoggerInterface $logger): Response
    {
        /*
         * Loading the Recurring Account Signature from azure.
         */
        $imageBase64 = null;
        try {
            $imageBase64 = $bsManager->getImage(ImageModel::IMAGE_ID);
        } catch (AzureBlobStorageNotFoundException $e) {
            // This means that any image has been uploaded yet
        } catch (Exception $e) {
            $logger->error('Unable to render the uploaded image to Blob Storage.', ['exception' => $e,]);
            throw new Exception('An error occurs trying to display an image from Azure Blog Storage.');
        }

        return $this->render('render/render.html.twig', ['imageBase64' => $imageBase64,]);
    }
}