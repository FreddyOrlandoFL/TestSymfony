<?php

namespace App\Controller;

use App\Repository\UserRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
// ...
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em){
        $this->em =$em;
    }

    #[Route('/api/user/{id}', name: 'list_user',methods:['GET'])]
    public function index(int $id): JsonResponse
    {
        $data=$this->em->getRepository(User::class)
        ->find($id);
        if(!$data){
            return $this->json([
                'estado'=>'Error',
                'mensaje'=>'La Url no esta disponible en este momento'
            ],404);
        }
        else{
            return $this->json($data);
        }
    }
    
    #[Route('/api/user/{id}', name: 'modifid_user',methods:['PUT'])]
    public function updateUser(int $id, Request  $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $datos=$this->em->getRepository(User::class)->find($id);
        if(!$datos){
            return $this->json([
                'estado'=>'Error',
                'mensaje'=>'La Url no esta disponible en este momento'
            ],Response::HTTP_BAD_REQUEST);
        }
        else{
            $data = json_decode($request->getContent(),true);
            if(!isset($data['name'])){
                return $this->json([
                    "status"=>"Error",
                    "mensaje"=>"El campo name es obligatorio"
                ],Response::HTTP_OK);
            }
            if(!isset($data['lastname'])){
                return $this->json([
                    "status"=>"Error",
                    "mensaje"=>"El campo apellido es obligatorio"
                ],Response::HTTP_OK);
            }
            if(!isset($data['email'])){
                return $this->json([
                    "status"=>"Error",
                    "mensaje"=>"El campo email es obligatorio"
                ],Response::HTTP_OK);
            }
            if(!isset($data['password'])){
                return $this->json([
                    "status"=>"Error",
                    "mensaje"=>"El campo password es obligatorio"
                ],Response::HTTP_OK);
            }
            if(!isset($data['confirmpassword'])){
                return $this->json([
                    "status"=>"Error",
                    "mensaje"=>"El campo confirmpassword es obligatorio"
                ],Response::HTTP_OK);
            }
            if(!isset($data['phone'])){
                return $this->json([
                    "status"=>"Error",
                    "mensaje"=>"El campo telefono es obligatorio"
                ],Response::HTTP_OK);
            }
            if($data['password']!=$data['confirmpassword']){
                return $this->json([
                    "status"=>"Error",
                    "mensaje"=>"La contraseña no es valida"
                ],Response::HTTP_OK);
            }
           
            
            $datos->setName($data['name']);
            $datos->setLastName($data['lastname']);
            $datos->setPassword($passwordHasher->hashPassword(
                $datos,
                $data['password']
            ));
            $datos->setEmail($data['email']);
            $datos->setPhone($data['phone']);

            $this->em->flush();

            return $this->json([
                'estado'=>"success",
                "mesanje"=>"Se modifico el registro exitosamente"
            ],Response::HTTP_CREATED);

        }
    }
}
