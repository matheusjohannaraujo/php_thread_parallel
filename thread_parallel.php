<?php 

/*
	GitHub: https://github.com/matheusjohannaraujo/php_thread_parallel
	Country: Brasil
	State: Pernambuco
	Developer: Matheus Johann Araujo
	Date: 2021-07-02
*/

error_reporting(E_ALL);
ini_set("default_charset", "utf-8");
ini_set("set_time_limit", "3600");
ini_set("max_execution_time", "3600");
ini_set("default_socket_timeout", "3600");
ini_set("max_input_time", "3600");
ini_set("max_input_time", "3600");
ini_set("max_input_vars", "6000");
ini_set("memory_limit", "6144M");
ini_set("post_max_size", "6144M");
ini_set("upload_max_filesize", "6144M");
ini_set("max_file_uploads", "200");

/** Código construído com base nos links abaixo.
 *  - https://www.toni-develops.com/2017/09/05/curl-multi-fetch
 *  - https://imasters.com.br/back-end/non-blocking-asynchronous-requests-usando-curlmulti-e-php
 *  - https://www.php.net/manual/pt_BR/function.curl-setopt.php
 *  - https://thiagosantos.com/blog/623/php/php-curl-timeout-e-connecttimeout
 *
 * @param string|array $script
 * @param bool $waitResponse [optional, default = true]
 * @param bool $infoRequest [optional, default = true]
 * @param string|null $thread_http [optional, default = null]
 * @return array
 */
function thread_parallel(
    $script,
    bool $waitResponse = true,
    bool $infoRequest = true,
    string $thread_http = "http://localhost/php_thread_parallel/thread_http.php"
) :array
{
    if (is_string($script)) {
        $script = [$script];
    }
    if (!is_array($script)) {
        $script = ['echo "invalid script";'];
    }
    if (!$waitResponse) {
        // https://www.php.net/manual/pt_BR/function.curl-setopt.php
        // https://thiagosantos.com/blog/623/php/php-curl-timeout-e-connecttimeout/
        foreach ($script as $key => $value) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $thread_http);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ["script" => base64_encode(trim($value))]);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            // Tempo em que o client pode aguardar para conectar no server
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            // Tempo em que o solicitante espera por uma resposta
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
            $response = base64_decode(curl_exec($ch));
            $script[$key] = [
                "await" => false,
                "response" => empty($response) ? null : $response,
                "error" => curl_errno($ch) ? curl_error($ch) : null,
                "info" => $infoRequest ? curl_getinfo($ch) : null
            ];
            curl_close($ch);            
        }
    } else {
        // Inicializa um multi-curl handle
        $mch = curl_multi_init();
        foreach ($script as $key => $value) {
            $script[$key] = null;
            // Inicializa e seta as opções para cada requisição
            $script[$key] = curl_init();
            curl_setopt($script[$key], CURLOPT_URL, $thread_http);
            curl_setopt($script[$key], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($script[$key], CURLOPT_POSTFIELDS, ["script" => base64_encode(trim($value))]);
            // Adiciona a requisição channel ($script[$key]) ao multi-curl handle ($mch)
            curl_multi_add_handle($mch, $script[$key]);
        }    
        // Fica em busy-waiting até que todas as requisições retornem
        do {
            $active = null;
            // Executa as requisições definidas no multi-curl handle, e retorna imediatamente o status das requisições
            curl_multi_exec($mch, $active);
            usleep(50);        
        } while($active > 0);
        foreach ($script as $key => $ch) {
            $script[$key] = [
                "await" => true,
                "response" => base64_decode(curl_multi_getcontent($ch)),// Acessa a resposta de cada requisição
                "error" => curl_errno($ch) ? curl_error($ch) : null,
                "info" => $infoRequest ? curl_getinfo($ch) : null
            ];
            // Remove o channel ($ch) da requisição do multi-curl handle ($mch)
            curl_multi_remove_handle($mch, $ch);
            // Fecha o channel ($ch)
            curl_close($ch);
        }
        // Fecha o multi-curl handle ($mch)
        curl_multi_close($mch);
    }    
    return $script;
}
