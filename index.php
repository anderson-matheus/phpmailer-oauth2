<?php

date_default_timezone_set('Etc/UTC');

if (!isset($_GET['code']) && !isset($_GET['provider'])) {
    ?>
    <html>
        <body>
            <b>Clique no provedor de envio:</b><br /><br />
            <a href='?provider=Google'>Google</a><br />
        </body>
    </html>
<?php
exit;
}

require './vendor/autoload.php';

session_start();

use League\OAuth2\Client\Provider\Google;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$providerName = '';
if (array_key_exists('provider', $_GET)) {
    $providerName = $_GET['provider'];
    $_SESSION['provider'] = $providerName;
} elseif (array_key_exists('provider', $_SESSION)) {
    $providerName = $_SESSION['provider'];
}
if (!in_array($providerName, ['Google'])) {
    exit('Atualmente, apenas provedores do Google suportam esse script.');
}

$clientId = $_ENV['CLIENT_ID'];
$clientSecret = $_ENV['CLIENT_SECRET'];
$redirectUri = $_ENV['REDIRECT_URI'];
$email = $_ENV['EMAIL'];

$params = [
    'clientId' => $clientId,
    'clientSecret' => $clientSecret,
    'redirectUri' => $redirectUri
];

$options = [];
$provider = null;
$refreshToken = null;
$token = null;
$scope = $_ENV['SCOPE'];

switch ($providerName) {
    case 'Google':
        $provider = new Google($params);
        $options = [
            'response_type' => $_ENV['RESPONSE_TYPE'],
            'client_id' => $clientId,
            'scope' => [$scope],
            'redirect_uri' => $redirectUri,
            'login_hint' => $email,
            'access_type' => $_ENV['ACCESS_TYPE'],
            'prompt' => $_ENV['PROMPT'],
        ];
        break;
}

if (null === $provider) {
    exit('Selecione algum provedor de envio');
}

if (!isset($_GET['code'])) {
    $authUrl = $provider->getAuthorizationUrl($options);
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    unset($_SESSION['provider']);
    exit('Estado inválido');
} else {
    unset($_SESSION['provider']);
    $provider = new Google($params);
    $token = $provider->getAccessToken(
        'authorization_code',
        [
            'code' => $_GET['code'],
        ]
    );
    $refreshToken = $token->getRefreshToken();
}

if (is_null($token) || is_null($refreshToken)) {
    exit('Token ou Refresh token não existem, falhas na autenticação');
}

class GMailer extends PHPMailer
{
    public $googleClientId;
    public $googleClientSecret;
    public $email;
    public $redirectUri;
    public $refreshToken;
    public $scope;

    public function __construct(
        $googleClientId,
        $googleClientSecret,
        $email,
        $redirectUri,
        $refreshToken,
        $scope,
        $exceptions = null) {
        parent::__construct($exceptions);
        $this->googleClientId = $googleClientId;
        $this->googleClientSecret = $googleClientSecret;
        $this->email = $email;
        $this->redirectUri = $redirectUri;
        $this->refreshToken = $refreshToken;
        $this->scope = $scope;
    }

    public function send()
    {
        $client = new Google_Client();

        $client->setClientId($this->googleClientId);
        $client->setClientSecret($this->googleClientSecret);
        $client->setRedirectUri($this->redirectUri);
        $client->addScope($this->scope);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        $client->setSubject($this->email);

        $client->refreshToken($this->refreshToken);
        $newtoken = $client->getAccessToken();
        $client->setAccessToken($newtoken);

        $service = new Google_Service_Gmail($client);
        parent::preSend();
        $mime = rtrim(strtr(base64_encode($this->getSentMIMEMessage()), '+/', '-_'), '=');
        $msg = new Google_Service_Gmail_Message();
        $msg->setRaw($mime);
        return $service->users_messages->send('me', $msg);
    }

    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }
}

$mail = new GMailer(
    $clientId,
    $clientSecret,
    $email,
    $redirectUri,
    $refreshToken,
    $scope
);
$mail->setFrom($email, $_ENV['EMAIL_NAME']);
$mail->addAddress($email, $_ENV['EMAIL_NAME']);
$mail->Subject = 'PHPMailer GMail XOAUTH2';
$mail->CharSet = PHPMailer::CHARSET_UTF8;
$mail->addReplyTo($email);
$mail->msgHTML('<p>Envio de email com PHPMailer usando o provedor <b>GMail<b> com a autenticação <b>XOAUTH2</b></p>');
$mail->AltBody = 'This is a plain-text message body';
try {
    $mail->send();
    echo ('e-mail enviado com sucesso');
} catch (Exception $e) {
    var_dump($e->getMessage());
}