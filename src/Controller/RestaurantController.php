<?php

namespace App\Controller;



use App\Form\RestaurantType;
use App\Repository\RestaurantRepository;
use App\Service\FormErrorHandler;
use App\Entity\Restaurant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/restaurant', name: 'app_restaurant_')]
final class RestaurantController extends AbstractController
{
    public function __construct( private RestaurantRepository $restaurantRepository , private EntityManagerInterface $em,
                                 private FormFactoryInterface $formFactory, private FormErrorHandler $errorHandler){

    }


    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $restaurants = $this->restaurantRepository->findAll();

        return $this->json($restaurants, context: ['groups' =>['restaurant:getAll']]);

    }

    #[Route('/{id}', name: 'details', methods: ['GET'])]
    public function getRestaurant(Restaurant $restaurant): JsonResponse
    {
        return $this->json($restaurant, context: ['groups' =>['restaurant:detail']]);
    }

    #[Route('',name:'create',methods:['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {

        $restaurant = new Restaurant();

        $form = $this->formFactory->create(RestaurantType::class, $restaurant);
        $form->submit(json_decode($request->getContent(),true),false);

        if(!$form->isValid()) {
            return     $this->errorHandler->createErrorResponse($form, Response::HTTP_BAD_REQUEST);
        }


        $this->em->persist($restaurant);
        $this->em->flush();

        return $this->json($restaurant, \Symfony\Component\HttpFoundation\Response::HTTP_CREATED, context: ['groups' => ['restaurant:detail']]);
    }

    #[Route('/{id}',name:'update',methods:['PUT','PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Restaurant $restaurant , Request $request): JsonResponse
    {

        $form = $this->formFactory->create(RestaurantType::class, $restaurant);
        $form->submit(json_decode($request->getContent(),true),false);

        if(!$form->isValid()) {
            return     $this->errorHandler->createErrorResponse($form, Response::HTTP_BAD_REQUEST);
        }

        $this->em->flush();

        return $this->json($restaurant,  context: ['groups' => ['collaborateur:detail']]);
    }

    #[Route('/{id}',name:'delete',methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Restaurant $restaurant): JsonResponse
    {
        $this->em->remove($restaurant);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

}
