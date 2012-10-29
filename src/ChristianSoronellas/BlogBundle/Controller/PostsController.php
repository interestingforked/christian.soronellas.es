<?php

namespace ChristianSoronellas\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use ChristianSoronellas\BlogBundle\Form\CommentType;
use ChristianSoronellas\BlogBundle\Entity\Post;
use ChristianSoronellas\BlogBundle\Entity\Comment;

/**
 * The Posts controller
 *
 */
class PostsController extends Controller
{
    /**
     * @Route("/", name="root")
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
     * Renders a post
     *
     * @Route("/{year}/{month}/{day}/{slug}", name="post")
     * @Template()
     */
    public function postAction($slug)
    {
        $post = $this->getDoctrine()->getRepository('ChristianSoronellasBlogBundle:Post')->findOneBySlug($slug);

        if (!$post || Post::STATE_COMPLETE != $post->getState()) {
            throw $this->createNotFoundException('The post doesn\'t exists!');
        }

        $form = $this->createForm(new CommentType());
        if (null !== ($commentId = $this->getRequest()->get('commentTo'))) {
            $comment = new Comment();
            $comment->setParentComment($this->getDoctrine()->getRepository('ChristianSoronellasBlogBundle:Comment')->find((int) $commentId));
            $form->setData($comment);
        }

        return array('post' => $post, 'form' => $form->createView());
    }

    /**
     * Adds a new comment to a given post
     *
     * @var \ChristianSoronellas\BlogBundle\Entity\Post $post
     * @Route("/post/{slug}/comment", name="post_comment")
     * @Template("ChristianSoronellasBlogBundle:Posts:post.html.twig")
     * @Method("post")
     */
    public function commentAction($slug)
    {
        $post = $this->getDoctrine()->getRepository('ChristianSoronellasBlogBundle:Post')->findOneBySlug($slug);

        if (false === $post->getCommentsEnabled()) {
            $this->get('session')->setFlash('notice', 'Comments on this entry are disabled!');

            return $this->redirect(
                    $this->generateUrl(
                            'post',
                            array(
                                    'day'   => $post->getCreatedAt()->format('d'),
                                    'month' => $post->getCreatedAt()->format('m'),
                                    'year'  => $post->getCreatedAt()->format('Y'),
                                    'slug'  => $post->getSlug()
                            )
                    )
            );
        }


        $form = $this->createForm(new CommentType());
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            // OK! Proceed to save the new comment to the database!
            $em = $this->getDoctrine()->getManager();

            $comment = $form->getData();
            $comment->setPost($post);

            // Akismet filtering
            $comment->setState(Comment::STATE_APPROVED);
            if ($this->get('ornicar_akismet')->isSpam(array('comment_author' => $comment->getName(), 'comment_content' => $comment->getBody()))) {
                $comment->setState(Comment::STATE_IS_SPAM);
            }

            $em->persist($comment);

            $post->addComment($comment);

            $em->persist($post);
            $em->flush();

            $this->get('session')->getFlashBag()->add('notice', 'Your comment has been saved succesfully!');

            return $this->redirect(
                $this->generateUrl(
                    'post',
                    array(
                        'day'   => $post->getCreatedAt()->format('d'),
                        'month' => $post->getCreatedAt()->format('m'),
                        'year'  => $post->getCreatedAt()->format('Y'),
                        'slug'  => $post->getSlug()
                    )
                )
            );
        }

        return array('post' => $post, 'form' => $form->createView());
    }
}
