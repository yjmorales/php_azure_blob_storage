<?php
/**
 * @author Yenier Jimenez <yjmorales86@gmail.com>
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class holding the endpoint route to upload the image to Azure Blob Storage and the page to visualize the uploaded
 * image.
 *
 * @package App\Controller
 */
class IndexController extends AbstractController
{
    /**
     * Route definition to visualize the uploaded image to Azure.
     * @Route("/", name="index")
     */
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    /**
     * Route definition to upload an image to Azure.
     * @Route("/upload", name="upload")
     */
    public function upload(): JsonResponse
    {
        return new JsonResponse();
    }
}
