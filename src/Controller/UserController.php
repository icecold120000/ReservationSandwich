<?php

namespace App\Controller;

use Ang3\Component\Serializer\Encoder\ExcelEncoder;
use App\Entity\Fichier;
use App\Entity\User;
use App\Form\FichierType;
use App\Form\FilterOrSearch\UserFilterType;
use App\Form\UserType;
use App\Repository\AdulteRepository;
use App\Repository\EleveRepository;
use App\Repository\InscriptionCantineRepository;
use App\Repository\UserRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private EleveRepository $eleveRepository;
    private UserPasswordHasherInterface $userPasswordHasher;
    private AdulteRepository $adulteRepository;

    public function __construct(EntityManagerInterface      $entityManager,
                                UserRepository              $userRepository,
                                EleveRepository             $eleveRepository,
                                AdulteRepository            $adulteRepository,
                                UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->entityManager = $entityManager;
        $this->adulteRepository = $adulteRepository;
        $this->userRepository = $userRepository;
        $this->eleveRepository = $eleveRepository;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    /**
     * Page de gestion des utilisateurs
     * @Route("/index/{page}",defaults={"page" : 1}, name="user_index", methods={"GET","POST"})
     * @param Request $request
     * @param UserRepository $userRepository
     * @param PaginatorInterface $paginator
     * @param int $page
     * @return Response
     */
    public function index(Request            $request,
                          UserRepository     $userRepository,
                          PaginatorInterface $paginator,
                          int                $page = 1): Response
    {
        /*Récupération des utilisateurs*/
        $users = $userRepository->findAll();
        $form = $this->createForm(UserFilterType::class, null, ['method' => 'GET']);
        $search = $form->handleRequest($request);

        /*Filtre*/
        if ($form->isSubmitted() && $form->isValid()) {
            $users = $userRepository->search(
                $search->get('roleUser')->getData(),
                $search->get('userVerifie')->getData(),
                $search->get('ordreNom')->getData(),
                $search->get('ordrePrenom')->getData(),
                $search->get('userName')->getData()
            );
        }

        /*Pagination*/
        $usersTotal = $users;
        $users = $paginator->paginate(
            $users,
            $page,
            30
        );

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'usersTotal' => $usersTotal,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire d'ajout d'un utilisateur
     * @Route("/new", name="user_new", methods={"GET", "POST"})
     * @param Request $request
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepo
     * @return Response
     * @throws NonUniqueResultException
     */
    public function new(Request                     $request,
                        UserPasswordHasherInterface $userPasswordHasher,
                        EntityManagerInterface      $entityManager,
                        UserRepository              $userRepo): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userBirthday = $form->get('dateNaissanceUser')->getData();
            /*
              Vérification si l'utilisateur existe dans la base de données
              Avec ou sans date de naissance saisie
            */
            if ($userBirthday != null) {
                $userRelated = $userRepo->findByNomPrenomAndBirthday($form->get('nomUser')->getData(),
                    $form->get('prenomUser')->getData(), $userBirthday);
            } else {
                $userRelated = $userRepo->findByNomAndPrenom($form->get('nomUser')->getData(),
                    $form->get('prenomUser')->getData());
            }

            /*Si l'utilisateur n'existe pas alors */
            if ($userRelated == null) {
                /*Il est créé*/
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
                $user->setTokenHash(md5($user->getNomUser() . $user->getEmail()));

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash(
                    'SuccessUser',
                    'L\'utilisateur a été sauvegardé !'
                );
            } else {
                /*Sinon un message d'erreur s'affiche*/
                $this->addFlash(
                    'FailedUser',
                    'L\'utilisateur existe déjà dans la base de données !'
                );
            }

            return $this->redirectToRoute('user_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire d'ajout d'une liste d'utilisateur
     * @Route("/file", name="user_file", methods={"GET","POST"})
     * @param Request $request
     * @param SluggerInterface $slugger
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws NonUniqueResultException
     */
    public function fileSubmit(Request                $request,
                               SluggerInterface       $slugger,
                               EntityManagerInterface $entityManager): Response
    {
        $userFile = new Fichier();
        $form = $this->createForm(FichierType::class, $userFile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $fichierUser */
            $fichierUser = $form->get('fileSubmit')->getData();
            if ($fichierUser) {
                $originalFilename = pathinfo($fichierUser->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '.' . $fichierUser->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $fichierUser->move(
                        $this->getParameter('userfile_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new FileException("Fichier corrompu. Veuillez retransférer votre liste !");
                }
                $userFile->setFileName($newFilename);
            }

            $entityManager->persist($userFile);
            $entityManager->flush();

            /*Traitement du fichier Excel soumis*/
            UserController::creerUsers($userFile->getFileName());
            $this->addFlash(
                'SuccessUserFileSubmit',
                'Les utilisateurs ont été sauvegardés !'
            );
            return $this->redirectToRoute('user_file');
        }

        return $this->render('user/userFile.html.twig', [
            'fichierUser' => $userFile,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Fonction permettant de traiter les utilisateurs dans le fichier excel
     * @param string $fileName
     * @throws NonUniqueResultException
     * @throws Exception
     */
    private function creerUsers(string $fileName): void
    {
        $userCreated = 0;
        /* Parcours le tableau donné par le fichier Excel*/
        while ($userCreated < sizeof($this->getDataFromFile($fileName))) {
            /*Pour chaque Utilisateur*/
            foreach ($this->getDataFromFile($fileName) as $row) {
                /*Parcours les données d'un utilisateur*/
                foreach ($row as $rowData) {
                    if ($rowData[""][0] != null or $rowData[""][0] != "Nom") {
                        /*Vérifie s'il existe une colonne email et qu'elle n'est pas vide*/
                        if (array_key_exists('Email', $rowData)
                            && !empty($rowData['Email'])) {
                            /*Recherche l'utilisateur dans la base de donnée*/
                            $userRelated = $this->userRepository->findOneByEmail(
                                $rowData['Email']
                            );
                            /*S'il n'existe pas alors on le crée
                             en tant qu'un nouvel utilisateur*/
                            if ($userRelated === null) {
                                $user = new User();
                                $roleUser = $rowData['Fonction'];
                                $birthday = new DateTime($rowData['Date de naissance'],
                                    new DateTimeZone('Europe/Paris'));

                                /*
                                  Récupère et attribue le compte utilisateur
                                  à l'élève ou l'adulte concerné
                                */
                                $eleve = $this->eleveRepository
                                    ->findByNomPrenomDateNaissance($rowData['Nom']
                                        , $rowData['Prénom'],
                                        $birthday
                                    );
                                $adulte = $this->adulteRepository
                                    ->findByNomPrenomDateNaissance($rowData['Nom']
                                        , $rowData['Prénom'],
                                        $birthday
                                    );

                                if ($eleve != null) {
                                    $user->addEleve($eleve);
                                } elseif ($adulte != null) {
                                    $user->addAdulte($adulte);
                                }

                                $user
                                    ->setEmail($rowData['Email'])
                                    ->setNomUser($rowData['Nom'])
                                    ->setPrenomUser($rowData['Prénom'])
                                    ->setDateNaissanceUser($birthday)
                                    ->setIsVerified(true);

                                /*Attribue le rôle de l'utilisateur*/
                                switch ($roleUser) {
                                    case "Admin":
                                        $user->setRoles([User::ROLE_ADMIN]);
                                        break;
                                    case "Élève":
                                        $user->setRoles([User::ROLE_ELEVE]);
                                        break;
                                    case "Cuisinier":
                                        $user->setRoles([User::ROLE_CUISINE]);
                                        break;
                                    case "Adulte":
                                        $user->setRoles([User::ROLE_ADULTES]);
                                        break;
                                    default:
                                        $user->setRoles([User::ROLE_USER]);
                                        break;
                                }

                                /*Hash le mot de passe*/
                                $user->setPassword(
                                    $this->userPasswordHasher->hashPassword(
                                        $user,
                                        $rowData['Mot de passe']
                                    )
                                );
                                /*Génère un token hash*/
                                $user->setTokenHash(md5($user->getNomUser() . $user->getEmail()));

                                $this->entityManager->persist($user);
                            } else {
                                $userRelated->setPassword(
                                    $this->userPasswordHasher->hashPassword(
                                        $userRelated,
                                        $rowData['Mot de passe']
                                    )
                                );

                                $userRelated
                                    ->setEmail($rowData['Email'])
                                    ->setNomUser($rowData['Nom'])
                                    ->setPrenomUser($rowData['Prénom'])
                                    ->setIsVerified(true)
                                    ->setTokenHash(md5($userRelated->getNomUser() . $userRelated->getEmail()));
                                $this->entityManager->persist($userRelated);
                            }
                        }
                        $userCreated++;
                    }
                }
            }
        }
        /*Supprime le fichier Excel après le traitement du fichier*/
        unlink($this->getParameter('userFile_directory') . $fileName);
        $this->entityManager->flush();
    }

    /**
     * Fonction permettant de récupérer les données du fichier excel et de retourner
     * un tableau qui contient les utilisateurs dans le fichier excel
     * @param string $fileName
     * @return array
     */
    public function getDataFromFile(string $fileName): array
    {
        $file = $this->getParameter('userfile_directory') . $fileName;
        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);

        $normalizers = [new ObjectNormalizer()];
        $encoders = [
            new ExcelEncoder([]),
        ];
        $serializer = new Serializer($normalizers, $encoders);

        /** @var string $fileString */
        $fileString = file_get_contents($file);

        return $serializer->decode($fileString, $fileExtension);
    }

    /**
     * Page de pré-suppression d'un utilisateur
     * @Route("/{id}/delete_view", name="user_delete_view", methods={"GET"})
     * @param User $user
     * @return Response
     */
    public function delete_view(User $user): Response
    {
        return $this->render('user/delete_view.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Formulaire de suppression d'un élève
     * @Route("/{id}/edit", name="user_edit", methods={"GET", "POST"})
     * @param Request $request
     * @param User $user
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function edit(Request                     $request,
                         User                        $user,
                         UserPasswordHasherInterface $userPasswordHasher,
                         EntityManagerInterface      $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user, ['password_required' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*Si le champ mot de passe est rempli*/
            if ($form->get('password')->getData() != null) {
                /*Hash le mot de passe*/
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );

                /*Récupère le champ fonction de l'utilisateur*/
                if ($form->get('roles')->getData()) {
                    $roles = $form->get('roles')->getData();
                    /*Attribue le rôle correspondant*/
                    switch ($roles) {
                        case in_array("ROLE_ADMIN", $roles):
                            $user->setRoles([User::ROLE_ADMIN]);
                            break;
                        case in_array("ROLE_ELEVE", $roles):
                            $user->setRoles([User::ROLE_ELEVE]);
                            break;
                        case in_array("ROLE_CUISINE", $roles):
                            $user->setRoles([User::ROLE_CUISINE]);
                            break;
                        case in_array("ROLE_ADULTES", $roles):
                            $user->setRoles([User::ROLE_ADULTES]);
                            break;
                        default:
                            $user->setRoles([User::ROLE_USER]);
                            break;
                    }
                }
            }

            /*Régénère le token hash*/
            $user->setTokenHash(md5($user->getId() . $user->getEmail()));
            $entityManager->flush();

            /*Message de validation*/
            $this->addFlash(
                'SuccessUser',
                'L\'utilisateur a été modifié !'
            );

            return $this->redirectToRoute('user_edit', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formulaire de suppression d'un utilisateur
     * @Route("/{id}", name="user_delete", methods={"POST"})
     * @param Request $request
     * @param User $user
     * @param EntityManagerInterface $entityManager
     * @param AdulteRepository $adulteRepository
     * @param EleveRepository $eleveRepository
     * @param InscriptionCantineRepository $cantineRepository
     * @return Response
     * @throws NonUniqueResultException
     */
    public function delete(Request                      $request,
                           User                         $user,
                           EntityManagerInterface       $entityManager,
                           AdulteRepository             $adulteRepository,
                           EleveRepository              $eleveRepository,
                           InscriptionCantineRepository $cantineRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $eleveFound = $eleveRepository->findOneByCompte($user);
            $adulteFound = $adulteRepository->findOneByCompte($user);

            /*Vérifie si l'utilisateur est rattaché un élève,
             supprime son inscription à la cantine et l'élève concerné.
            */
            if ($eleveFound) {
                $cantine = $cantineRepository->findOneByEleve($eleveFound->getId());
                $entityManager->remove($cantine);
                $entityManager->flush();
                $entityManager->remove($eleveFound);
                $entityManager->flush();
            }
            /*Vérifie si l'utilisateur est rattaché un adulte,
             supprime son inscription à la cantine et l'adulte concerné.
            */
            if ($adulteFound) {
                $entityManager->remove($adulteFound);
                $entityManager->flush();
            }

            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash(
                'SuccessDeleteUser',
                'L\'utilisateur a été supprimé !'
            );
        }

        return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
    }
}
