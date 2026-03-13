<?php

namespace App\Controller;


use App\Entity\Affectation;
use App\Form\AffectationType;
use App\Repository\AffectationRepository;
use App\Service\AffectationDuplicateChecker;
use App\Service\FormErrorHandler;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

#[Route('/api/affectation', name: 'app_affectation_')]
final class AffectationController extends AbstractController
{

    public function __construct( private AffectationRepository $affectationRepository , private EntityManagerInterface $em,
                                 private FormFactoryInterface $formFactory, private FormErrorHandler $errorHandler){

    }


    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/affectation',
        summary: 'Liste des affectations',
        tags: ['Affectations'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des affectations',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        new Model(type: Affectation::class, groups: ['affectation:list'])
                    )
                )
            )
        ]
    )]
    public function list(): JsonResponse
    {
        $affectation = $this->affectationRepository->findAll();

        return $this->json($affectation, context: ['groups' =>['affectation:list']]);

    }

    #[Route('/{id}', name: 'details', methods: ['GET'])]
    #[OA\Get(
        path: '/api/affectation/{id}',
        summary: 'Detail d un affectation',
        tags: ['Affectations'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail d un affectation',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        new Model(type: Affectation::class, groups: ['affectation:list'])
                    )
                )
            )
        ]
    )]
    public function getAffectation(Affectation $affectation, AffectationRepository $affectationRepository, int $id): JsonResponse
    {
//        if($affectation->getId() !== $this->getUser()->getId() && !in_array('ROLE_ADMIN', $this->getUser()->getRoles())){
//            throw $this->createAccessDeniedException();
//        }
        $detail =$affectationRepository->findOne($id);


        return $this->json($detail, context: ['groups' =>['affectation:list']]);
    }

    #[Route('/filter', name: 'filter', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/affectation/filter',
        summary: 'Liste des affectation filtrée',
        tags: ['Affectations'],
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
                description: 'Liste des affectation filtré par nom de restaurant ou de fonction',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        new Model(type: Affectation::class, groups: ['affectation:list'])
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

        $affectation = $this->affectationRepository->findByFilters($filters);

        return $this->json($affectation, context: ['groups' =>['affectation:list']]);

    }


    #[Route('',name:'create',methods:['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(
        path: '/api/affectation',
        summary: 'Creation d un affectation',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'collaborateur', description: 'id de collaborateur', type: 'int'),
                    new OA\Property(property: 'fonction', description: 'id de fonction', type: 'int'),
                    new OA\Property(property: 'restaurant', description: 'id de restaurant', type: 'int'),
                ]
            ),
        ),
        tags: ['Affectations'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Creer d un affectation',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        new Model(type: Affectation::class, groups: ['affectation:list'])
                    )
                )
            )
        ]
    )]
    public function create(Request $request, AffectationDuplicateChecker $affectationDuplicateChecker): JsonResponse
    {

        $affectation = new Affectation();

        $form = $this->formFactory->create(AffectationType::class, $affectation);
        $form->submit(json_decode($request->getContent(),true),false);


        if(!$form->isValid()) {
            return     $this->errorHandler->createErrorResponse($form, Response::HTTP_BAD_REQUEST);
        }

        if($affectationDuplicateChecker->isDuplicate($affectation)){
            return $this->json(
                ['error' => 'Une affectation similaire existe déjà.'],
                Response::HTTP_CONFLICT
            );
        }


        $this->em->persist($affectation);
        $this->em->flush();

        return $this->json($affectation, \Symfony\Component\HttpFoundation\Response::HTTP_CREATED, context: ['groups' => ['affectation:list']]);
    }

    #[Route('/{id}',name:'update',methods:['PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Patch(
        path: '/api/affectation/{id}',
        summary: 'Update d un affectation',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'fonction', description: 'id de fonction', type: 'int'),
                ]
            ),
        ),
        tags: ['Affectations'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Update d un affectation',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        new Model(type: Affectation::class, groups: ['affectation:list'])
                    )
                )
            )
        ]
    )]
    public function update(Affectation $affectation , Request $request): JsonResponse
    {

        $form = $this->formFactory->create(AffectationType::class, $affectation);
        $form->submit(json_decode($request->getContent(),true),false);

        if(!$form->isValid()) {
            return     $this->errorHandler->createErrorResponse($form, Response::HTTP_BAD_REQUEST);
        }

        $this->em->flush();

        return $this->json($affectation,  context: ['groups' => ['affectation:list']]);
    }

    #[Route('/{id}',name:'delete',methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Delete(
        path: '/api/affectation/{id}',
        summary: 'Suppression d une affectation',
        tags: ['Affectations'],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Suppression d un affectation',
            )
        ]
    )]
    public function delete(Affectation $affectation): JsonResponse
    {
        $this->em->remove($affectation);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }


}
