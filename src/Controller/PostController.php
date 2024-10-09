<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Rubrik;
use App\Entity\Comment;
use App\Form\CommentFormType;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use App\Repository\RubrikRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class PostController extends AbstractController


{
    private $repo;
    private $emi;

    public function __construct(PostRepository $repo, EntityManagerInterface $emi)
    {
        $this->repo = $repo;
        $this->emi = $emi;
    }

    // Gestion de l'affichage de la page d'accueil (index)
    #[Route('/', name: 'app_post')]
    public function index(): Response
    {
        $posts = $this->repo->findBy([], ['createdAt' => 'DESC'], 4);

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    //GESTION DE LA RECUPERATION DU DETAIL D'UN POST(TOTALITE D'UN POST)+Commentaires

    #[Route('/post/{id}/{slug}', name: 'show', requirements: ['id' => '\d+'])]
    public function showone(Post $post, Request $req, CommentRepository $crepo): Response
    {
        $comment = new Comment();
        $commentForm = $this->createForm(CommentFormType::class, $comment);
        $commentForm->handleRequest($req);

        // Soumettre le commentaire via le formulaire
        if ($commentForm->isSubmitted()) {
            if ($commentForm->isValid()) {
                // Vérifier si l'utilisateur est connecté
                if (!$this->getUser()) {
                    $this->addFlash('warning', 'Vous devez être connecté pour laisser un commentaire.');
                    return $this->redirectToRoute('app_login');
                }

                // Persister les données du commentaire
                $comment->setUser($this->getUser());
                $comment->setPost($post);
                $comment->setCreatedAt(new \DateTimeImmutable());

                try {
                    $this->emi->persist($comment);
                    $this->emi->flush();

                    // Rediriger pour éviter la resoumission du formulaire
                    return $this->redirectToRoute('show', [
                        'id' => $post->getId(),
                        'slug' => $post->getSlug()
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'envoi du commentaire.');
                    dump($e->getMessage()); // Pour debug
                }
            } else {
                // Affichage des erreurs en cas d'invalidité
                dump($commentForm->getErrors(true));
            }
        }

        // Récupération des commentaires pour affichage
        $allComments = $crepo->findByPostOrderedByCreatedAtDesc($post->getId());

        return $this->render('show/show.html.twig', [
            'post' => $post,
            'comments' => $allComments,
            'comment_form' => $commentForm->createView(),
        ]);
    }
    //Gestion de l'affichage des rubriques
    #[Route('/rubrik/{id}', name: 'posts_by_rubrik')]
    public function postsByRubrik(Rubrik $rubrik, $id, PostRepository $postRepository, RubrikRepository $rubrikRepository): Response
    {
        $rubrik = $rubrikRepository->find($id);
        if (!$rubrik) {
            throw $this->createNotFoundException('rubrik not found');
        }

        $posts = $postRepository->findByRubrik($rubrik);


        return $this->render('rubrik/rubrik.html.twig', [
            'rubrik' => $rubrik,
            'posts' => $posts
        ]);
    }
}
