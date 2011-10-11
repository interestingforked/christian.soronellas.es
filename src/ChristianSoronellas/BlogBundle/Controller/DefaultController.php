<?php

namespace ChristianSoronellas\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        $posts = $this->getDoctrine()
                      ->getRepository('ChristianSoronellasBlogBundle:Post')
                      ->findAll();
        
        return array('posts' => $posts);
    }
    
    /**
     * @param type $id
     * 
     * @Route("/post/{id}")
     * @Template()
     */
    public function postAction($id)
    {
        
    }
}