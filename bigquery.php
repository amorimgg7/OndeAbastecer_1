<?php

require 'vendor/autoload.php'; // Carrega o SDK do Google

use Google\Cloud\BigQuery\BigQueryClient;

if (!file_exists(__DIR__ . '/credentials.json')) {
    die('Arquivo de credenciais não encontrado!');
} else {
    echo 'Arquivo de credenciais encontrado!';

    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/credentials.json'); // Define o caminho das credenciais

    
}



$projectId = 'ondeabastecer-455021'; // Substitua pelo seu ID do projeto no Google Cloud

// Inicializa o cliente BigQuery
$bigQuery = new BigQueryClient([
    'projectId' => $projectId,
]);

// Query SQL
$query = "SELECT nome_estabelecimento as NOME, bairro_revenda as BAIRRO, produto as PRODUTO, data_coleta, 'IBGE' AS FONTE,  
                 MAX(preco_venda) AS preco_maximo
          FROM `basedosdados.br_anp_precos_combustiveis.microdados`
          WHERE data_coleta >= '2025-03-01'

            AND id_municipio IN (/*'3301702',*/ '3304557')
            --AND produto = 'Diesel S10'
            --AND bairro_revenda IN ('Caxias')
            AND bairro_revenda IN ('Barra Da Tijuca', 'Jacarepagua', 'Recreio Dos Bandeirantes')
          GROUP BY nome_estabelecimento, bairro_revenda, produto, data_coleta
          ORDER BY /*data_coleta DESC,*/ preco_maximo ASC";

$queryJobConfig = $bigQuery->query($query);
$queryResults = $bigQuery->runQuery($queryJobConfig);

if ($queryResults->isComplete()) {
    echo "<table border='1'>";
    echo "<tr>";
    

    // Verifica se não houve resultados e exibe uma mensagem apropriada
if (iterator_count($queryResults) === 0) {
    echo "<tr><td colspan='5'>Nenhum dado encontrado</td></tr>";
}

    // Pegando os nomes das colunas
    $firstRow = true;
    foreach ($queryResults as $row) {
        if ($firstRow) {
            foreach ($row as $key => $value) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $firstRow = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    

    


    echo "</table>";
} else {
    echo "A consulta não foi concluída.";
}
