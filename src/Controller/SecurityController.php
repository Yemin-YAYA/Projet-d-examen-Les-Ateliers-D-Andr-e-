<?php
namespace App\Controller;

use DateTimeZone;
use DateTimeImmutable;
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
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Chercher l'utilisateur par email
            $user = $usersRepository->findOneByEmail($form->get('email')->getData());
    
            if ($user) {
                // Générer un token JWT avec expiration de 5 minutes
                $expiration = new DateTimeImmutable('15 minutes', new DateTimeZone('Europe/Paris'));
                $expTimestamp = $expiration->getTimestamp(); // Convertir en timestamp Unix (en secondes)
    
                $header = [
                    'typ' => 'JWT',
                    'alg' => 'HS256'
                ];
    
                // Payload avec l'ID de l'utilisateur et la date d'expiration
                $payload = [
                    'user_id' => $user->getId(),
                    'exp' => $expTimestamp,  // expiration en secondes
                    'iat' => time(), // Le moment où le token a été généré
                    'now' => (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->getTimestamp(),
                ];
    
                // Générer le token
                $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));
    
                // Générer l'URL pour réinitialiser le mot de passe
                $url = $this->generateUrl('reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
    
                // Envoyer l'email
                $mail->send(
                    'sandbox@smtp.mailtrap.io',
                    $user->getEmail(),
                    "Récupération de mot de passe sur le site Les Ateliers d'Andrée",
                    'password_reset',
                    compact('user', 'url')
                );
    
                // Message flash de succès
                $this->addFlash('succes', 'Email envoyé avec succès');
                return $this->redirectToRoute('app_login');
            }
    
            // Utilisateur introuvable
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
        // Vérifier si le token est valide, et s'il n'est pas expiré
        if ($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))) {
            $payload = $jwt->getPayload($token);
            $user = $usersRepository->find($payload['user_id']);
        
            if ($user) {
                $form = $this->createForm(ResetPasswordFormType::class);
                $form->handleRequest($request);
        
                if ($form->isSubmitted() && $form->isValid()) {
                    // Changer le mot de passe de l'utilisateur
                    $user->setPassword(
                        $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
                    );
                    $em->flush();
        
                    // Message flash de succès
                    $this->addFlash('success', 'Mot de passe changé avec succès');
                    return $this->redirectToRoute('app_login');
                }
        
                return $this->render('security/reset_password.html.twig', [
                    'passForm' => $form->createView(),
                ]);
            }
        }
        
        // Si le token est expiré ou invalide, afficher un message flash d'erreur
        $this->addFlash('danger', 'Le lien a expiré ou est invalide.');
        // Rediriger vers la page de connexion
        return $this->redirectToRoute('app_login');
    }    
}