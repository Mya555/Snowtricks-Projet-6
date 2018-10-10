<?php
namespace App\Controller;
use App\Repository\ImageRepository;
use App\Repository\TricksRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\MediaVideo;
use App\Entity\Video;
use App\Form\ImageType;
use App\Entity\Comment;
use App\Entity\Image;
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
use Symfony\Component\HttpFoundation\JsonResponse;
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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
class TricksController extends Controller
{
    /// AFFICHER UNE FIGURE ///
    /**
     * @Route("/figure/{id}", name="show")
     * @param TokenStorageInterface $tokenStorage
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function show(TokenStorageInterface $tokenStorage, Request $request ,$id)
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
        //Récuperation de l'utilisateur connecté
        $form = $this->get('form.factory')->create(CommentType::class, $comment);
        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $comment->setAuthor($tokenStorage->getToken()->getUser());
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
            if ($files){
                foreach( $files  as $key => $file ){
                    $fileName = $this->generateUniqueFilename() . '.' . $file['file']->guessExtension();
                    // Déplace le fichier dans le répertoire où sont stockées les images
                    $file['file']->move($this->getParameter('img_directory'), $fileName);
                    $image = new Image();
                    $image->setPath($fileName);
                    $image->setTricks($trick);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($image);
                }
            }
            $listVideo =  $request->get('tricks')['mediaVideos'];
            if ($listVideo){
                foreach ( $listVideo as $video)
                {
                    $mediaVideo = new MediaVideo();
                    $mediaVideo->setUrl($video['url']);
                    $mediaVideo->setTrick($trick);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($mediaVideo);
                }
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($trick);
            $em->flush();
            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');
            return $this->redirectToRoute('show', array('id' => $trick->getId(), 'trick' => $trick));
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
     * @param TricksRepository $repo
     * @param $id
     * @param Tricks $trick
     * @param Request $request
     * @param ObjectManager $manager
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function edit(TricksRepository $repo, $id, Tricks $trick = null, Request $request, ObjectManager $manager)
    {
        // Récupération des tricks pour l'affichage de multimédia associée
        $trick = $repo->find($id);

        if (null === $trick) {
            throw new NotFoundHttpException("Cette page n'existe pas");}
        $form = $this->createForm(TricksEditType::class, $trick);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $listVideo =  $request->get('tricks')['mediaVideos'];
            if ($listVideo){
                foreach ( $listVideo as $video)
                {
                    $mediaVideo = new MediaVideo();
                    $mediaVideo->setUrl($video['url']);
                    $mediaVideo->setTrick($trick);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($mediaVideo);
                }
            }
            $manager->persist($trick);
            $manager->flush();
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
        return $this->redirectToRoute('list_add');
    }
    /// SUPPRIMER UNE IMAGE ///
    /**
     * @Route("/supprimerImage/{id}", name="deleteImage")
     * @param Image $image
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteImage(Image $image, Request $request){
        if (null === $image) {
            throw new NotFoundHttpException("Imposible de supprimer la vidéo.");
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($image);
        $em->flush();
        $request->getSession()->getFlashBag()->add('notice', 'L\'image a bien été supprimée.');
        return $this->redirectToRoute('list_add');
    }
    /// SUPPRIMER UNE VIDEO ///

    /**
     * @Route("/supprimerVideo/{id}", name="deleteVideo")
     * @param MediaVideo $mediaVideo
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteVideo(MediaVideo $mediaVideo, Request $request){

        if (null === $mediaVideo) {
            throw new NotFoundHttpException("Imposible de supprimer la vidéo.");
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($mediaVideo);
        $em->flush();
        $request->getSession()->getFlashBag()->add('notice', 'La vidéo a bien été supprimée.');
        return new JsonResponse('Le vidéo a bien été supprimée.');
    }
}