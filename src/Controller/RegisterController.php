<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\User;
use App\Form\RegisterUserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegisterController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    //request for receive the information of form
    //EntityManagerInterFace for working with dataBase
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        //create a new user object (this will hold the form data)
        $user = new User();
        //create the form and bind it to the user object
        $form = $this->createForm(RegisterUserType::class, $user);
        //handleRequest for listening to user requests
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //persist the user object (prepare it for saving in dataBase)
            //flush the persisted data to the database (save it)
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash(
                'success',
                "Votre compte est correctement créé, veuillez vous connecter."
            );

            // Envoie d'un email de confirmation d'inscription
            $mail = new Mail();
            $vars = [
                'firstname' => $user->getFirstname()
            ];
            $mail->send($user->getEmail(),$user->getFirstname().' '.$user->getLastname(), 'la boutique Française','welcome.html', $vars);

            return $this->redirectToRoute('app_login');
        }
        return $this->render('register/index.html.twig', [
            'registerForm' => $form->createView()
            ]);
    }
}