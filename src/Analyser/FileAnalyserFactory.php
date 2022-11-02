<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Analyser;

use PHPStan\Analyser\FileAnalyser;
use PHPStan\DependencyInjection\DerivativeContainerFactory;

/**
 * this factory creates custom file analyser for analysing files created during run
 */
final class FileAnalyserFactory
{
    private DerivativeContainerFactory $derivativeContainerFactory;

    private ?FileAnalyser $fileAnalyser = null;

    public function __construct(DerivativeContainerFactory $derivativeContainerFactory)
    {
        $this->derivativeContainerFactory = $derivativeContainerFactory;
    }

    public function create(): FileAnalyser
    {
        if ($this->fileAnalyser !== null) {
            return $this->fileAnalyser;
        }

        $container = $this->derivativeContainerFactory->create([__DIR__ . '/../../config/php-parser.neon']);
        $this->fileAnalyser = $container->getByType(FileAnalyser::class);
        return $this->fileAnalyser;
    }
}
