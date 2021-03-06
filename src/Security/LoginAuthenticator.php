<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(EntityManagerInterface      $entityManager,
                                UrlGeneratorInterface       $urlGenerator,
                                UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $request->getSession()->set(Security::LAST_USERNAME, $email);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        /*V??rifie si l'utilisateur existe et renvoie un message d'erreur si c'est non*/
        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Erreur de saisie.
                Veuillez v??rifier votre email et votre mot de passe ou vous inscrire pour vous connecter !');
        } else {
            /*V??rifie si le compte de l'utilisateur est v??rifi?? et renvoie un message d'erreur si non */
            if ($user->isVerified() === false) {
                throw new CustomUserMessageAuthenticationException('Votre demande d\'inscription
                 n\'a pas encore ??t?? valid??e. Veuillez attendre la confirmation de l\'administrateur !');
            }
        }
        /*Message d'erreur si le mot de passe n'est pas rempli ou valide*/
        if (empty($request->request->get('password')) ||
            $this->userPasswordHasher->isPasswordValid($user, $request->request->get('password')) === false) {
            throw new CustomUserMessageAuthenticationException('Erreur de saisie.
                Veuillez v??rifier votre email et votre mot de passe ou vous inscrire pour vous connecter !');
        }

        /*V??rifie si la fonctionnalit?? rememberMe est coch??,
         si oui le compte aura cette fonctionnalit??
         sinon le compte n'aura pas la fonctionnalit??
        */
        if ($request->request->get('_remember_me') == "on") {
            return new Passport(
                new UserBadge($email),
                new PasswordCredentials($request->request->get('password', '')),
                [
                    new RememberMeBadge(),
                    new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                ]
            );
        } else {
            return new Passport(
                new UserBadge($email),
                new PasswordCredentials($request->request->get('password', '')),
                [
                    new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                ]
            );
        }
    }

    /**
     * @throws Exception
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
