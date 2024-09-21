<?php

// src/Controller/PasswordResetController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Symfony\Component\String\Slugger\SluggerInterface;

class PasswordResetController extends AbstractController
{
    #[Route('api/reset-password-request', name: 'password_reset_request', methods: ['POST'])]
    public function request(
        Request $request,
        UserRepository $userRepository,
        MailerInterface $mailer,
        SluggerInterface $slugger,
        EntityManagerInterface $em
    ): Response {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'];

        // Trouver l'utilisateur par email
        $user = $userRepository->findOneByEmail($email);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        // Générer un token unique
        $token = $slugger->slug(uniqid());

        // Stocker le token dans la base de données
        $user->setResetToken($token);
        $em->persist($user);
        $em->flush();

        // Construire le lien pour l'application React avec le token
        $reactAppUrl = 'http://localhost:3000/modern-textpair/reset-password'; // Remplacez par l'URL de votre app React
        $resetLink = $reactAppUrl . '?token=' . $token;

        // Créer l'email avec le lien de réinitialisation
        $email = (new Email())
            ->from('no-reply@yourdomain.com')
            ->to($user->getEmail())
            ->subject('Password Reset Request')
            ->html('<p>Click on the following link to reset your password: <a href="' . $resetLink . '">Reset Password</a></p>');

        // Envoyer l'email
        $mailer->send($email);

        return $this->json(['message' => 'Reset password email sent'], 200);
    }

    #[Route('/api/password-reset/{token}', name: 'password_reset', methods: ['POST'])]
    public function resetPassword(string $token, Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordEncoder, EntityManagerInterface $em): Response
    {
        // Trouver l'utilisateur par token
        $user = $userRepository->findOneByResetToken($token);

        if (!$user) {
            return $this->json(['error' => 'Invalid token'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $newPassword = $data['password'] ?? null;

        if (!$newPassword) {
            return $this->json(['error' => 'Password is required'], 400);
        }

        // Encoder le nouveau mot de passe
        $encodedPassword = $passwordEncoder->hashPassword($user, $newPassword);
        $user->setPassword($encodedPassword);
        // $user->setResetToken(null); // Réinitialiser le token
        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'Password successfully reset'], 200);
    }
}
