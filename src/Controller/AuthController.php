<?php

namespace App\Controller;

use App\Entity\Auth;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[Route('/api', name: 'api_')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'register', methods: 'post')]
    public function register(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository): JsonResponse
    {
        $em = $doctrine->getManager();
        $decoded = json_decode($request->getContent(), true);
        $email = $decoded['email'] ?? null;
        $plaintextPassword = $decoded['password'] ?? null;

        if (!$email || !$plaintextPassword) {
            return $this->json(['error' => 'Email and password are required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Check if the email is already taken
        $existingUser = $userRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json(['error' => 'Email is already taken'], JsonResponse::HTTP_CONFLICT);
        }

        $user = new User();
        $hashedPassword = $passwordHasher->hashPassword($user, $plaintextPassword);
        $user->setPassword($hashedPassword);
        $user->setEmail($email);

        //Uncomment this to create first admin user
        //$user->setRoles(['ROLE_ADMIN']);

        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'Registered Successfully']);
    }

    #[Route('/login', name: 'login', methods: 'POST')]
    public function login(Request $request, EntityManagerInterface $entityManager, UserProviderInterface $userProvider, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $JWTTokenManager): JsonResponse
    {
        $decoded = json_decode($request->getContent());
        $email = $decoded->email;
        $plaintextPassword = $decoded->password;

        try {
            $user = $userProvider->loadUserByIdentifier($email);

            if (!$passwordHasher->isPasswordValid($user, $plaintextPassword)) {
                throw new AuthenticationException('Invalid credentials.');
            }

            $token = $JWTTokenManager->create($user);
            $expirationDate = new \DateTime('+1 hour');

            // Store relevant data in the Auth entity
            $auth = new Auth();
            $auth->setUser($user);
            $auth->setJwtToken($token);
            $auth->setExpirationDate($expirationDate);

            $entityManager->persist($auth);
            $entityManager->flush();

            return new JsonResponse(['token' => $token]);
        } catch (AuthenticationException $e) {
            return new JsonResponse(['error' => 'Invalid credentials.'], JsonResponse::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}