<?php
/**
 * @author Yenier Jimenez <yjmorales86@gmail.com>
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class holding the landing page to upload and render the image to/from Azure Blob Storage
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
        return $this->render('index/index.html.twig');
    }

}