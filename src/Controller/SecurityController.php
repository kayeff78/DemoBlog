<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    
    #[Route("/login", name:"app_login")]
     public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // renvoie un msg d'erreur si jon a mis de mavais identifiants pour la connexion
        $error = $authenticationUtils->getLastAuthenticationError();
        // cette méthode renvoie dans notre cas, le dernier 'email' saisi par l'internaute dans le formualire d'authentification
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig',[
            'last_username' => $lastUsername,
             'error' => $error
            ]);
    }

    
    #[Route("/logout", name:"app_logout")]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
