<?php
include 'dataprocessor_interface.php';
include 'config_interface.php';
include 'population_interface.php';
include 'OptimizerInterface.php';
include 'dataset/seeds/seeds.php';
include 'config/variable_ranges.php';

class Parameters
{
    public $dataProcessorSelections = [
        ['fileName' => 'dataset/agile_ziauddin.txt', 'processorType' => 'agile_ziauddin'],
        ['fileName' => 'dataset/cocomo_nasa93.txt', 'processorType' => 'cocomo_nasa93'],
        ['fileName' => 'dataset/ucp_silhavy.txt', 'processorType' => 'ucp_silhavy']
    ];

    public $optimizers = [
        'ga',  'es', 'pso'
    ];

    public $popSize = 0;
    public $stoppingValue = 0;
    public $maxIter = 0;
    public $generateType = ['random', 'seeds'];
    public $individuType = ['esRandom', 'gaRandom', 'gaSeeds'];
    public $estimators = ['ucp', 'cocomo', 'agile'];
    public $experimentType = ['random', 'seeds', 'seedsEvaluation'];
    public $pathToSaveResult = [
        'spso' => 'results/spso.txt',
        'ga' => 'results/ga.txt',
        'es' => 'results/es.txt',
        'cpso' => 'results/cpso.txt',
        'mucpso' => 'results/mucpso.txt',
        'mpso' => 'results/mpso.txt',
        'ucpso' => 'results/ucpso.txt'
    ];
    public $start = 0;
    public $end = 0;
    public $stepSize = 0;

    function getSeedsFilename()
    {
        $seedsData = new Data;
        $ret = [];
        foreach ($seedsData->getDataUCP() as $seed) {
            $ret[] = $seed;
        }
        return $ret;
    }

    function getVariableRanges()
    {
        //return (new VariableRanges())->Agile();
        //return (new VariableRanges())->CocomoSachan();
        return (new VariableRanges())->UCP();
    }
}

interface MainInterface
{
    public function runMain($parameters);
}

class RandomMain implements MainInterface
{
    function hasDataset($parameters)
    {
        return (new DataprocessorFactory())->initializeDataprocessor($parameters['processorType'], $parameters['popSize'])->processingData($parameters['fileName']);
    }

    function hasPopulation($parameters)
    {
        return (new PopulationFactory())->initializingPopulation('random', '')->createPopulation($parameters);
    }

    function hasAlgorithm($parameters)
    {
        return (new OptimizerFactory())->initializingOptimizer($parameters['optimizer']);
    }

    function getMinAEOfBestIndividu($parameters, $testData)
    {
        return $this->hasAlgorithm($parameters)->optimizing($this->hasPopulation($parameters), $parameters['estimator'], $testData, $parameters, $parameters['variableRanges']);
    }

    function runMain($parameters)
    {
        $absoluteErrors = [];
        $rawDataset = $this->hasDataset($parameters);
        $parameters['datasetSize'] = count($rawDataset);
        if (!$rawDataset) {
            return 'Dataset does not exist...';
        }
        if (!$this->hasPopulation($parameters)) {
            return 'Unable to generate population...';
        }
        if (!$this->hasAlgorithm($parameters)) {
            return 'Unable to select optimization algorithm...';
        }
        foreach ($rawDataset as $testData) {

            $absoluteErrors[] = $this->getMinAEOfBestIndividu($parameters, $testData)['minAEOfBestIndividu'];
            // $absoluteErrors[] = $this->getMinAEOfBestIndividu($parameters, $testData)['ae'];
        }
        ## TODO refaktor ke fitness function interface jika ada fungsi fitness lain selain MAE. seperti RMSE dkk
        return (new FitnessEvaluation())->calcMeanAbsoluteErrors($absoluteErrors);
    }
}

class SeedsMain implements MainInterface
{
    function __construct($seedsFileName)
    {
        $this->seedsFileName = $seedsFileName;
    }

    function hasDataset($parameters)
    {
        return (new DataprocessorFactory())->initializeDataprocessor($parameters['processorType'], $parameters['popSize'])->processingData($parameters['fileName']);
    }

    function hasRawData($parameters)
    {
        $dataProcessor = new DataprocessorFactory;
        return $dataProcessor->initializeDataprocessor($parameters['processorType'], $parameters['popSize'])->processingData($parameters['fileName']);
    }

