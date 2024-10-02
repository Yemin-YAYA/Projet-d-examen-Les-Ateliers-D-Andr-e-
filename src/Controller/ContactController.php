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
            // Envoyer l'email
            $email = (new Email())
                ->from($contact->getEmail()) /*recupere l'email de l'expediteur*/
                ->to('sandbox@smtp.mailtrap.io')/*Email de reception*/
                ->subject('Nouveau message de contact')
                ->html('<p>' . nl2br(htmlspecialchars($contact->getMessage())) . '</p>');


            $mailer->send($email);

            $this->addFlash('success', 'Votre message a été envoyé avec succès.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/contact.html.twig', [
            'formContact' => $form->createView(),
        ]);
    }
}

