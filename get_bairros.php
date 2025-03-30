<?php
require 'vendor/autoload.php'; // Carrega o SDK do Google

use Google\Cloud\BigQuery\BigQueryClient;

putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/credentials.json');

$projectId = 'ondeabastecer-455021';
$bigQuery = new BigQueryClient([
    'projectId' => $projectId,
]);

if (isset($_GET['municipio'])) {
    $municipio = $_GET['municipio'];

    // Adicionando depuração para verificar o município
    error_log("Municipio recebido: $municipio");

    $query = "SELECT DISTINCT bairro_revenda FROM `basedosdados.br_anp_precos_combustiveis.microdados`
              WHERE data_coleta >= '2025-02-01' AND id_municipio = '$municipio' GROUP BY bairro_revenda ORDER BY bairro_revenda ASC";

$query = "SELECT DISTINCT 
    m.nome AS nome_municipio, 
    b.bairro_revenda 
FROM `basedosdados.br_anp_precos_combustiveis.microdados` AS b
JOIN `basedosdados.br_bd_diretorios_brasil.municipio` AS m 
    ON b.id_municipio = m.id_municipio
WHERE b.data_coleta >= '2025-02-01'  
ORDER BY nome_municipio, bairro_revenda ASC;
";

/*


WHERE data_coleta >= '2025-02-01'
        AND id_municipio = '".$municipio."'
        AND bairro_revenda = '".$bairro."'
        AND produto IN ('".$produto."')
        GROUP BY nome_estabelecimento, bairro_revenda, produto, data_coleta
        ORDER BY preco_maximo ASC
        
        */
    try {
        $queryJobConfig = $bigQuery->query($query);
        $queryResults = $bigQuery->runQuery($queryJobConfig);

        $bairros = [];
        foreach ($queryResults as $row) {
            $bairros[] = $row['bairro_revenda'];
        }

        // Verificando se retornou algum bairro
        if (empty($bairros)) {
            error_log("Nenhum bairro encontrado para o município $municipio.");
        }

        echo json_encode($bairros);
    } catch (Exception $e) {
        // Em caso de erro na consulta
        error_log('Erro na consulta: ' . $e->getMessage());
        echo json_encode([]);
    }
} else {
    // Caso o parâmetro não esteja presente
    echo json_encode([]);
}
?>
