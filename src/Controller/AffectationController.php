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
                                 private FormFactoryInterface $formFactory, private FormErrorHandler $errorHandler, private AffectationDuplicateChecker $affectationDuplicateChecker){

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

    #[Route('/detail/{id}', name: 'details', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/affectation/detail/{id}',
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
    public function getAffectation(AffectationRepository $affectationRepository, int $id): JsonResponse
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
            'restaurant' => $request->query->get('restaurant'),
            'status' => $request->query->get('status'),
            'collaborateur' => $request->query->get('collaborateur'),
        ];

//        dump($filters);exit;
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
                    new OA\Property(property: 'status', description: 'status active ou archivée', type: 'bool'),
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
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $affectation = new Affectation();

        if (array_key_exists('status', $data)) {
            if (!is_bool($data['status'])) {
                return $this->json([
                    'errors' => [
                        'status' => ['Le champ status doit être un booléen.'],
                    ],
                ], Response::HTTP_BAD_REQUEST);
            }

            $affectation->setStatus($data['status']);
            unset($data['status']);
        }

        $form = $this->formFactory->create(AffectationType::class, $affectation);
        $form->submit($data,false);


        if(!$form->isValid()) {
            return     $this->errorHandler->createErrorResponse($form, Response::HTTP_BAD_REQUEST);
        }

        if ($affectation->getStatus() === null) {
            return $this->json([
                'errors' => [
                    'status' => ['Un status est obligatoire'],
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        if($this->affectationDuplicateChecker->isDuplicate($affectation)){
            return $this->json(
                ['error' => 'Ce collaborateur est deja affecté. Veuillez  supprimer ou désactiver cette affectation'],
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
                    new OA\Property(property: 'status', description: 'status active ou archivée', type: 'bool'),
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
        $data = json_decode($request->getContent(),true);
        if (array_key_exists('status', $data)) {
            if (!is_bool($data['status'])) {
                return $this->json([
                    'errors' => [
                        'status' => ['Le champ status doit être un booléen.'],
                    ],
                ], Response::HTTP_BAD_REQUEST);
            }

            $affectation->setStatus($data['status']);
            unset($data['status']);
        }
        $form = $this->formFactory->create(AffectationType::class, $affectation);
        $form->submit($data, false);

        if(!$form->isValid()) {
            return     $this->errorHandler->createErrorResponse($form, Response::HTTP_BAD_REQUEST);
        }
//
//        dump($affectation->getStatus());exit;
        if($affectation->getStatus() === true && $this->affectationDuplicateChecker->isDuplicate($affectation)){
            return $this->json(
                ['error' => 'Ce collaborateur est deja affecté. Veuillez  supprimer ou désactiver cette affectation'],
                Response::HTTP_CONFLICT
            );
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
