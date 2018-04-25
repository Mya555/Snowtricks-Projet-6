<?php

namespace App\Controller;

use App\Form\ImageType;
use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Entity\Tricks;
use App\Form\CommentEditType;
use App\Form\CommentType;
use App\Form\TricksType;
use App\Form\TricksEditType;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Form\UserType;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Validator\Constraints\Image;


class TricksController extends Controller
{
   /// AFFICHER UNE FIGURE ///

    /**
     * @Route("/figure/{id}", name="show")
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */

    public function show(Request $request ,$id)


    {
        /* Récuperation de la figure triées par $id */

        $trick = $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository(Tricks::class)
        ->find($id);

        /* Création du commentaire lié à la figure affichée */

        $comment = new Comment();
        $comment->setTricks($trick);
        $form = $this->get('form.factory')->create(CommentType::class, $comment);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('show', array('id' => $trick->getId($id)));
        }

        if (!$trick) {
            throw new NotFoundHttpException(
                'Aucun résultat ne correspond à votre recherche'
            );
        }
        /** @var TYPE_NAME $this */
        return $this->render('show.html.twig', array('trick' => $trick,  'form' => $form->createView(), 'id' => $trick->getId($id), 'comment' => $comment));
    }



    /// AFFICHER UNE LISTE DE FIGURES ///

    /**
     * @Route("/liste_add", name="list_add")
     */
    public function listAdd()
    {
        /* Récuperation de toutes les figures */

       $tricks =  $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository(Tricks::class)
            ->findAll();

        /** @var TYPE_NAME $tricks */
        return $this->render('listAdd.html.twig',  array('tricks' => $tricks, 'repository' => $repository ));
    }


    /// AJOUTER UNE FIGURE ///

    /**
     * @Route("/ajout", name="add")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function add(Request $request)
    {
        /* Création d'une nouvelle figure */

        $trick = new Tricks();
        $form   = $this->createForm(TricksType::class, $trick);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // $file stock l'image chargée
            $files = $request->files->get('tricks')['images'];
            foreach( $files  as $key => $file ){
            $fileName = $this->generateUniqueFilename() . '.' . $file->guessExtension();

            // Déplace le fichier dans le répertoire où sont stockées les images
            $file->move($this->getParameter('img_directory'), $fileName);

            $image = new Image();
            // Met à jour la propriété 'images' pour stocker le nom du fichier , au lieu de son contenu
            $image->setPath($fileName);
            $trick->addImage($image);
             $em = $this->getDoctrine()->getManager();
             $em->persist($image);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($trick);
            $em->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

            return $this->redirectToRoute('show', array('id' => $trick->getId()));

        }
        return $this->render('add.html.twig', array(
            'form' => $form->createView()
        ));
    }





    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        // md5 () réduit la similarité des noms de fichiers générés par
        // uniqid (), basé sur timestamps(horodatages)
        return md5(uniqid());
    }


    /// EDITER UNE FIGURE ///

    /**
     * @Route("/editer/{id}", name="edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function edit($id, Request $request)
    {
        /* Edition d'une figure */

        $em = $this->getDoctrine()->getManager();
        $trick = $em->getRepository(Tricks::class)->find($id);

        if (null === $trick) {
            throw new NotFoundHttpException("Cette page n'existe pas");
        }
        $form = $this->get('form.factory')->create(TricksEditType::class, $trick);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

            $em->flush();
            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');

            return $this->redirectToRoute('show', array('id' => $trick->getId()));
        }
        return $this->render('edit.html.twig', array(
            'trick' => $trick,
            'form'   => $form->createView(),
        ));
    }


    /// SUPPRIMER UNE FIGURE ///

    /**
     * @Route("/supprimer/{id}", name="delete")
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete($id)
    {
        /* Récuperation de la figure */

        $em = $this->getDoctrine()->getManager();
        $tricks = $em->getRepository(Tricks::class)->find($id);

        if (null === $tricks) {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }
        $em->remove($tricks);
        $em->flush();

        return $this->redirectToRoute('list');
    }


}
