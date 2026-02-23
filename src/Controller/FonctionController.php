<?php

namespace App\Controller;


use App\Entity\Fonction;
use App\Form\FonctionType;
use App\Repository\FonctionRepository;
use App\Service\FormErrorHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/fonctions', name: 'app_fonction_')]
final class FonctionController extends AbstractController
{
    public function __construct( private FonctionRepository $fonctionRepository , private EntityManagerInterface $em,
                                 private FormFactoryInterface $formFactory, private FormErrorHandler $errorHandler){

    }


    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(): JsonResponse
    {
        $fonctions = $this->fonctionRepository->findAll();

        return $this->json($fonctions, context: ['groups' =>['fonction:list']]);

    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {

        $fonction = new Fonction();

        $form = $this->formFactory->create(FonctionType::class, $fonction);
        $form->submit(json_decode($request->getContent(), true), false);

        if(!$form->isValid()){
            return     $this->errorHandler->createErrorResponse($form, Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($fonction);
        $this->em->flush();

        return $this->json($fonction, \Symfony\Component\HttpFoundation\Response::HTTP_CREATED, context: ['groups' =>['fonction:list']]);

    }

    #[Route('/{id}', name: 'update', methods: ['PUT','PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Fonction $fonction, Request $request): JsonResponse
    {

        $form = $this->formFactory->create(FonctionType::class, $fonction);
        $form->submit(json_decode($request->getContent(), true), false);

        if(!$form->isValid()){
            return     $this->errorHandler->createErrorResponse($form, Response::HTTP_BAD_REQUEST);
        }

        $this->em->flush();

        return $this->json($fonction, context: ['groups' =>['fonction:list']]);

    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Fonction $fonction): JsonResponse
    {

        $this->em->remove($fonction);
        $this->em->flush();

        return $this->json($fonction, Response::HTTP_NO_CONTENT);

    }

}
