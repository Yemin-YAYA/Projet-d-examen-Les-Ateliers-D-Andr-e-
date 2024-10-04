<?php


namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request); /*permet de recuperer le formulaire*/

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer la raison du contact
            $raison = $contact->getRaison();

            // Préparer le contenu de l'e-mail avec la raison du contact
            $emailContent = sprintf(
                '<p>Nom: %s</p>
                <p>Email: %s</p>
                <p>Motif du contact: %s</p>
                <p>Message: %s</p>',
                htmlspecialchars($contact->getName()),  // Nom de l'expéditeur
                htmlspecialchars($contact->getEmail()), // Email de l'expéditeur
                htmlspecialchars($raison),              // Raison du contact
                nl2br(htmlspecialchars($contact->getMessage()))  // Le message
            );

            // Envoyer l'e-mail
            $email = (new Email())
                ->from($contact->getEmail()) /*recupere l'email de l'expediteur*/
                ->to('sandbox@smtp.mailtrap.io') /*Email de reception*/
                ->subject('Nouveau message de contact')
                ->html($emailContent); /*Le contenu de l'email avec la raison et le message*/

            $mailer->send($email);

            $this->addFlash('success', 'Votre message a été envoyé avec succès.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/contact.html.twig', [
            'formContact' => $form->createView(),
        ]);
    }
}


