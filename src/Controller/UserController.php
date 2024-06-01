<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user', name: 'api_')]
class UserController extends AbstractController
{
    #[Route('/list', name: 'users_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function listUsers(ManagerRegistry $doctrine, SerializerInterface $serializer): JsonResponse
    {
        $em = $doctrine->getManager();
        $users = $em->getRepository(User::class)->findAll();

        $json = $serializer->serialize($users, 'json', ['groups' => 'user']);

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/{id}', name: 'show', methods: 'GET')]
    public function show(User $user, SerializerInterface $serializer): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser() !== $user) {
            throw new AccessDeniedException('You do not have permission to view this user.');
        }

        $json = $serializer->serialize($user, 'json', ['groups' => 'user']);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/', name: 'create', methods: 'POST')]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, UserPasswordHasherInterface $passwordHasher, ManagerRegistry $doctrine): JsonResponse
    {
        $em = $doctrine->getManager();
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $roles = $data['roles'] ?? ['ROLE_USER'];

        if (!$email || !$password) {
            return $this->json(['error' => 'Email and password are required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();

        return $this->json($user, JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: 'PUT')]
    public function update(User $user, Request $request, UserPasswordHasherInterface $passwordHasher, ManagerRegistry $doctrine): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser() !== $user) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $em = $doctrine->getManager();
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $roles = $data['roles'] ?? null;

        if ($email) {
            $user->setEmail($email);
        }

        if ($password) {
            $user->setPassword($passwordHasher->hashPassword($user, $password));
        }

        if ($roles && $this->isGranted('ROLE_ADMIN')) {
            $user->setRoles($roles);
        }

        $em->persist($user);
        $em->flush();

        return $this->json($user);
    }

    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(User $user, ManagerRegistry $doctrine): JsonResponse
    {
        $em = $doctrine->getManager();
        $em->remove($user);
        $em->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
