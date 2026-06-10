<?php if (!defined('FW')) die('Forbidden');

class FW_Ext_Mailer_Send_Method_SMTP extends FW_Ext_Mailer_Send_Method {

	/**
	 * @return string
	 */
	public function get_id() {
		return 'smtp';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return 'SMTP';
	}

	/**
	 * @return array
	 */
	public function get_settings_options() {
		return array(
			'host' => array(
				'label' => __( 'Server Address', 'fw' ),
				'desc'  => __( 'Enter your email server', 'fw' ),
				'type'  => 'text',
				'value' => '',
			),
			'username' => array(
				'label' => __( 'Username', 'fw' ),
				'desc'  => __( 'Enter your username', 'fw' ),
				'type'  => 'text',
				'value' => '',
			),
			'password' => array(
				'label' => __( 'Password', 'fw' ),
				'desc'  => __( 'Enter your password', 'fw' ),
				'type'  => 'password',
				'value' => '',
			),
			'secure' => array(
				'label'   => __( 'Secure Connection', 'fw' ),
				'type'    => 'radio',
				'inline'  => true,
				'value'   => 'no',
				'choices' => array(
					'no'  => 'No',
					'ssl' => 'SSL',
					'tls' => 'TLS'
				)
			),
			'port' => array(
				'label' => __( 'Custom Port', 'fw' ),
				'desc'  => __( 'Optional - SMTP port number to use.', 'fw' ),
				'help'  => __( 'Leave blank for default (SMTP - 25, SMTPS - 465, STARTTLS - 587)', 'fw' ),
				'type'  => 'text',
				'attr'  => array(
					'maxlength' => 5,
				),
				'value' => '',
			),
		);
	}

	/**
	 * @param array $values
	 * @return array|WP_Error
	 */
	public function prepare_settings_options_values($values) {
		$values = is_array($values) ? $values : array();

		$conf = array(
			'host'      => trim((string) ($values['host'] ?? '')),
			'username'  => trim((string) ($values['username'] ?? '')),
			'password'  => trim((string) ($values['password'] ?? '')),
			'secure'    => (string) ($values['secure'] ?? ''),
			'port'      => trim((string) ($values['port'] ?? ''))
		);

		if (empty($conf['username'])) {
			return new WP_Error(
				'empty_username',
				__('Username cannot be empty', 'fw')
			);
		}

		if (empty($conf['password'])) {
			return new WP_Error(
				'empty_password',
				__('Password cannot be empty', 'fw')
			);
		}

		if (!fw_is_valid_domain_name($conf['host'])) {
			return new WP_Error(
				'invalid_host',
				__('Invalid host', 'fw')
			);
		}

		if (!in_array($conf['secure'], array('ssl', 'tls'), true)) {
			$conf['secure'] = '';
		}

		// in case the port is missing or invalid
		if (empty($conf['port']) || !is_numeric($conf['port'])) {
			$conf['port'] = 25;

			if ($conf['secure'] === 'ssl') {
				$conf['port'] = 465;
			} elseif ($conf['secure'] === 'tls') {
				$conf['port'] = 587;
			}
		} else {
			$conf['port'] = (int) $conf['port'];
		}

		return $conf;
	}

	/**
	 * @param array $settings_options_values
	 * @param FW_Ext_Mailer_Email $email
	 * @param array $data
	 * @return bool|WP_Error
	 */
	public function send(FW_Ext_Mailer_Email $email, $settings_options_values, $data = array()) {
		// WP 5.5+ uses namespaced PHPMailer; older WP shipped class-phpmailer.php in wp-includes
		if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
			if (file_exists(ABSPATH . WPINC . '/PHPMailer/PHPMailer.php')) {
				require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
				require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
				require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			} elseif (file_exists(ABSPATH . WPINC . '/class-phpmailer.php')) {
				require_once ABSPATH . WPINC . '/class-phpmailer.php';
			} else {
				return new WP_Error(
					'phpmailer_missing',
					__('PHPMailer is not available on this WordPress install', 'fw')
				);
			}
		}

		$config = self::prepare_settings_options_values($settings_options_values);

		if (is_wp_error($config)) {
			return $config;
		}

		$mailer_class = class_exists('PHPMailer\\PHPMailer\\PHPMailer')
			? 'PHPMailer\\PHPMailer\\PHPMailer'
			: 'PHPMailer';

		// passing true makes PHPMailer throw exceptions instead of returning false
		$mailer = new $mailer_class(true);

		$mailer->isSMTP();
		$mailer->isHTML(true);
		$mailer->Host       = $config['host'];
		$mailer->Port       = $config['port'];
		$mailer->SMTPSecure = $config['secure']; // '', 'ssl', or 'tls'
		$mailer->SMTPAuth   = true;
		$mailer->Username   = $config['username'];
		$mailer->Password   = $config['password'];
		$mailer->CharSet    = 'utf-8';

		$from = trim((string) $email->get_from());
		if ($from === '') {
			$from = (string) get_option('admin_email');
		}
		if ($from !== '') {
			try {
				$mailer->setFrom($from, (string) $email->get_from_name());
			} catch (Exception $e) {
				return new WP_Error('failed', $e->getMessage());
			}
		}

		try {
			$to = $email->get_to();
			if (is_array($to)) {
				foreach ($to as $_address) {
					$mailer->addAddress($_address);
				}
			} else {
				$mailer->addAddress($to);
			}

			$reply_to = method_exists($email, 'get_reply_to') ? $email->get_reply_to() : '';
			if (!empty($reply_to)) {
				if (is_array($reply_to)) {
					foreach ($reply_to as $_address => $_name) {
						$mailer->addReplyTo($_address, (string) $_name);
					}
				} else {
					$mailer->addReplyTo($reply_to);
				}
			}

			foreach ($email->get_cc() as $_address => $_name) {
				$mailer->addCC($_address, (string) $_name);
			}
			foreach ($email->get_bcc() as $_address => $_name) {
				$mailer->addBCC($_address, (string) $_name);
			}

			$mailer->Subject = (string) $email->get_subject();
			$mailer->Body    = (string) $email->get_body();

			if (method_exists($email, 'get_attachments')) {
				foreach ($email->get_attachments() as $attachment) {
					if (is_string($attachment) && is_file($attachment)) {
						$mailer->addAttachment($attachment);
					}
				}
			}

			return $mailer->send()
				? true
				: new WP_Error(
					'failed',
					__('Could not send the email', 'fw')
				);
		} catch (\PHPMailer\PHPMailer\Exception $e) {
			return new WP_Error('failed', $e->errorMessage());
		} catch (Exception $e) {
			return new WP_Error('failed', $e->getMessage());
		}
	}
}
