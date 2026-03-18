<?php declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Mail - PHPMailer wrapper
 */
class Mail
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function configure(): void
    {
        $m = $this->mailer;

        $m->isSMTP();
        $m->Host       = $_ENV['MAIL_HOST']       ?? 'smtp.gmail.com';
        $m->SMTPAuth   = true;
        $m->Username   = $_ENV['MAIL_USERNAME']   ?? '';
        $m->Password   = $_ENV['MAIL_PASSWORD']   ?? '';
        $m->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $m->Port       = (int) ($_ENV['MAIL_PORT'] ?? 587);
        $m->CharSet    = 'UTF-8';

        $m->setFrom(
            $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@financas.com',
            $_ENV['MAIL_FROM_NAME']    ?? 'JW Finanças'
        );
    }

    /**
     * Send a password-reset e-mail.
     */
    public function sendPasswordReset(string $email, string $name, string $token): bool
    {
        $appUrl  = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
        $appPath = $_ENV['APP_BASE_PATH'] ?? '/financas/public';
        $link    = $appUrl . $appPath . '/reset-password/' . $token;
        $appName = $_ENV['APP_NAME'] ?? 'JW Finanças';

        $subject = "Redefinição de senha – {$appName}";
        $body    = $this->buildResetEmailHtml($name, $link, $appName);

        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;
            $this->mailer->AltBody = "Olá {$name},\n\nAcesse o link para redefinir sua senha:\n{$link}\n\nEste link expira em 2 horas.";
            $this->mailer->send();
            return true;
        } catch (MailException $e) {
            error_log('Mail error: ' . $e->getMessage());
            return false;
        }
    }

    private function buildResetEmailHtml(string $name, string $link, string $appName): string
    {
        $firstName = explode(' ', $name)[0];
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Redefinição de Senha</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Inter',sans-serif;background:#f1f5f9;color:#1e293b;}
  .wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);}
  .header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:48px 40px;text-align:center;}
  .header h1{color:#fff;font-size:28px;font-weight:700;margin-top:12px;}
  .header p{color:rgba(255,255,255,.85);font-size:14px;margin-top:4px;}
  .icon{width:64px;height:64px;background:rgba(255,255,255,.2);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:28px;}
  .body{padding:40px;}
  .body h2{font-size:20px;font-weight:700;margin-bottom:8px;}
  .body p{font-size:15px;color:#475569;line-height:1.7;margin-bottom:16px;}
  .btn{display:inline-block;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;text-decoration:none;padding:14px 36px;border-radius:8px;font-weight:600;font-size:16px;margin:16px 0;}
  .notice{background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:16px;font-size:13px;color:#713f12;margin-top:24px;}
  .footer{padding:24px 40px;border-top:1px solid #e2e8f0;text-align:center;font-size:12px;color:#94a3b8;}
  .link-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;word-break:break-all;font-size:12px;color:#6366f1;margin-top:8px;}
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="icon">🔐</div>
    <h1>{$appName}</h1>
    <p>Sistema de Finanças Pessoais</p>
  </div>
  <div class="body">
    <h2>Olá, {$firstName}! 👋</h2>
    <p>Recebemos uma solicitação para redefinir a senha da sua conta no <strong>{$appName}</strong>.</p>
    <p>Clique no botão abaixo para criar uma nova senha. Este link é válido por <strong>2 horas</strong>.</p>
    <div style="text-align:center;">
      <a href="{$link}" class="btn">Redefinir Minha Senha</a>
    </div>
    <p style="font-size:13px;color:#64748b;">Se o botão não funcionar, copie e cole o link abaixo no seu navegador:</p>
    <div class="link-box">{$link}</div>
    <div class="notice">
      ⚠️ <strong>Não solicitou isso?</strong> Se você não pediu a redefinição de senha, ignore este e-mail. Sua conta permanece segura.
    </div>
  </div>
  <div class="footer">
    <p>&copy; 2024 {$appName}. Todos os direitos reservados.</p>
    <p style="margin-top:4px;">Este é um e-mail automático, por favor não responda.</p>
  </div>
</div>
</body>
</html>
HTML;
    }
}
