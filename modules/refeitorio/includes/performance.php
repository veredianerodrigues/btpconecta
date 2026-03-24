<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists('RD_Performance') ) {
    class RD_Performance {
        private static $instance = null;
        private $cache_group = 'rd_queries';
        private $cache_expiration = 300;

        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            add_action('init', [$this, 'setup_lazy_loading']);
            add_action('rd_clear_cache', [$this, 'clear_all_cache']);
            add_action('rd_weekly_cache_cleanup', [$this, 'clear_all_cache']);
        }

        public function setup_lazy_loading() {
            if (!is_admin()) {
                add_filter('script_loader_tag', [$this, 'add_defer_attribute'], 10, 2);
            }
        }

        public function add_defer_attribute($tag, $handle) {
            $defer_scripts = ['rd-form', 'rd-list', 'rd-cards', 'rd-admin'];

            if (in_array($handle, $defer_scripts)) {
                return str_replace(' src', ' defer src', $tag);
            }

            return $tag;
        }

        public function get_cached_query($key, $callback, $args = []) {
            $cache_key = $this->generate_cache_key($key, $args);
            $cached = get_transient($cache_key);

            if (false !== $cached) {
                return $cached;
            }

            $result = call_user_func_array($callback, $args);

            set_transient($cache_key, $result, $this->cache_expiration);

            return $result;
        }

        public function clear_cache($key, $args = []) {
            $cache_key = $this->generate_cache_key($key, $args);
            delete_transient($cache_key);
        }

        public function clear_all_cache() {
            global $wpdb;

            $pattern = '_transient_rd_cache_%';
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $pattern
                )
            );

            $pattern_timeout = '_transient_timeout_rd_cache_%';
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $pattern_timeout
                )
            );
        }

        private function generate_cache_key($key, $args = []) {
            $args_hash = md5(serialize($args));
            return 'rd_cache_' . $key . '_' . $args_hash;
        }

        public function cache_refeicao_list($type, $matricula = null, $filters = []) {
            $cache_key_parts = array_merge(
                ['list', $type],
                $matricula ? [$matricula] : [],
                $filters
            );

            return $this->get_cached_query(
                implode('_', array_filter($cache_key_parts)),
                function() use ($type, $matricula, $filters) {
                    if ($type === 'user' && $matricula) {
                        return rd_refeicao_list_user($matricula, $filters);
                    } elseif ($type === 'admin') {
                        return rd_refeicao_list_admin($filters);
                    }
                    return [];
                }
            );
        }

        public function invalidate_refeicao_cache($matricula = null, $date = null) {
            $patterns = [
                'list_user',
                'list_admin',
                'count',
                'form_data',
            ];

            foreach ($patterns as $pattern) {
                if ($matricula) {
                    $this->clear_cache($pattern . '_' . $matricula);
                }
                if ($date) {
                    $this->clear_cache($pattern . '_' . $date);
                }
                $this->clear_cache($pattern);
            }
        }

        public function cache_form_data($matricula) {
            return $this->get_cached_query(
                'form_data_' . $matricula,
                function() use ($matricula) {
                    $nome = function_exists('rd_user_known_name') ? rd_user_known_name($matricula) : '';

                    $types = function_exists('rd_get_meal_types_codes')
                        ? rd_get_meal_types_codes()
                        : ['QVeggie', 'QLight', 'QSabor'];

                    $local_opts = function_exists('rd_local_retirada_options')
                        ? rd_local_retirada_options()
                        : [];

                    $default_local = '';
                    if (!empty($local_opts) && function_exists('rd_db_table_full')) {
                        global $wpdb;
                        $table = rd_db_table_full();
                        $last = $wpdb->get_var($wpdb->prepare(
                            "SELECT local_retirada FROM {$table}
                             WHERE matricula = %s AND local_retirada <> ''
                             ORDER BY updated_at DESC, id DESC LIMIT 1",
                            $matricula
                        ));

                        if ($last && isset($local_opts[$last])) {
                            $default_local = $last;
                        }
                    }

                    return [
                        'matricula' => $matricula,
                        'nome_completo' => $nome,
                        'meal_types' => $types,
                        'local_retirada_options' => $local_opts,
                        'local_retirada' => $default_local,
                    ];
                }
            );
        }
    }
}

function rd_performance() {
    return RD_Performance::get_instance();
}

add_action('plugins_loaded', 'rd_performance');

function rd_cache_get($key, $callback, $args = []) {
    return rd_performance()->get_cached_query($key, $callback, $args);
}

function rd_cache_clear($key, $args = []) {
    rd_performance()->clear_cache($key, $args);
}

function rd_cache_invalidate_refeicao($matricula = null, $date = null) {
    rd_performance()->invalidate_refeicao_cache($matricula, $date);
}
