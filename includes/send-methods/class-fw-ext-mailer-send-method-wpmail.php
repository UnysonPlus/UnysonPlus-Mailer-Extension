<?php if (!defined('FW')) die('Forbidden');

class FW_Ext_Mailer_Send_Method_WPMail extends FW_Ext_Mailer_Send_Method {

        /**
         * @return string
         */
        public function get_id() {
                return 'wpmail';
        }

        /**
         * @return string
         */
        public function get_title() {
                return 'wp-mail';
        }

        /**
         * @return array
         */
        public function get_settings_options() {
                return array();
        }

        /**
         * @param array $values
         * @return array|WP_Error
         */
        public function prepare_settings_options_values($values) {
                return array();
        }

        private function make_email_header($address, $name) {
                $name    = (string) ($name ?? '');
                $address = (string) ($address ?? '');
                return (trim($name) !== '' ? ' '. htmlspecialchars($name, ENT_QUOTES, 'UTF-8') : '')
                        .' <'. htmlspecialchars($address, ENT_QUOTES, 'UTF-8') .'>';
        }

        /**
         * @param array $settings_options_values
         * @param FW_Ext_Mailer_Email $email
         * @param array $data
         * @return bool|WP_Error
         */
        public function send(FW_Ext_Mailer_Email $email, $settings_options_values, $data = array()) {
                $headers = array();

                $headers[] = 'Content-type: text/html; charset=utf-8';

                $from = (string) $email->get_from();
                if ($from === '') {
                        $from = (string) get_option('admin_email');
                }
                if (trim($from) !== '') {
                        $headers[] = 'From:'. $this->make_email_header($from, $email->get_from_name());
                }

                if (method_exists($email, 'get_reply_to')) {
                        $reply_to = $email->get_reply_to();
                        if (!empty($reply_to)) {
                                if (is_array($reply_to)) {
                                        foreach ($reply_to as $_address => $_name) {
                                                $headers[] = 'Reply-To:'. $this->make_email_header($_address, $_name);
                                        }
                                } else {
                                        $headers[] = 'Reply-To:'. $this->make_email_header($reply_to, '');
                                }
                        }
                }

                foreach ($email->get_cc() as $_address => $_name) {
                        $headers[] = 'Cc:'. $this->make_email_header($_address, $_name);
                }
                foreach ($email->get_bcc() as $_address => $_name) {
                        $headers[] = 'Bcc:'. $this->make_email_header($_address, $_name);
                }

                $attachments = method_exists($email, 'get_attachments') ? $email->get_attachments() : array();

                $result = wp_mail(
                        $email->get_to(),
                        (string) $email->get_subject(),
                        (string) $email->get_body(),
                        $headers,
                        $attachments
                );

                return $result
                        ? true
                        : new WP_Error(
                                'failed',
                                __('Could not send the email', 'fw')
                        );
        }

}