    function hasInitialPopulation($parameters)
    {
        $populationFactory = new PopulationFactory($this->seedsFileName);
        return $populationFactory->initializingPopulation('seeds', $this->seedsFileName)->createPopulation($parameters);
    }

    function hasAlgorithm($parameters)
    {
        return (new OptimizerFactory())->initializingOptimizer($parameters['optimizer']);
    }

    function getMinAEOfBestIndividu($parameters, $testData, $initialPopulation)
    {
        return $this->hasAlgorithm($parameters)->optimizing($initialPopulation, $parameters['estimator'], $testData, $parameters, $parameters['variableRanges'])['ae'];
        // return $this->hasAlgorithm($parameters)->optimizing($initialPopulation, $parameters['estimator'], $testData, $parameters, $parameters['variableRanges'])['minAEOfBestIndividu'];
    }

    function runMain($parameters)
    {
        $rawDataset = $this->hasDataset($parameters);
        $parameters['datasetSize'] = count($rawDataset);

        if (!$this->hasRawData($parameters)) {
            return 'Rawdata tidak ada...';
        }
        if (!$this->hasInitialPopulation($parameters)) {
            return 'Initial population does not exist...';
        }
        if (!$this->hasAlgorithm($parameters)) {
            return 'Algorithm does not exist...';
        }
        foreach ($this->hasRawData($parameters) as $testData) {
            $absoluteErrors[] = $this->getMinAEOfBestIndividu($parameters, $testData, $this->hasInitialPopulation($parameters));
        }
        return (new FitnessEvaluation())->calcMeanAbsoluteErrors($absoluteErrors);
    }
}

class MainFactory
{
    public function initializingMain($mainType, $seedsFileName)
    {
        $mainTypes = [
            ['mainType' => 'random', 'select' => new RandomMain],
            ['mainType' => 'seeds', 'select' => new SeedsMain($seedsFileName)]
        ];
        $index = array_search($mainType, array_column($mainTypes, 'mainType'));
        return $mainTypes[$index]['select'];
    }
}

interface ExperimentInterface
{
    public function running($parameters);
}

class RandomExperiment implements ExperimentInterface
{
    function calcOptimizedMAE($parameters)
    {
        return (new MainFactory())->initializingMain($parameters['generateType'], '')->runMain($parameters);
    }

    function saveToFile($path, $mae, $popSize)
    {
        $data = array($mae, $popSize);
        $fp = fopen($path, 'a');
        fputcsv($fp, $data);
        fclose($fp);
        $data = [];
    }

    public function running($parameters)
    {
        for ($popSize = $parameters['start']; $popSize <= $parameters['end']; $popSize += $parameters['start']) {
            for ($i = 0; $i < 30; $i++) {
                $parameters['popSize'] = $popSize;
                if (!$this->calcOptimizedMAE($parameters)) {
                    return 'Can not optimized..';
                }
                $mae = $this->calcOptimizedMAE($parameters);
                //echo $mae . ' ' . $popSize;
                //echo "\n";
                $this->saveToFile($parameters['pathToSaveResult'], $mae, $popSize);
            }
        }
    }
}

class SeedsExperiment implements ExperimentInterface
{
    function calcOptimizedMAE($parameters, $seedsFileName)
    {
        $mainFactory = new MainFactory($seedsFileName);
        return $mainFactory->initializingMain($parameters['generateType'], $seedsFileName)->runMain($parameters);
    }

    // function saveToFile($path, $maes, $iter)
    // {
    //     $countedAllMAE = array_count_values($maes);
    //     print_r($countedAllMAE) . "\n";
    //     $maxStagnantValue = max($countedAllMAE);
    //     $indexMaxStagnantValue = array_search($maxStagnantValue, $countedAllMAE);
    //     echo $maxStagnantValue . "\n";
    //     echo "\n";
    //     echo $indexMaxStagnantValue;

    //     $data = array($iter, $maxStagnantValue, $indexMaxStagnantValue);
    //     $fp = fopen($path, 'a');
    //     fputcsv($fp, $data);
    //     fclose($fp);
    // }

    function saveToFile($path, $popSize, $mae)
    {
        $data = array($popSize, $mae);
        $fp = fopen($path, 'a');
        fputcsv($fp, $data);
        fclose($fp);
    }

