<?php

namespace App\Controller;


use App\Entity\Collaborateur;
use App\Form\CollaborateurType;
use App\Repository\CollaborateurRepository;
use App\Service\FormErrorHandler;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[Route('/api/collaborateur', name: 'app_collaborateur_')]
final class CollaborateurController extends AbstractController
{


    public function __construct( private CollaborateurRepository $collaborateurRepository , private EntityManagerInterface $em,
                                 private FormFactoryInterface $formFactory, private FormErrorHandler $errorHandler){

    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/collaborateur',
        summary: 'Liste des collaborateurs',
        tags: ['Collaborateur'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des collaborateurs',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        new Model(type: Collaborateur::class, groups: ['collaborateur:list'])
                    )
                )
            )
        ]
    )]
    public function list(): JsonResponse
    {
        $collaborateurs = $this->collaborateurRepository->findAll();

        return $this->json($collaborateurs, context: ['groups' =>['collaborateur:list']]);

    }

    #[Route('/filter', name: 'filter', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/collaborateur/filter',
        summary: 'Liste des collaborateurs filtrée',
        tags: ['Collaborateur'],
        parameters: [
           new OA\Parameter(
               name: "restaurant",
               description: "filtre par restaurant",
               in: "query",
               schema: new OA\Schema(type: "string")
           ),
            new OA\Parameter(
                name: "fonction",
                description: "filtre par fonction",
                in: "query",
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des collaborateurs filtré par nom de restaurant ou de fonction',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        new Model(type: Collaborateur::class, groups: ['collaborateur:list'])
                    )
                )
            )
        ]
    )]
    public function filter(Request $request): JsonResponse
    {
        $filters = [
            'fonction' => $request->query->get('fonction'),
            'restaurant' => $request->query->get('restaurant')
        ];
        $collaborateur = $this->collaborateurRepository->findByFilters($filters);

        return $this->json($collaborateur, context: ['groups' =>['collaborateur:list']]);

    }


    #[Route('/{id}', name: 'details', methods: ['GET'])]
    #[OA\Get(
        path: '/api/collaborateur/{id}',
        summary: 'Detail d un collaborateur',
        tags: ['Collaborateur'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail d un collaborateur',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        new Model(type: Collaborateur::class, groups: ['collaborateur:detail'])
                    )
                )
            )
        ]
    )]
    public function getCollaborateur(Collaborateur $collaborateur, CollaborateurRepository $collaborateurRepository, int $id): JsonResponse
    {
        if($collaborateur->getId() !== $this->getUser()->getId() && !in_array('ROLE_ADMIN', $this->getUser()->getRoles())){
            throw $this->createAccessDeniedException();
        }
        $detail =$collaborateurRepository->findOne($id);


        return $this->json($detail, context: ['groups' =>['collaborateur:detail']]);
    }

    #[Route('',name:'create',methods:['POST'])]
   #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(
        path: '/api/collaborateur',
        summary: 'Creer d un collaborateur',
        requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    ref: new Model(type: Collaborateur::class, groups: ['collaborateur:create']),
                    type: 'object',
                ),
        ),
        tags: ['Collaborateur'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Creer d un collaborateur',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        new Model(type: Collaborateur::class, groups: ['collaborateur:detail'])
                    )
                )
            )
        ]
    )]
    public function create(Request $request, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {

        $collaborateur = new Collaborateur();

        $form = $this->formFactory->create(CollaborateurType::class, $collaborateur);
        $form->submit(json_decode($request->getContent(),true),false);

        if(!$form->isValid()) {
            return     $this->errorHandler->createErrorResponse($form, Response::HTTP_BAD_REQUEST);
        }


        $collaborateur->setPassword($userPasswordHasher->hashPassword($collaborateur, $collaborateur->getPassword()));

        $this->em->persist($collaborateur);
        $this->em->flush();

        return $this->json($collaborateur, \Symfony\Component\HttpFoundation\Response::HTTP_CREATED, context: ['groups' => ['collaborateur:detail']]);
    }

    #[Route('/{id}',name:'update',methods:['PUT','PATCH'])]
    #[OA\Patch(
        path: '/api/collaborateur/{id}',
        summary: 'Update d un collaborateur',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: Collaborateur::class, groups: ['collaborateur:create']),
                type: 'object',
            ),
        ),
        tags: ['Collaborateur'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Update d un collaborateur',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        new Model(type: Collaborateur::class, groups: ['collaborateur:detail'])
                    )
                )
            )
        ]
    )]
    public function update(Collaborateur $collaborateur , Request $request): JsonResponse
    {

        if($collaborateur->getId() !== $this->getUser()->getId() && !in_array('ROLE_ADMIN', $this->getUser()->getRoles())){
            throw $this->createAccessDeniedException(message: 'Accès non autorisés ou droits insuffisants');
        }

        $form = $this->formFactory->create(CollaborateurType::class, $collaborateur);
        $form->submit(json_decode($request->getContent(),true),false);

        if(!$form->isValid()) {
            return     $this->errorHandler->createErrorResponse($form, Response::HTTP_BAD_REQUEST);
        }

        $this->em->flush();

        return $this->json($collaborateur,  context: ['groups' => ['collaborateur:detail']]);
    }

    #[Route('/{id}',name:'delete',methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        path: '/api/collaborateur/{id}',
        summary: 'Suppression d un collaborateur',
        tags: ['Collaborateur'],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Suppression d un collaborateur',
            )
        ]
    )]
    public function delete(Collaborateur $collaborateur): JsonResponse
    {
        $this->em->remove($collaborateur);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

}
