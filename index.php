<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Postos</title>
    <script>
        function atualizarBairros() {
            var municipio = document.getElementById("municipio_posto").value;
            var bairroSelect = document.getElementById("bairro_posto");

            // Limpa os bairros antes de adicionar novas opções
            bairroSelect.innerHTML = "<option value=''>Selecione um bairro</option>";

            // Verificando se o município foi selecionado
            if (!municipio) {
                return;
            }

            // Busca os bairros do município selecionado no banco
            fetch(`get_bairros.php?municipio=${municipio}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Bairros recebidos:', data); // Adicionando log para ver o que veio

                    if (data.length > 0) {
                        data.forEach(function(bairro) {
                            var option = document.createElement("option");
                            option.value = bairro;
                            option.text = bairro;
                            bairroSelect.appendChild(option);
                        });
                    } else {
                        var option = document.createElement("option");
                        option.value = "";
                        option.text = "Nenhum bairro encontrado";
                        bairroSelect.appendChild(option);
                    }
                })
                .catch(error => console.error('Erro ao carregar bairros:', error));
        }
    </script>
</head>
<body>

<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



echo 'PHP Version: ' . phpversion();
//shell_exec('composer --version');

require 'vendor/autoload.php'; // Carrega o SDK do Google

use Google\Cloud\BigQuery\BigQueryClient;

//putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/credentials.json'); // Define o caminho das credenciais
if (!file_exists(__DIR__ . '/credentials.json')) {
    die('Arquivo de credenciais não encontrado!');
} else {
    //echo 'Arquivo de credenciais encontrado!';

    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/credentials.json'); // Define o caminho das credenciais

    
}

$projectId = 'ondeabastecer-455021'; // Substitua pelo seu ID do projeto no Google Cloud

// Inicializa o cliente BigQuery
$bigQuery = new BigQueryClient([
    'projectId' => $projectId,
]);

if(isset($_POST['consulta_posto'])) {
    // Pegando os parâmetros do formulário
    $municipio = $_POST['municipio_posto'];
    $bairro = $_POST['bairro_posto'];
    $produto = $_POST['produto_posto'];

    // Logando os parâmetros
    echo "<pre>";
    echo "Município: " . $municipio . "<br>";
    echo "Bairro: " . $bairro . "<br>";
    echo "Produto: " . $produto . "<br>";
    echo "</pre>";

    // Consulta SQL para obter os dados
    $query = "SELECT nome_estabelecimento AS NOME, cep_revenda, endereco_revenda, bairro_revenda AS BAIRRO, produto AS PRODUTO, data_coleta, 'IBGE' AS FONTE,  
        MAX(preco_venda) AS preco_maximo
        FROM `basedosdados.br_anp_precos_combustiveis.microdados`
        WHERE data_coleta >= '2025-02-01'
        AND id_municipio = '".$municipio."'
        AND bairro_revenda = '".$bairro."'
        AND produto IN ('".$produto."')
        GROUP BY nome_estabelecimento, bairro_revenda, produto, data_coleta, cep_revenda, endereco_revenda
        ORDER BY preco_maximo ASC";

    echo "Consulta SQL: " . $query . "<br>"; // Log da consulta SQL

    $queryJobConfig = $bigQuery->query($query);
    $queryResults = $bigQuery->runQuery($queryJobConfig);

    if ($queryResults->isComplete()) {
        echo "<table border='1'>";
        echo "<tr>";
    
        // Verifica se não houve resultados
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
            foreach ($row as $key => $value) {
                if ($key === 'cep_revenda') {
                    echo "<td>
                            <button onclick=\"updateMap('$value')\">Ver no Mapa</button>
                            <a href='https://www.google.com/maps/dir/?api=1&destination=" . urlencode($value) . "' target='_blank'>Abrir no Google Maps</a>
                          </td>";
                } else {
                    echo "<td>$value</td>";
                }
            }
            echo "</tr>";
        }
    
        echo "</table>";
    }
     else {
        echo "A consulta não foi concluída.";
    }
} else {
    // Consulta SQL para obter os municípios (id_municipio)
    $query = "SELECT DISTINCT 
    b.id_municipio, 
    m.sigla_uf, 
    m.nome AS nome_municipio
FROM `basedosdados.br_anp_precos_combustiveis.microdados` AS b
JOIN `basedosdados.br_bd_diretorios_brasil.municipio` AS m 
    ON b.id_municipio = m.id_municipio
ORDER BY m.sigla_uf ASC, m.nome ASC;
";
    $queryJobConfig = $bigQuery->query($query);
    $queryResults = $bigQuery->runQuery($queryJobConfig);

    echo '<form method="POST">';
    echo '<label for="municipio_posto">Município</label></br>';
    echo '<select id="municipio_posto" name="municipio_posto" onchange="atualizarBairros()">';
    echo '<option value="">Selecione um município</option>';
    echo '<option value="3304557">Rio de Janeiro RJ</option>';
    
    // Mostra as opções dos municípios
    foreach ($queryResults as $row) {
        echo '<option value="' . $row['id_municipio'] . '">' . $row['id_municipio'] . ' -  ' . $row['nome_municipio'] . '('. $row['sigla_uf'] .')</option>';
    }
    echo '</select></br></br>';

    echo '<label for="bairro_posto">Bairro</label></br>';
    echo '<select id="bairro_posto" name="bairro_posto">';
    echo '<option value="">Selecione um município primeiro</option>';
    echo '</select></br></br>';

    echo '<label for="produto_posto">Produto</label></br>';
    echo '<select id="produto_posto" name="produto_posto">';
    echo '<option value="Gnv">Gnv</option>';
    echo '<option value="Etanol">Etanol</option>';
    echo '<option value="Gasolina">Gasolina</option>';
    echo '<option value="Gasolina Aditivada">Gasolina Aditivada</option>';
    echo '<option value="Diesel">Diesel</option>';
    echo '<option value="Diesel S10">Diesel S10</option>';
    echo '</select></br></br>';

    echo '<input type="submit" id="consulta_posto" name="consulta_posto" value="Consultar">';
    echo '</form>';
}
?>


</body>
</html>
