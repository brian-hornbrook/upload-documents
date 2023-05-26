<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use App\Form\PostType;
use App\Form\ImageType;
use App\Controller\safeFilename;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\File;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'images')]
    public function images(PostRepository $posts): Response
    {
        $images = $posts->findAll();
        // dd($posts->findAll());

        return $this->render('images.html.twig', ['images' => $images]);
    }

    // pdf
    #[Route('/addpdf', name: 'add_pdf')]
    public function addPDF(Request $request, PostRepository $posts, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $form = $this->createFormBuilder($post)
            ->add('title')
            ->add('image', FileType::class, [
                'label' => 'upload an image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF document',
                    ])
                ],
            ])
            ->add('upload', SubmitType::class)
            ->getForm();
        
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $brochureFile = $form->get('image')->getData();

            if($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename. '-' .uniqid().'.'. $brochureFile->guessExtension();

                try {
                    $brochureFile->move(
                        $this->getParameter('image_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {

                }
                $post->setImage($newFilename);
            }

            return $this->redirectToRoute('images');
        }

        return $this->render('addimage.html.twig',['form' => $form]);
    }

    // image
    #[Route('/addimage', name: 'add_image')]
    public function addImage(Request $request, PostRepository $posts, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $form = $this->createFormBuilder($post)
            ->add('title')
            ->add('image', FileType::class, [
                'label' => 'upload an image'
            ])
            ->add('upload', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $File = $post->getImage();
            $brochureFile = $form->get('image')->getData();

            if($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename. '-' .uniqid().'.'. $brochureFile->guessExtension();

                try {
                    $brochureFile->move(
                        $this->getParameter('image_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {

                }

                $post->setImage($newFilename);
            }
            return $this->redirectToRoute('images');
        }
        return $this->render('addimage.html.twig',['form' => $form]);
    }
}
