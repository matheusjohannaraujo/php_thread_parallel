<?php 

/*
	GitHub: https://github.com/matheusjohannaraujo/php_thread_parallel
	Country: Brasil
	State: Pernambuco
	Developer: Matheus Johann Araujo
	Date: 2020-12-29
*/

error_reporting(E_ALL);
ini_set('default_charset', 'utf-8');
ini_set("set_time_limit", 3600);
ini_set("max_execution_time", 3600);
ini_set("default_socket_timeout", 3600);
ini_set("max_input_time", 3600);
ini_set("max_input_time", 3600);
ini_set("max_input_vars", 6000);
ini_set("memory_limit", "6144M");
ini_set("post_max_size", "6144M");
ini_set("upload_max_filesize", "6144M");
ini_set("max_file_uploads", 200);

require_once "work.php";
require_once "Promise.php";

/* Código construído com base nos links abaixo.
- https://www.toni-develops.com/2017/09/05/curl-multi-fetch/
- https://imasters.com.br/back-end/non-blocking-asynchronous-requests-usando-curlmulti-e-php */

/**
 * @param string|array $script
 * @param string $thread_http [optional, default = "http://localhost/php_thread_parallel/thread_http.php"]
 * @return Promise
 */
function thread_parallel($script, string $thread_http = "http://localhost/php_thread_parallel/thread_http.php") :Promise
{
    if (is_string($script)) {
        $script = [$script];
    }
    if (!is_array($script)) {
        $script = ['echo "invalid script";'];
    }
    return new Promise(function($resolve, $reject) use ($script, $thread_http) {
        $mch = null;
        try {
            // Inicializa um multi-curl handle
            $mch = curl_multi_init();
            foreach ($script as $key => $value) {
                $data = ["script" =>  base64_encode(trim($value))];
                $script[$key] = null;
                // Inicializa e seta as opções para cada requisição
                $script[$key] = curl_init($thread_http);
                curl_setopt($script[$key], CURLOPT_RETURNTRANSFER, true);
                curl_setopt($script[$key], CURLOPT_POSTFIELDS, $data);
                // Adiciona a requisição channel ($script[$key]) ao multi-curl handle ($mch)
                curl_multi_add_handle($mch, $script[$key]);
                unset($data);
            }
            $uid = null;
            $uid = setInterval(function() use (&$uid, &$mch, &$script, &$resolve) {
                $active = null;
                curl_multi_exec($mch, $active);
                if ($active === 0) {
                    clearInterval($uid);
                    foreach ($script as $key => $ch) {
                        // Acessa a resposta de cada requisição
                        $script[$key] = base64_decode(curl_multi_getcontent($ch));
                        // Remove o channel ($ch) da requisição do multi-curl handle ($mch)
                        curl_multi_remove_handle($mch, $ch);
                        // Fecha o channel ($ch)
                        curl_close($ch);
                    }
                    // Fecha o multi-curl handle ($mch)
                    curl_multi_close($mch);
                    $resolve($script);
                }
            }, 100);
        } catch (\Throwable $th) {
            // Fecha o multi-curl handle ($mch)
            curl_multi_close($mch);
            $reject($th);
        };
    });
}
