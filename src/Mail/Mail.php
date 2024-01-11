<?php


namespace App\Mail;

use App\Extension\IntlExtension;
use App\Lib\AlphiMail\AlphiMail;
use App\Lib\JWT\JsonWebToken;
use App\Model\User;
use App\Model\UserVerify;
use Slim\Views\Twig;

class Mail
{

	protected $alphiMail;
	protected $mail_content;
	protected $token_key;
	/**
	 * Mail constructor.
	 * @param array $mail_config
	 */
	public function __construct($mail_config)
	{
		$this->alphiMail = new AlphiMail($mail_config['host'], $mail_config['port']);
		$this->alphiMail->setLogin($mail_config['mail'], $mail_config['mail_password']);
		$this->alphiMail->setFrom($mail_config['mail'], $mail_config['mail_from']);
		$this->token_key = $mail_config['token_key'];

		$this->mail_content = Twig::create(__DIR__ . '/../../views', ['cache' => false]);
		$this->mail_content->getEnvironment()->getExtension('\Twig\Extension\CoreExtension')->setTimezone('Europe/Paris');
		$this->mail_content->addExtension(new IntlExtension());
	}

	/**
	 * @return string
	 */
	public function getTokenKey()
	{
		return $this->token_key;
	}

	/**
	 * @param $email
	 * @param $code
	 * @return bool
	 */
	public function sendVerifMail($email,$code){

		$this->alphiMail->addTo($email);
		$this->alphiMail->setSubject('Code de confirmation');

		$html = $this->mail_content->fetch('/mail/validaccount.twig', [
			'code' => $code
		]);
		$this->alphiMail->setHtmlMessage($html);

		return $this->alphiMail->send();
	}

	/**
	 * @param $userid
	 * @param $token
	 * @param $email
	 * @return bool
	 */
	public function sendPasswordMail($token,$userid,$email){

		$this->alphiMail->addTo($email);
		$this->alphiMail->setSubject('Réinitialiser votre mot de passe');

		$html = $this->mail_content->fetch('/mail/password.twig', [
			'token' => $token,
			'userid' => $userid
		]);
		$this->alphiMail->setHtmlMessage($html);

		return $this->alphiMail->send();
	}

}