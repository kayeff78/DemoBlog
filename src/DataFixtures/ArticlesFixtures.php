<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Commentaire;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ArticlesFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        //la boucle tourne 10 fois afin de créer 10 articles FICTIFS dans la BDD
        // for($i = 1; $i <= 10; $i++)
        // {
        //     // Pour insérer des données dans la table SQL Article, nous sommes obligé de passer par sa classe Entity correspondant 'App\Entity\Article', cette classe est le reflet de la table
        //     // SQL, elle contient des propriété identique aux champs/colonnes de la table SQL

        //     $article = new Article;
        
        //     // On éxecute tout les setters de l'objet afin de remplir les objets et d'ajouter un titre, contenu, image etc pour nos 10 articles

        //     $article->setTitre("Titre de l'article N°$i");
        //     $article->setContenu("<p> Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolorum mollitia repellendus fuga non sequi repudiandae optio? Fuga asperiores totam repudiandae.</p>");
        //     $article->setPhoto("https://picsum.photos/id/239/300/600");
        //     $article->setDate(new \DateTime());

        //     // Nous faisons à l'objet $manager ObjectManager
        //     // Une classe permet entre autre de manipuler les lignes SQL de la BDD (Insert, Update, Delete)
        //     // persist() : méthode issue de la classe ObjectManager (ORM Doctrine) permettant de garder en mémoire les 10 objet $articles et de préparer les requetes SQL
        //     $manager->persist($article);
        //     // $r = $bdd->prepare($article->getTitre(),$article->getContenu(),etc...);
        // }

        // // $product = new Product();
        // // $manager->persist($product);


        // // flush() : méthode issue de la classe ObjectManager (ORM Doctrine ) permettant véritablement d'éxecuter les requetes SQL en BDD
        //$manager->flush();


        // on importe la librairie Faker pour les fixtures, cela nous permet de créer des faux articles, catégories, commentaire plus évolué
        $faker = \Faker\Factory::create('fr_FR');

        // création de 3 catégories
        for($cat = 1; $cat <= 3; $cat++)
        {
            $category = new Category;
            $category->setTitre($faker->word);
            $category->setDescription($faker->paragraph());
            $manager->persist($category);



            // Création de 4 à 10 article par catégorie
            for($art = 1; $art <= mt_rand(4,10); $art++)
            {
                $article = new Article;
                $contenu = '<p>'.join('</p><p>',$faker->paragraphs(5)).'</p>';
                $article->setTitre($faker->sentence(2));
                $article->setContenu($contenu);
                $article->setPhoto(null);
                $article->setDate($faker->dateTimeBetween('-6 months'));
                $article->setCategory($category); // On relie les articles aux categories déclarée ci-dessus, le setter attend en argument l'objet entité pour créer la clé étrangère et non un int
                
                $manager->persist($article);

                for($cmt = 1; $cmt <= mt_rand(4,10); $cmt++)
                {
                    $comment = new Commentaire;
                    $now = new \DateTime();
                    $interval = $now->diff($article->getDate());
                    $days = $interval->days;

                    $comment->setAuteur($faker->name);
                    $contenu = '<p>'.join('</p><p>',$faker->paragraphs(5)).'</p>';
                    $comment->setCommentaire($contenu);
                    $comment->setDate($faker->dateTimeBetween("-$days days"));
                    $comment->setArticle($article);
                    $manager->persist($comment);
                }
            }
        }
        $manager->flush();
    }
}
