<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
// ...
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
//Para generar el Token
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AccessController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em){
        $this->em =$em;
    }
    #[Route('/api/login', name: 'api_login' , methods:['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher):JsonResponse{
        $data = json_decode($request->getContent(),true);
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
        $user =$this->em->getRepository(User::class)->findOneBy(['email'=>$data['email']]);

        if(!$user){
            return $this->json([
                'estado'=>'Error',
                'password'=>'Las credenciales ingresadas no son validas'
            ], ResponseResponse::HTTP_BAD_REQUEST);
        }
        if($passwordHasher->isPasswordValid($user,$data['password'])){
            $payload=[
                'iss'=>"http://". dirname($_SERVER['SERVER_NAME'] ."".  $_SERVER['PHP_SELF']). "/",
                'aud'=>$user->getId(),
                'iat'=>time(),
                'exp'=> time()+ (30 * 24 * 60 * 60)
            ];
            $jwt=JWT::encode($payload,$_ENV['JWT_SECRET'],'HS512');

            return $this->json([
                'nombre'=>$user->getName(),
                'apellido'=>$user->getLastname(),
                'role'=>$user->getRoles(),
                'telefono'=>$user->getPhone(),
                'token'=>$jwt
            ]);
        }else{
            return $this->json([
                'estado'=>'Error',
                'password'=>'Las credenciales ingresadas no son validas'
            ], ResponseResponse::HTTP_BAD_REQUEST);
        }


    }
    
    #[Route('/api/register', name: 'api_register' , methods:['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
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
        $existe = $this->em->getRepository(User::class)->findOneBy(['email'=>$data['email']]);
        if($existe){
            return $this->json([
                "status"=>"Error",
                "mensaje"=>"El correo {$data['email']} ya esta siendo usado por otro usuario "
            ],Response::HTTP_OK);
        }
        $entity= new User();
        $entity->setName($data['name']);
        $entity->setLastName($data['lastname']);
        $entity->setPassword($passwordHasher->hashPassword(
            $entity,
            $data['password']
        ));
        $entity->setEmail($data['email']);
        $entity->setPhone($data['phone']);
        $entity->setRoles(['ROLE_USER']);
        $this->em->persist($entity);
        $this->em->flush();

        return $this->json([
            'estado'=>"success",
            "mesanje"=>"Se creo el registro exitosamente"
        ],Response::HTTP_CREATED);
    }
    // /api/login 
}
