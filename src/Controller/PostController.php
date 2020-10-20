<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class PostController extends AbstractController
{
    /**
     * @Route("/new-post", name="new-post")
     */
    public function index(Request $request, SluggerInterface $slugger)
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $brochureFile = $form->get('pic')->getData();

            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $brochureFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                    throw new Exception('Ha ocurrido un error.');
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $post->setPic($newFilename);
            }    

            //get logged user
            $user = $this->getUser();
            $post->setUser($user);

            //save post in db
            $em = $this->getDoctrine()->getManager();
            $em->persist($post);  
            $em->flush();  

           
            
           // return $this->redirectToRoute('home');
        }

        return $this->render('post/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/post/{id}", name="post")
     */
    public function VerPost($id)
    {
        $em = $this->getDoctrine()->getManager();
        $post = $em->getRepository(Post::class)->find($id);
        return $this->render('post/post.html.twig', [
            'post' => $post,
        ]);
    }

    /**
     * @Route("/me", name="misposts")
     */
    public function MisPost() 
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $posts = $em->getRepository(Post::class)->findBy(['user'=>$user]);
        return $this->render('post/me.html.twig',['posts'=>$posts]);
    }
}
