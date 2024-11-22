<?php
namespace App\Controller;

use App\Service\JWTService;
use App\Service\SendEmailService;
use App\Form\ResetPasswordFormType;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ResetPasswordRequestFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        
        $error = $authenticationUtils->getLastAuthenticationError();
       
        $lastUsername = $authenticationUtils->getLastUsername();

        

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }
    

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
    #[Route('/mot-de-passe-oublie', name: 'forgotten_password')]
    public function forgottenPassword(
        Request $request,
        UsersRepository $usersRepository,
        JWTService $jwt,
        SendEmailService $mail
    ) : Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            // Le formulaire est envoyé ET valide
            // On va chercher l'utilisateur dans la base
            $user = $usersRepository->findOneByEmail($form->get('email')->getData());

            // On vérifie si on a un utilisateur
            if($user){
                // On a un utilisateur
                // On génère un JWT
                // Header
                $header = [
                    'typ' => 'JWT',
                    'alg' => 'HS256'
                ];

                // Payload
                $payload = [
                    'user_id' => $user->getId()
                ];

                // On génère le token
                $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

                // On génère l'URL vers reset_password
                $url = $this->generateUrl('reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                // Envoyer l'e-mail
                $mail->send(
                    'sandbox@smtp.mailtrap.io',
                    $user->getEmail(),
                    "Récupération du mot de passe sur le site les Ateliers d'Andrée",
                    'password_reset',
                    compact('user', 'url')
                );

                $this->addFlash('succes', 'Un email vous a été envoyé pour réinitialiser votre mot de passe');
                return $this->redirectToRoute('app_login');

            }
            // $user est null
            $this->addFlash('dangers', 'Un problème est survenu');
            return $this->redirectToRoute('app_login');
        }



        return $this->render('security/reset_password_request.html.twig', [
            'requestPassForm' => $form->createView()
        ]);
    }


    #[Route('/mot-de-passe-oublie/{token}', name: 'reset_password')]
    public function resetPassword(
        $token,
        JWTService $jwt,
        UsersRepository $usersRepository,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response
    {
        
        // On vérifie si le token est valide (cohérent, pas expiré et signature correcte)
        if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))){
            // Le token est valide
            // On récupère les données (payload)
            $payload = $jwt->getPayload($token);
            
            
            // On récupère le user
            $user = $usersRepository->find($payload['user_id']);

            if($user){
                $form = $this->createForm(ResetPasswordFormType::class);

                $form->handleRequest($request);

                if($form->isSubmitted() && $form->isValid()){
                    $user->setPassword(
                        $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
                    );

                    $em->flush();

                    $this->addFlash('success', 'Mot de passe changé avec succès');
                    return $this->redirectToRoute('app_login');
                }
                return $this->render('security/reset_password.html.twig', [
                    'passForm' => $form->createView()
                ]);
            }
        }
        $this->addFlash('danger', 'Le lien est invalide ou a expiré');
        return $this->redirectToRoute('app_login');
    }
}
    // #[Route('/mot-de-passe-oublie', name: 'forgotten_password')]
    // public function forgottenPassword(
    //     Request $request,
    //     UsersRepository $usersRepository,
    //     JWTService $jwt,
    //     SendEmailService $mail
    // ): Response {
    //     $form = $this->createForm(ResetPasswordRequestFormType::class);
    //     $form->handleRequest($request);
    
    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $user = $usersRepository->findOneByEmail($form->get('email')->getData());
    
    //         if ($user) {
    //             // Configuration du header et du payload pour le JWT
    //             $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    //             $payload = [
    //                 'user_id' => $user->getId(),
    //             ];
    
    //             // Générer le token avec une validité de 5 minutes (300 secondes)
    //             $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'), 900);
    
    //             // Générer l'URL pour la réinitialisation de mot de passe
    //             $url = $this->generateUrl('reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
    
    //             // Envoyer l'email
    //             $mail->send(
    //                 'sandbox@smtp.mailtrap.io',
    //                 $user->getEmail(),
    //                 "Récupération de mot de passe",
    //                 'password_reset',
    //                 compact('user', 'url')
    //             );
    
    //             $this->addFlash('success', 'Un email vous a été envoyé pour réinitialiser votre mot de passe.');
    //             return $this->redirectToRoute('app_login');
    //         }
    
    //         $this->addFlash('danger', 'Utilisateur introuvable.');
    //         return $this->redirectToRoute('forgotten_password');
    //     }
    
    //     return $this->render('security/reset_password_request.html.twig', [
    //         'requestPassForm' => $form->createView()
    //     ]);
    // }    
    // #[Route('/mot-de-passe-oublie/{token}', name: 'reset_password')]
    // public function resetPassword(
    //     string $token,
    //     JWTService $jwt,
    //     UsersRepository $usersRepository,
    //     Request $request,
    //     UserPasswordHasherInterface $passwordHasher,
    //     EntityManagerInterface $em
    // ): Response {
    //     if ($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))) {
    //         $payload = $jwt->getPayload($token);
    //         $user = $usersRepository->find($payload['user_id']);
    
    //         if ($user) {
    //             $form = $this->createForm(ResetPasswordFormType::class);
    //             $form->handleRequest($request);
    
    //             if ($form->isSubmitted() && $form->isValid()) {
    //                 $user->setPassword(
    //                     $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
    //                 );
    //                 $em->flush();
    
    //                 $this->addFlash('success', 'Mot de passe changé avec succès.');
    //                 return $this->redirectToRoute('app_login');
    //             }
    
    //             return $this->render('security/reset_password.html.twig', [
    //                 'passForm' => $form->createView(),
    //             ]);
    //         }
    //     }
    
    //     $this->addFlash('danger', 'Le lien a expiré ou est invalide.');
    //     return $this->redirectToRoute('app_login');
    // }
    // }