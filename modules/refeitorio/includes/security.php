<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists('RD_Security') ) {
    class RD_Security {
        private static $instance = null;

        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            add_action('rest_api_init', [$this, 'setup_rate_limiting']);
            add_filter('wp_handle_upload_prefilter', [$this, 'validate_upload_mime'], 10, 1);
        }

        public function setup_rate_limiting() {
            add_filter('rest_pre_dispatch', [$this, 'check_rate_limit'], 10, 3);
        }

        public function check_rate_limit($result, $server, $request) {
            $route = $request->get_route();

            if (strpos($route, '/rd/v1/') !== 0) {
                return $result;
            }

            $method = $request->get_method();
            if (!in_array($method, ['POST', 'PATCH', 'DELETE'])) {
                return $result;
            }

            $ip = $this->get_client_ip();
            $user_id = get_current_user_id();
            $identifier = $user_id ? 'user_' . $user_id : 'ip_' . $ip;

            $limits = [
                'POST' => ['requests' => 30, 'window' => 60],
                'PATCH' => ['requests' => 20, 'window' => 60],
                'DELETE' => ['requests' => 10, 'window' => 60],
            ];

            $limit_config = $limits[$method];
            $transient_key = 'rd_rate_limit_' . $method . '_' . md5($identifier);

            $requests = get_transient($transient_key);

            if (false === $requests) {
                set_transient($transient_key, 1, $limit_config['window']);
            } else {
                if ($requests >= $limit_config['requests']) {
                    $this->log_unauthorized_attempt($identifier, $route, 'rate_limit_exceeded');

                    return new WP_Error(
                        'rd_rate_limit',
                        sprintf('Limite de %d requisições por %d segundos excedido',
                            $limit_config['requests'],
                            $limit_config['window']
                        ),
                        ['status' => 429]
                    );
                }
                set_transient($transient_key, $requests + 1, $limit_config['window']);
            }

            return $result;
        }

        public function validate_upload_mime($file) {
            $allowed_mimes = [
                'image/jpeg' => 'jpg|jpeg',
                'image/png' => 'png',
                'application/pdf' => 'pdf',
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'application/zip' => 'zip',
            ];

            if (isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!array_key_exists($mime, $allowed_mimes)) {
                    $file['error'] = sprintf('Tipo de arquivo não permitido: %s', $mime);
                    $this->log_unauthorized_attempt('upload', $file['name'], 'invalid_mime_type');
                } else {
                    $allowed_exts = explode('|', $allowed_mimes[$mime]);
                    if (!in_array($ext, $allowed_exts)) {
                        $file['error'] = sprintf('Extensão incompatível com MIME type: %s vs %s', $ext, $mime);
                        $this->log_unauthorized_attempt('upload', $file['name'], 'mime_extension_mismatch');
                    }
                }
            }

            return $file;
        }

        public function log_unauthorized_attempt($identifier, $resource, $reason) {
            if (function_exists('rd_log')) {
                rd_log('Tentativa de acesso não autorizado', [
                    'identifier' => $identifier,
                    'resource' => $resource,
                    'reason' => $reason,
                    'ip' => $this->get_client_ip(),
                    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                    'timestamp' => current_time('mysql'),
                ]);
            }
        }

        private function get_client_ip() {
            $ip = '';

            if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
                $ip = $_SERVER['HTTP_X_REAL_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }

            return filter_var(trim($ip), FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
        }
    }
}

function rd_security_init() {
    return RD_Security::get_instance();
}

add_action('plugins_loaded', 'rd_security_init');
