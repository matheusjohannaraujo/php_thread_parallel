<?php 

/*
	GitHub: https://github.com/matheusjohannaraujo/php_thread_parallel
	Country: Brasil
	State: Pernambuco
	Developer: Matheus Johann Araujo
	Date: 2020-12-29
*/

declare(ticks=1);

require_once "thread_parallel.php";

/*

	Este código permite executar scritps PHP de forma simultanea,
	onde cada script será executado em uma Thread distinta.
	-
	Observação: Os scripts serão executados de forma paralela, porém
	o script (main) que criou as Threads vai aguardar até que todos
	os scripts terminem de executar para retornar o resultado da
	execução de cada script. É semelhante a criar várias Threads e
	executar o comando "JOIN" que só deixa o script principal (main)
	prosseguir quando as Threads terminam de serem executadas.

*/

// Scripts PHP que serão executados na Thread HTTP
$scripts = [
'
	// SCRIPT 1
	echo "hello";
',
'
	// SCRIPT 2
	function test() {
		sleep(1);
		echo "Software desenvolvido por Matheus Johann Araujo";
	}
	test();
',
'
	// SCRIPT 3
	echo "loop ";
	for ($i = 0; $i <= 20; $i++) { 
		echo $i, " ";
	}
',
'
	// SCRIPT 4
	for ($i = 0; $i < 10; $i++) { 
		file_put_contents("a.txt", $i);
		sleep(1);	
	}
	unlink("a.txt");
',
'
	// SCRIPT 5
	for ($i = 0; $i < 10; $i++) { 
		file_put_contents("b.txt", $i);
		sleep(1);	
	}
	unlink("b.txt");
'
];

// Localização do arquivo Thread HTTP
$thread_http = "http://localhost/php_thread_parallel/thread_http.php";

// Cria uma thread para cada script, e aguarda o fim da execução de todos os scripts para obter o respectivo resultado de cada um.
$promise = thread_parallel($scripts, $thread_http);

$promise
	->then(function($res) {
		// Mostra o conteúdo retornado ao fim da execução de cada script.
		var_export($res);
	})
	->catch(function($err) {
		var_export($err);
	});

workWait(function() { usleep(1); });
