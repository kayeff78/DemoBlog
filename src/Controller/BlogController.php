<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Commentaire;
use App\Form\ArticleType;
use App\Form\CommentaireType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class BlogController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        // méthode rendu : en fonction de la route l'URL, la méthode render() envoi un template, un rendu sur le navigateur
        return $this->render('blog/home.html.twig',[
            'title' => 'Bienvenue sur le blog Symfony',
            'age' => 25
        ]);
    }

    #[Route('/blog', name: 'blog')]
    public function blog(ArticleRepository $repoArticle): Response
    {
        /*
            Injection de dépendances : c'est un des fondement de Symfony, ici notre méthode dépend de la classe ArticleRepository pour fonctionner correctement
            Ici Symfony comprend que la méthode blog() attend un argument objet issu de la classe ArticleRepository, automatiquement Symfony envoi une instance de cette classe en argument de cette classe
            $repoArticle est un objet issu de la classe ArticleRepository

            Symfony est une application qui est capable de répondre à un navigateur lorseque celui-ci appel une adresse (ex: localhost:8000/blog), le controller doit etre capable d'envoyer
            un rendu, un template sur le navigateur

            Ici, lorseque l'on transmet la route '/blog' dans l'URL, cela execute la méthode index() dans le controller qui renvoie le template '/blog/index.html.twig' sur le navigateur
        
            Pour selectionner des données en BDD, nous devons passer par une class Repository, ses classes permettant uniquement d'executer des requetes de selection SELECT en BDD, contient des
            méthodes mise à dispositions par Symfony (findAll(), find(), findBy(), etc...)

            Ici nous devons importer au sein de notre controller la classe Article Repository pour pouvoir selectionner dans la table Article
            $repoArticle est un objet issu de la classe ArticleRepository
            getRepository() est une méthode issue de l'objet Doctrine permettant ici d'importer la classe ArticleRepository
        */

        //$repoArticle = $doctrine->getRepository(Article::class);

        // dump() / dd() : outil de débug de symfony
        //dd($repoArticle);

        // findAll() : méthode issue de la classe ArticleRepository permettant de selectionner l'ensemble de la table SQL et de récuperer un talbeau multi contenant l'ensemble des articles
        $articles = $repoArticle->findAll(); // SELECT * FROM article + FETCH_ALL
        // dump($articles);

        // dd($articles);

        return $this->render('blog/blog.html.twig',[
            'articles' => $articles
        ]);
    }

    #[Route('/blog/new', name: 'blog_create')]
    #[Route('/blog/{id}/edit', name: 'blog_edit')]
    public function blogCreate(Article $article = null, Request $request, EntityManagerInterface $manager, SluggerInterface $slugger): Response
    {
        // Si les données dans le tableau ARRAY $_POST sont supérieur à 0 alors on entre dans la condition
        // if($request->request->count() > 0)
        // {


        //     // Pour insérer dans la table SQL 'article', nous avons besoin d'un objet de son entité correspondante
        //     $article = new Article;
        //     $article->setTitre($request->request->get('titre'));
        //     $article->setContenu($request->request->get('contenu'));
        //     $article->setPhoto($request->request->get('photo'));
        //     $article->setDate(new \DateTime());

        //     // dd($article);

        //     $manager->persist($article);

        //     $manager->flush();
        // }

        // Si la variable est null, cela veut dire que nous sommes sur la route '/blog/new', on entre dans la condition et on crée une nouvelle instance de l'entité Article
        if($article)
        {
            $photoActuelle = $article->getPhoto();
        }
        
        if(!$article)
        {
            $article = new Article;            
        }

        // $article->setTitre("Titre nul");
        // $article->setContenu("Contenu null");


        $formArticle = $this->createForm(ArticleType::class, $article);

        // $article->setTitre($_POST['titre'])
        // $article->setContenu($_POST['contenu'])
        // handleRequest() permet d'envoyer chaque donnée de $_POST et de les transmettre aux bon setter de l'objet entité $article
        $formArticle->handleRequest($request);

        if($formArticle->isSubmitted() && $formArticle->isValid())
        {
            // Le champs date n'existe pas en tant que formulaire
            if(!$article->getId())
            {
                $article->setDate(new \DateTime());
                $txt = "enregistré";
            }
            else
            {
                $txt = "modifié";
            }
            
            $photo = $formArticle->get('photo')->getData();

            if($photo)
            {
                $nomOriginePhoto = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                //dd($nomOriginePhoto);

                // cela est necessaire pour inclure en toute sécurité le nom du fichier dans l'URL
                $secureNomPhoto = $slugger->slug($nomOriginePhoto);

                $nouveauNomFichier = $secureNomPhoto.' - '.uniqid().'.'.$photo->guessExtension();
                // dd($nouveauNomFichier);
                try
                {
                    $photo->move(
                        $this->getParameter('photo_directory'),
                        $nouveauNomFichier
                    );
                }
                catch(FileException $e)
                {

                }

                $article->setPhoto($nouveauNomFichier);

            }
            else
            {
                $article->setPhoto($photoActuelle);
            }

            //dd($article);
            $this->addFlash('success', "L'article a été $txt avec succès !");

            $manager->persist($article);

            $manager->flush();

            // Une fois l'insertion/modification terminé, on redirige l'internaute vers l'article
            return $this->redirectToRoute('blog_show',['id' => $article->getId()]);
        }

        return $this->render('blog/blog_create.html.twig',[
            'formArticle' => $formArticle->createView(), // on transmet le formulaire au tempalte afin de pouvoir l'afficher en twig
            // createView() : Retourne un petit objet qui représente
            'editMode' => $article->getId(),
            'photoActuelle' => $article->getPhoto()
        ]);
    }
    
    // Méthode permettant d'afficher le détail d'un article
    // On définit une route 'paramètrée' {id}, ici la route permet de recevoir l'id d'un article stocké en BDD
    #[Route('/blog/{id}', name: 'blog_show')]
    public function blogShow(Request $request, Article $article, EntityManagerInterface $manager) : Response
    {
        $user= $this->getUser();
        // dd($user);
       

        $commentaire = new Commentaire;

        $formComment = $this->createForm(CommentaireType::class, $commentaire);


        $formComment->handleRequest($request);

        if($formComment->isSubmitted() && $formComment->isValid())
        {

            $commentaire->setDate(new \DateTime());
            $commentaire->setArticle($article);

            $this->addFlash('success', "Le commentaire a été ajouter avec succès !");

            $manager->persist($commentaire);

            $manager->flush();

            return $this->redirectToRoute('blog_show',['id' => $article->getId()]);
        }


        return $this->render('blog/blog_show.html.twig',[
            'article' => $article,
            'formComment' => $formComment->createView()
        ]);
    }
   
}
