<?php

namespace Claserre9\CodeCoverageManager\utils;

use Exception;
use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\PHP as PhpReport;
use SebastianBergmann\FileIterator\Facade as FileIteratorFacade;

/**
 * Manages code coverage collection and reporting.
 */
class CodeCoverageManager
{
    private $coverage;
    private $filter;
    private $reportPath;

    /**
     * Constructs a new instance of the class.
     *
     * @param array $paths An array of file paths to be included in the filter.
     * @param string|null $reportDir The directory where the coverage report will be stored. Defaults to a temporary directory if not provided.
     *
     * @return void
     */
    public function __construct(array $paths = [], string $reportDir = null)
    {
        $this->filter       = new Filter();
        $fileIteratorFacade = new FileIteratorFacade();

        foreach ($paths as $path) {
            $this->filter->includeFiles($fileIteratorFacade->getFilesAsArray($path));
        }

        $this->coverage = new CodeCoverage(
            (new Selector())->forLineCoverage($this->filter),
            $this->filter
        );

        $this->coverage->start($_SERVER['REQUEST_URI']);
        $this->reportPath = $reportDir ?? __DIR__.'/temp/crawler/';

        if (!is_dir($this->reportPath)) {
            if (!mkdir($concurrentDirectory = $this->reportPath, 0777, true) && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        register_shutdown_function([$this, 'saveCoverage']);
    }

    public function saveCoverage(): void
    {
        $this->stopCoverageAndSave($this->coverage);
    }

    private function stopCoverageAndSave(CodeCoverage $coverage): void
    {
        try {
            $coverage->stop();
            $filePath  = $this->reportPath.bin2hex(random_bytes(16)).'.cov';
            $phpReport = new PhpReport();
            $phpReport->process($coverage, $filePath);
        } catch (Exception $e) {
            error_log('Code coverage save failed: '.$e->getMessage());
        }
    }
}

// Instantiate the CodeCoverageManager
new CodeCoverageManager();