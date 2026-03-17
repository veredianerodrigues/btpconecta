<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — inc/parse-horario.php
 * Lê e estrutura o CSV de horários de ônibus.
 *
 * @package btpconecta
 */

/**
 * Faz o parse do arquivo CSV de horários e retorna array estruturado por grupos.
 *
 * Estrutura de retorno:
 * [
 *   [ 'nome' => 'ADM E TURNO – SEGUNDA A SEXTA', 'secoes' => [
 *       [ 'nome' => 'Saída Terminal', 'linhas' => [
 *           [ 'horario' => '5:25', 'trajeto' => '...', 'veiculo' => 'Micro-ônibus' ],
 *           ...
 *       ]],
 *       ...
 *   ]],
 *   ...
 * ]
 *
 * @param string $file_path Caminho absoluto para o arquivo CSV.
 * @return array Dados estruturados, ou array vazio em caso de erro.
 */
function btpconecta_parse_horario_csv(string $file_path): array {
    $grupos_nomes = [
        'ADM E TURNO – SEGUNDA A SEXTA',
        'ADM E TURNO – SÁBADOS E FERIADOS',
        'ADM E TURNO – DOMINGO',
    ];

    $secoes_nomes = [
        'Saída Terminal',
        'Saída Museu Pelé',
        'Saída Alfândega',
    ];

    // Abre o arquivo
    $handle = @fopen($file_path, 'r');
    if ($handle === false) {
        error_log('[BTP Horário] Não foi possível abrir o arquivo: ' . $file_path);
        return [];
    }

    // Inicializa a estrutura de grupos/seções
    $grupos = [];
    foreach ($grupos_nomes as $gi => $gn) {
        $secoes = [];
        foreach ($secoes_nomes as $sn) {
            $secoes[] = ['nome' => $sn, 'linhas' => []];
        }
        $grupos[] = ['nome' => $gn, 'secoes' => $secoes];
    }

    $section_index = -1; // índice global da seção atual (0-8)

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        // Pula linhas completamente vazias
        $filled = array_filter($row, function($v) { return trim($v) !== ''; });
        if (empty($filled)) {
            continue;
        }

        // Detecta cabeçalho de seção: col[1] == 'TRAJETO' (case-insensitive)
        $col1 = isset($row[1]) ? strtoupper(trim($row[1])) : '';
        if ($col1 === 'TRAJETO') {
            $section_index++;
            // section_index 0-2 → grupo 0, 3-5 → grupo 1, 6-8 → grupo 2
            continue; // o nome da seção já está predefinido por posição
        }

        // Linha de dado: precisa de seção ativa
        if ($section_index < 0) {
            continue;
        }

        $horario = isset($row[0]) ? trim($row[0]) : '';
        $trajeto = isset($row[1]) ? trim($row[1]) : '';
        $veiculo = isset($row[2]) ? trim($row[2]) : '';

        // Limpa quebras de linha em campos multi-linha vindos de células CSV
        $horario = preg_replace('/[\r\n]+/', ' ', $horario);
        $trajeto = preg_replace('/[\r\n]+/', ' ', $trajeto);
        $veiculo = preg_replace('/[\r\n]+/', ' ', $veiculo);

        if ($horario === '' && $trajeto === '') {
            continue;
        }

        $grupo_idx = (int) floor($section_index / 3);
        $secao_idx = $section_index % 3;

        // Garante que o índice existe (proteção contra CSVs com mais de 9 seções)
        if (!isset($grupos[$grupo_idx]['secoes'][$secao_idx])) {
            continue;
        }

        $grupos[$grupo_idx]['secoes'][$secao_idx]['linhas'][] = [
            'horario' => $horario,
            'trajeto' => $trajeto,
            'veiculo' => $veiculo,
        ];
    }

    fclose($handle);

    return $grupos;
}
