<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use Symfony\Component\Process\Process;

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
        $successCount = 0;
        $errorCount   = 0;

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
                $successCount++;
                $lineTime = round((microtime(true) - $lineStart) * 1000, 2);
                $this->line("Linha {$lineNumber} processada em {$lineTime} ms");
            } catch (\Throwable $e) {
                $lineTime = round((microtime(true) - $lineStart) * 1000, 2);
                $errorCount++;
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

        if ($successCount === 0) {
            rename($processingFile, $file . '.error');
            $this->error("Arquivo falhou completamente ({$fileTime} ms)");
        } else {
            rename($processingFile, $file . '.done');
            $this->info("Arquivo finalizado em {$fileTime} ms | OK: {$successCount} | ERRO: {$errorCount}");
        }

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

        $externalId = trim($row[0]);
        $name       = trim($row[1]);
        $priceRaw   = trim($row[2]);
        $stockRaw   = trim($row[3]);
        $activeRaw  = trim($row[4]);

        if ($name === '') { // podem ter mais validacoes de acordo com a regra de negocio
            throw new \Exception('Campos obrigatorios ausentes');
        }

        // normaliza o preco
        $price = str_replace(',', '.', $priceRaw);
        if (!is_numeric($price)) {
            throw new \Exception('Preco invalido');
        }

        // normaliza o estoque
        if (!is_numeric($stockRaw)) {
            throw new \Exception('Stock invalido');
        }
        $stock = (int) $stockRaw;

        // normaliza o status aceitando mais de uma variacao para true ou false
        $active = match (strtolower($activeRaw)) {
            '1', 'true', 'yes', 'sim' => true,
            '0', 'false', 'no', 'nao' => false,
            default => throw new \Exception('Active invalido'),
        };

        // upsert para a indempondencia
        Product::updateOrCreate(
            ['external_id' => $externalId],
            [
                'name'   => $name,
                'price'  => $price,
                'stock'  => $stock,
                'active' => $active,
            ]
        );
    }

}
