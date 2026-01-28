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

        $this->info("Processando: " . basename($file));

        // sleep para conferir o arquivo simulando um processamento mais longo
        sleep(5);
        
        $handle = fopen($processingFile, 'r');
        if (!$handle) {
            rename($processingFile, $file);
            return;
        }

        $lineNumber = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            try {
                $this->processRow($row);
            } catch (\Throwable $e) {
                Log::error('Erro ao processar linha', [
                    'file' => basename($file),
                    'line' => $lineNumber,
                    'row' => $row,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        fclose($handle);

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

        // OK - gravar no banco
    }
}
