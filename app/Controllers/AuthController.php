<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Core\Mail;
use App\Models\User;

/**
 * AuthController
 */
class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    // ----------------------------------------------------------------
    // Login
    // ----------------------------------------------------------------

    public function loginForm(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth/login', [
            'csrf'  => $this->csrfToken(),
            'flash' => $this->getFlash(),
        ], 'auth');
    }

    public function login(): void
    {
        $this->verifyCsrf();

        $cpf   = trim($_POST['cpf']   ?? '');
        $senha = trim($_POST['senha'] ?? '');

        $v = new Validator(['cpf' => $cpf, 'senha' => $senha]);
        $v->required('cpf', 'CPF')
          ->required('senha', 'Senha');

        if ($v->fails()) {
            $this->setFlash('error', $v->firstError());
            $this->redirect('/');
            return;
        }

        $user = $this->userModel->authenticate($cpf, $senha);

        if ($user === null) {
            $this->setFlash('error', 'CPF ou senha inválidos.');
            $this->redirect('/');
            return;
        }

        // Store user in session
        $this->session->set('user_id', (int) $user['id']);
        $this->session->set('user', [
            'id'    => $user['id'],
            'nome'  => $user['nome'],
            'email' => $user['email'],
            'cpf'   => $user['cpf'],
        ]);
        $this->session->regenerateCsrf();

        $redirect = $this->session->get('redirect_after_login', '/dashboard');
        $this->session->remove('redirect_after_login');
        $this->redirect($redirect);
    }

    public function logout(): void
    {
        $this->session->destroy();
        $this->redirect('/');
    }

    // ----------------------------------------------------------------
    // Forgot password
    // ----------------------------------------------------------------

    public function forgotPassword(): void
    {
        $this->view('auth/forgot_password', [
            'csrf'  => $this->csrfToken(),
            'flash' => $this->getFlash(),
        ], 'auth');
    }

    public function sendResetEmail(): void
    {
        $this->verifyCsrf();

        $cpf = trim($_POST['cpf'] ?? '');

        $v = new Validator(['cpf' => $cpf]);
        $v->required('cpf', 'CPF')->cpf('cpf');

        if ($v->fails()) {
            $this->setFlash('error', $v->firstError());
            $this->redirect('/forgot-password');
            return;
        }

        // Always show success even if user not found (security)
        $token = $this->userModel->createResetToken($cpf);

        if ($token !== null) {
            $user = $this->userModel->findByCpf($cpf);
            if ($user) {
                $mail = new Mail();
                $mail->sendPasswordReset(
                    (string) $user['email'],
                    (string) $user['nome'],
                    $token
                );
            }
        }

        $this->setFlash('success', 'Se o CPF estiver cadastrado, você receberá um e-mail com instruções em breve.');
        $this->redirect('/forgot-password');
    }

    // ----------------------------------------------------------------
    // Reset password
    // ----------------------------------------------------------------

    public function resetPasswordForm(string $token): void
    {
        $user = $this->userModel->validateResetToken($token);

        if ($user === null) {
            $this->setFlash('error', 'Link de redefinição inválido ou expirado.');
            $this->redirect('/forgot-password');
            return;
        }

        $this->view('auth/reset_password', [
            'csrf'  => $this->csrfToken(),
            'token' => $token,
            'flash' => $this->getFlash(),
        ], 'auth');
    }

    public function resetPassword(): void
    {
        $this->verifyCsrf();

        $token   = trim($_POST['token']            ?? '');
        $senha   = trim($_POST['nova_senha']       ?? '');
        $confirm = trim($_POST['confirmar_senha']  ?? '');

        $v = new Validator([
            'token'           => $token,
            'nova_senha'      => $senha,
            'confirmar_senha' => $confirm,
        ]);
        $v->required('token',           'Token')
          ->required('nova_senha',      'Nova senha')
          ->minLength('nova_senha',     8, 'Nova senha')
          ->required('confirmar_senha', 'Confirmação de senha')
          ->confirmed('nova_senha', 'confirmar_senha', 'senha');

        if ($v->fails()) {
            $this->setFlash('error', $v->firstError());
            $this->redirect('/reset-password/' . urlencode($token));
            return;
        }

        $ok = $this->userModel->resetPassword($token, $senha);

        if (!$ok) {
            $this->setFlash('error', 'Link de redefinição inválido ou expirado.');
            $this->redirect('/forgot-password');
            return;
        }

        $this->setFlash('success', 'Senha redefinida com sucesso! Faça login.');
        $this->redirect('/');
    }
}
