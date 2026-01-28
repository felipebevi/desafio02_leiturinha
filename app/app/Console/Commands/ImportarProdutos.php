<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportarProdutos extends Command
{
    protected $signature = 'products:import';
    protected $description = 'Importa arquivos CSV de produtos';

    private string $csvPath = '/var/www/csv';

    public function handle(): int
    {
        if (!is_dir($this->csvPath)) {
            $this->error('Diretório csv não encontrado');
            return Command::FAILURE;
        }

        $files = glob($this->csvPath . '/*.csv');

        foreach ($files as $file) {
            $this->processFile($file);
        }

        return Command::SUCCESS;
    }

    private function processFile(string $file): void
    {
        $processingFile = $file . '.processing';


        // lock do arquivo
        if (!@rename($file, $processingFile)) {
            return; // outro worker pegou
        }

        $this->info("Processando: " . basename($file) . " - se houver header ele acusa erro na linha 1 pois será ignorada");

        $fileStart = microtime(true);

        // sleep para conferir o arquivo simulando um processamento mais longo
        sleep(5);

        $handle = fopen($processingFile, 'r');
        if (!$handle) {
            rename($processingFile, $file);
            return;
        }

        $lineNumber = 0;

        while (($row = fgetcsv($handle, 0, ';', '"')) !== false) {
            $lineNumber++;
            $lineStart = microtime(true);

            try {
                $this->processRow($row);

                $lineTime = round((microtime(true) - $lineStart) * 1000, 2);
                $this->line("Linha {$lineNumber} processada em {$lineTime} ms");
            } catch (\Throwable $e) {
                $lineTime = round((microtime(true) - $lineStart) * 1000, 2);

                Log::error('Erro ao processar linha', [
                    'file' => basename($file),
                    'line' => $lineNumber,
                    'row' => $row,
                    'time_ms' => $lineTime,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Erro na linha {$lineNumber} ({$lineTime} ms)");
            }
        }

        fclose($handle);

        $fileTime = round((microtime(true) - $fileStart) * 1000, 2);
        $this->info("Arquivo finalizado em {$fileTime} ms");

        rename($processingFile, $file . '.done');
    }

    private function processRow(array $row): void
    {
        // Exemplo de colunas esperadas: external_id;name;price;stock;active
        // [0] => external_id
        // [1] => name
        // [2] => price
        // [3] => stock
        // [4] => active

        if (count($row) < 5) {
            throw new \Exception('Linha inválida');
        }

        $externalId = trim($row[0]);

        if ($externalId === '') {
            throw new \Exception('external_id vazio');
        }
            
        // se houver header - pode ser melhorado com alguma estrategia mais robusta
        $firstColumn = strtolower($externalId);
        if ($firstColumn === 'external_id') {
            throw new \Exception('linha header ignorada');
        }

        $this->info("Linha OK - external_id: {$externalId}");

        // OK - gravar no banco
    }
}
