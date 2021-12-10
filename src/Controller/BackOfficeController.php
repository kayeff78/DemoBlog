<?php

namespace App\Controller;

use App\Form\ArticleType;
use App\Entity\Article;
use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class BackOfficeController extends AbstractController
{
    # Méthode qui affiche la page Home du backOffice
    #[Route('/admin', name: 'app_admin')]
    public function index() : Response
    {
        return $this->render('back_office/index.html.twig', [
            'controller_name' => 'BackOfficeController',
        ]);
    }

    #[Route('/admin/articles', name: 'app_admin_articles')]
    #[Route('/admin/articles/{id}/remove', name: 'app_admin_articles_remove')]
    public function adminArticles(EntityManagerInterface $manager, ArticleRepository $repoArticle, Article $artRemove = null) : Response
    {
        // dd($artRemove);
        $colonnes = $manager->getClassMetadata(Article::class)->getFieldNames();
        // dd($colonnes);



        /*  
            Exo: afficher sous forme de tableau HTML l'ensemble des articles stockés en BDD
            1.Selectionner en BDD l'ensemble de la table 'article' et transmettre le résultat à la méthode render()
            2.Dans le template 'admin_articles.html.twig', mettre en forme l'affichage des articles dans un tableau HTML
            3.Afficher le nom de la catégorie de chaque article
            4.Afficher le nb de commentaire de chaque article
            5.Prévoir un lien modification /suppression pour chaque article

            
        */
      
        
            $articles = $repoArticle->findAll();

            //  dd($articles);

            //traitement supression de la BDD
            if($artRemove)
            {  
                 //Avant de supprimer l'article dans la BDD, on stock son ID afin de l'intégrer dans le message de validation de supression (addFlash)
                $id= $artRemove->getId();

                $manager->remove($artRemove);
                $manager->flush();

                $this->addFlash('success', "L'article n° $id a été suprimé avec succès.");

                return $this->redirectToRoute('app_admin_articles');
            }

            return $this->render('back_office/admin_articles.html.twig', [ 'colonnes' => $colonnes,
                     'articles' => $articles

        ]);  
    }
    /*
        Exo: création d'une nouvelle méthode permettant d'insérer et de modifier 1 article en BDD
        1.Créer une route '/admin/article/add' (name:app_admin_article_add)
        2.Créer la méthode adminArticleForm()
        3.Créer un nouveau template 'admin_article_form.html.twig'
        4.Importer et créer le formulaire au sein de la méthode adminArticleForm() (createForm)
        5.Afficher le formulaire sur le template 'admin_article_form.html.twig'
        6.Gerer l'upload d'image
        7.Dans la méthode adminArticleForm(), réaliser le traitement permettant d'insérer un nouvel article en BDD (persist() / flush())

    */
    #[Route('/admin/article/add', name: 'app_admin_article_add')]
    #[Route('/admin/article/{id}/edit', name: 'admin_edit')]
    public function adminArticleForm(Article $article = null, Request $request, EntityManagerInterface $manager, SluggerInterface $slugger) : Response
    {

        if($article)
        {
            $photoActuelle = $article->getPhoto();
        }
        
        if(!$article)
        {
            $article = new Article;            
        }

        $formArticle = $this->createForm(ArticleType::class, $article);

        $formArticle->handleRequest($request);

        if($formArticle->isSubmitted() && $formArticle->isValid())
        {
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
                $secureNomPhoto = $slugger->slug($nomOriginePhoto);

                $nouveauNomFichier = $secureNomPhoto.' - '.uniqid().'.'.$photo->guessExtension();
                
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

            $this->addFlash('success', "L'article a été $txt avec succès !");

            $manager->persist($article);

            $manager->flush();

            return $this->redirectToRoute('blog_show',['id' => $article->getId()]);
        }

        return $this->render('admin_article_form.html.twig',[
            'formArticle' => $formArticle->createView(), 
            'editMode' => $article->getId(),
            'photoActuelle' => $article->getPhoto()
        ]);
    }    
    /*
    Exo: affichage et suppression catégorie
    1.Création d'une nouvelle route 'admin/categories' (name: app_admin_categories)
    2.Création d'une nouvelle méthode adminCategories()
    3.Création d'un nouveau template 'admin_categories.html.twig'
    4.Selectionner les noms des champs/colonnes de la table Category, les transmettre au template et les afficher
    5.selctionner dans le controller l'ensemble de la table 'category' (findAll) et transmettre au template (render)  et les afficher sur le template (twig), afficher également le nbre d'article liés a chaque catégorie
    6.Prévoir un lien 'modifier' et 'supprimer' pour chaque catégorie
    7.Réaliser le traitement permettant de supprimer une catégorie de la BDD
    */

    #[Route('/admin/categories', name: 'app_admin_categories')]
    public function adminCategories(EntityManagerInterface $manager, CategoryRepository $Repositorycategory): Response
    {  
            $colonnes = $manager->getClassMetadata(Category::class)->getFieldNames();
       
            $allCategory = $Repositorycategory->findAll();

        
     return $this->render('admin_categories.html.twig', [
         'colonnes' => $colonnes,
         'allCategory' => $allCategory
        ]);
    }
}
