<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\User;

/**
 * UserController
 */
class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    public function profile(): void
    {
        $this->requireAuth();

        $user = $this->userModel->find($this->getUserId());

        $this->view('user/profile', [
            'user'  => $user,
            'csrf'  => $this->csrfToken(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function updateProfile(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = $this->getUserId();

        $data = [
            'nome'            => trim($_POST['nome']            ?? ''),
            'email'           => trim($_POST['email']           ?? ''),
            'telefone'        => trim($_POST['telefone']        ?? ''),
            'data_nascimento' => !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null,
            'municipio'       => trim($_POST['municipio']       ?? ''),
            'uf'              => strtoupper(trim($_POST['uf']   ?? '')),
        ];

        $v = new Validator($data);
        $v->required('nome',  'Nome')
          ->required('email', 'E-mail')
          ->email('email');

        if ($v->fails()) {
            $this->setFlash('error', $v->firstError());
            $this->redirect('/perfil');
            return;
        }

        $this->userModel->updateProfile($userId, $data);

        // Update session
        $sessionUser = $this->session->get('user', []);
        $sessionUser['nome']  = $data['nome'];
        $sessionUser['email'] = $data['email'];
        $this->session->set('user', $sessionUser);

        $this->setFlash('success', 'Perfil atualizado com sucesso!');
        $this->redirect('/perfil');
    }

    public function changePassword(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = $this->getUserId();
        $user   = $this->userModel->find($userId);

        if ($user === null) {
            $this->setFlash('error', 'Usuário não encontrado.');
            $this->redirect('/perfil');
            return;
        }

        $senhaAtual    = $_POST['senha_atual']       ?? '';
        $novaSenha     = $_POST['nova_senha']        ?? '';
        $confirmSenha  = $_POST['confirmar_senha']   ?? '';

        if (!$this->userModel->verifyPassword($senhaAtual, (string) $user['senha'])) {
            $this->setFlash('error', 'Senha atual incorreta.');
            $this->redirect('/perfil');
            return;
        }

        $v = new Validator(['nova_senha' => $novaSenha, 'confirmar_senha' => $confirmSenha]);
        $v->required('nova_senha', 'Nova senha')
          ->minLength('nova_senha', 8, 'Nova senha')
          ->confirmed('nova_senha', 'confirmar_senha', 'senha');

        if ($v->fails()) {
            $this->setFlash('error', $v->firstError());
            $this->redirect('/perfil');
            return;
        }

        $this->userModel->changePassword($userId, $novaSenha);
        $this->setFlash('success', 'Senha alterada com sucesso!');
        $this->redirect('/perfil');
    }
}