    public function running($parameters)
    {
        $popSizeParameter = $parameters['popSize'];
        for ($popSize = 20; $popSize <= $popSizeParameter; $popSize += 40) {
            foreach ($parameters['fileNames'] as $seedsFileName) {
                $parameters['popSize'] = $popSize;
                $maes[] = $this->calcOptimizedMAE($parameters, $seedsFileName);
            }
            $this->saveToFile($parameters['pathToSaveResult'], $popSize, (array_sum($maes) / count($maes)));
            $maes = [];
        }

        // for ($iter = 1; $iter <= $parameters['maxIter']; $iter += $parameters['stepSize']) {
        //     foreach ($parameters['fileNames'] as $seedsFileName) {
        //         for ($popSize = $parameters['start']; $popSize <= $parameters['end']; $popSize += $parameters['start']) {
        //             $parameters['popSize'] = $popSize;
        //             $maes[] = (string)(number_format((float)$this->calcOptimizedMAE($parameters, $seedsFileName),2));
        //         }
        //     }
        //     $this->saveToFile($parameters['pathToSaveResult'], $maes, $iter);
        // }
        //$maes = [];
    }
}

class SeedsEvalExperiment implements ExperimentInterface
{
    function saveToFile($path, $data)
    {
        //$data = array($mae);
        $fp = fopen($path, 'a');
        fputcsv($fp, $data);
        fclose($fp);
    }

    function calcOptimizedMAE($parameters, $seedsFileName)
    {
        return (new MainFactory($seedsFileName))->initializingMain($parameters['generateType'], $seedsFileName)->runMain($parameters);
    }

    public function running($parameters)
    {
        $labels = array($parameters['pathToSaveResult'], $parameters['estimator'], $parameters['experimentType']);
        $this->saveToFile($parameters['pathToSaveResult'], $labels);

        foreach ($parameters['fileNames'] as $seedsFileName) {
            $startTimeInSeconds = time();
            for ($i = 0; $i < 30; $i++) {
                $maes[] = $this->calcOptimizedMAE($parameters, $seedsFileName);
                $endTimeInSeconds = time();
            }
            $this->saveToFile($parameters['pathToSaveResult'], array(min($maes), ($endTimeInSeconds- $startTimeInSeconds)) );
            $startTimeInSeconds = 0;
            $endTimeInSeconds = 0;
            $maesFileName[] = min($maes);

            echo min($maes);
            echo "\n";
            $maes = [];
        }
        $averageMAES = array_sum($maesFileName) / count($maesFileName);
        $stringAverage = array('Average: ', $averageMAES);
        $this->saveToFile($parameters['pathToSaveResult'], $stringAverage);
        $maesFileName = [];
        exit;
    }
}

class ExperimentFactory
{
    public function initializingExperiment($experimentType)
    {
        $experimentTypes = [
            ['experimentType' => 'random', 'select' => new RandomExperiment],
            ['experimentType' => 'seeds', 'select' => new SeedsExperiment],
            ['experimentType' => 'seedsEvaluation', 'select' => new SeedsEvalExperiment]
        ];
        $index = array_search($experimentType, array_column($experimentTypes, 'experimentType'));
        return $experimentTypes[$index]['select'];
    }
}

$parameters = new Parameters;
$parameters->maxIter = 20;
$parameters->stepSize = 4;
$parameters->start = 10;
$parameters->end = 100;
$parameters->stoppingValue = 0;
$parameters->popSize = 20;

$settingsParameter = [
    'processorType' => $parameters->dataProcessorSelections[2]['processorType'],
    'fileName' => $parameters->dataProcessorSelections[2]['fileName'],
    'optimizer' => $parameters->optimizers[0],
    'variableRanges' => $parameters->getVariableRanges(),
    'generateType' => $parameters->generateType[1],
    'individuType' => $parameters->individuType[2],
    'experimentType' => $parameters->experimentType[2],
    'estimator' => $parameters->estimators[0],
    'numOfVariable' => count($parameters->getVariableRanges()),
    'maxIter' => $parameters->maxIter,
    'stepSize' => $parameters->stepSize,
    'start' => $parameters->start,
    'end' => $parameters->end,
    'popSize' => $parameters->popSize,
    'stoppingValue' => $parameters->stoppingValue,
    'fileNames' => $parameters->getSeedsFilename(),
    'pathToSaveResult' => $parameters->pathToSaveResult['ga']
];
// print_r($settingsParameter);
// exit();
$experiment = (new ExperimentFactory())->initializingExperiment($settingsParameter['experimentType'])->running($settingsParameter);
