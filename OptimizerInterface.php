<?php
include 'estimator_interface.php';
require_once 'individu_interface.php';
//include 'algorithms/es.php';
include 'algorithms/ga/crossover_interface.php';
require_once 'algorithms/ga/mutation.php';
require_once 'algorithms/ga/selection_interface.php';
require_once 'algorithms/pso/ParticleSwarmOptimizer.php';

use algorithms\pso\ParticleSwarmOptimizer;

interface OptimizerInterface
{
    public function optimizing($population, $estimator, $data, $parameters, $variableRanges);
}

class FitnessEvaluation
{
    function solutionIsFound($bestIndividus, $stoppingValue)
    {
        if ($bestIndividus <= $stoppingValue) {
            return TRUE;
        }
    }

    function bestIndividu($absoluteErrors)
    {
        $minAE = min($absoluteErrors);
        $indexMinAE = array_search($minAE, $absoluteErrors);
        return [
            'indexOfBestIndividu' => $indexMinAE,
            'ae' => $absoluteErrors[$indexMinAE]
        ];
    }

    function evaluatingFitness($fitnessValues, $testData)
    {
        foreach ($fitnessValues as $fitnessValue) {
            $absoluteErrors[] = abs($fitnessValue - floatval($testData['actualEffort']));
        }
        return $this->bestIndividu($absoluteErrors);
    }
    ## TODO Refaktor duplicate code
    function getAbsoluteErrors($fitnessValues, $testData)
    {
        foreach ($fitnessValues as $fitnessValue) {
            $absoluteErrors[] = abs($fitnessValue - floatval($testData['actualEffort']));
        }
        return $absoluteErrors;
    }

    function calcMeanAbsoluteErrors($absoluteErrors)
    {
        return array_sum($absoluteErrors) / count($absoluteErrors);
    }
}

/**
 * Genetic Algorithms
 */
class GA implements OptimizerInterface
{
    function __construct($crossoverType, $crossoverRate)
    {
        $this->crossoverType = $crossoverType;
        $this->crossoverRate = $crossoverRate;
    }

    function getVariableIndividuValue($individu)
    {
        foreach ($individu as $val) {
            $ret[] = $val['variableValue'];
        }
        return $ret;
    }

    function addVariableValueLabel($newPopulations)
    {
        $ret = [];
        foreach ($newPopulations as $newPopulation) {
            foreach ($newPopulation as $val) {
                $labels[]['variableValue'] = $val;
            }
            $ret[] = $labels;
            $labels = [];
        }
        return $ret;
    }

    function getIndexOfMinBestIndividu($bests)
    {
        $globalBest = min(array_column($bests, 'ae'));
        return $bests[array_search($globalBest, array_column($bests, 'indexOfBestIndividu'))];
    }

    function hasEstimatedEfforts($population, $estimator, $testData)
    {
        $estimatorFactory = new EstimatorFactory;
        foreach ($population as $individu) {
            $variableIndividuValues = $this->getVariableIndividuValue($individu);
            $estimatedEfforts[] = $estimatorFactory->initializingEstimator($estimator)->estimating($variableIndividuValues, $testData);
        }
        return $estimatedEfforts;
    }

    function findBestIndividu($estimatedEfforts, $testData, $stoppingValue)
    {
        $fitnessEvaluation = new FitnessEvaluation;
        return $fitnessEvaluation->evaluatingFitness($estimatedEfforts, $testData, $stoppingValue);
    }

    function isFound($bestIndividus, $stoppingValue)
    {
        $fitnessEvaluation = new FitnessEvaluation;
        return $fitnessEvaluation->solutionIsFound($bestIndividus, $stoppingValue);
    }

    function hasCrossover()
    {
        $crossoverFactory = new CrossoverFactory;
        return $crossoverFactory->initializingCrossover($this->crossoverType);
    }

    function labelingCrossoverOffsprings($population, $lengthOfGen)
    {
        $crossoverOffsprings = $this->hasCrossover()->crossover($population, $this->crossoverRate, $lengthOfGen);
        return $this->addVariableValueLabel($crossoverOffsprings);
    }

    function mutation($population, $lengthOfGen, $variableRanges, $labeledCrossoverOffsprings)
    {
        $mutation = new Mutation($population, $lengthOfGen, $variableRanges);
        foreach ($mutation->mutation() as $mutationOffspring) {
            $labeledCrossoverOffsprings[] = $mutationOffspring;
        }
        return $labeledCrossoverOffsprings;
    }

    function createNewBestPopulation($selectionType, $combinedOffsprings, $estimator, $testData, $popSize, $population)
    {
        return (new SelectionFactory())->initializingSelection($selectionType)->selectingIndividus($combinedOffsprings, $estimator, $testData, $popSize, $population, $selectionType);
    }

    function blah($errorIndex)
    {
        if (!$errorIndex['estimatedEfforts']) {
            return 'Estimated efforts do not exist...';
        }
        if (!$errorIndex['bestIndividus']) {
            return 'Can not find best individu...';
        }
        if (!$errorIndex['crossover']) {
            return 'Crossover type does not exist...';
        }
        if (!$errorIndex['labeling']) {
            return 'Can not labeling crossover offsprings';
        }
    }

    function analytics($iter, $analitics)
    {
        $numOfLastResults = 10;
        if ($iter >= ($numOfLastResults - 1)) {
            $residual = count($analitics) - $numOfLastResults;
            
            if ($residual === 0 && count(array_unique($analitics)) === 1) {
                return true;
            }

            if ($residual > 0) {
                for ($i = 0; $i < $residual; $i++) {
                    array_shift($analitics);
                }
                if (count(array_unique($analitics)) === 1) {
                    return true;
                }
            }
        }
    }

    function optimizing($population, $estimator, $testData, $parameters, $variableRanges)
    {
        ## TODO pastikan ada class khusus untuk menyediakan panjang individu dll
        $bests = [];
        $estimatedEfforts = $this->hasEstimatedEfforts($population, $estimator, $testData);
        $bestIndividus = $this->findBestIndividu($estimatedEfforts, $testData, $parameters['stoppingValue']);
        $bestIndividuIsFound = $this->isFound($bestIndividus, $parameters['stoppingValue']);
        $errorIndex = [
            'estimatedEfforts' => $estimatedEfforts,
            'bestIndividus' => $bestIndividus,
            'crossover' => $this->hasCrossover(),
            'labeling' => $this->labelingCrossoverOffsprings($population, count($variableRanges))
        ];
        echo $this->blah($errorIndex);
        $bests[] = $bestIndividus;
        $iter = 0;
        $combinedOffsprings = [];

        ## TODO refaktor agar terintegrasi ke setting parameters
        $selectionTypes = ['elitism', 'binary', 'roulette'];

        while ($bestIndividuIsFound === FALSE || $iter <= $parameters['maxIter'] - 1) {
            if ($bestIndividuIsFound) {
                return $bestIndividus;
                break;
            }
            $labeledCrossoverOffsprings = $this->labelingCrossoverOffsprings($population, count($variableRanges));
            $mutation = $this->mutation($population, count($variableRanges), $variableRanges, $labeledCrossoverOffsprings);
            echo $this->blah($errorIndex);
            $combinedOffsprings = $mutation;
            $population = [];
            $estimatedEfforts = [];
            $population = $this->createNewBestPopulation($selectionTypes[0], $combinedOffsprings, $estimator, $testData, $parameters['popSize'], $population);

            $bestIndividuIsFound = $this->isFound($this->findBestIndividu($this->hasEstimatedEfforts($population, $estimator, $testData), $testData, $parameters['stoppingValue']), $parameters['stoppingValue']);

            $bests[] = $bestIndividus;
            $analitics[] = $bestIndividus['ae'];
            if ($this->analytics($iter, $analitics)) {
                break;
            }
            $iter++;
        }
        $combinedOffsprings = [];
        $population = [];
        $analitics = [];
        return $this->getIndexOfMinBestIndividu($bests);
    }
}

class ES implements OptimizerInterface
{
    function __construct($alpha, $percentage)
    {
        $this->alpha = $alpha;
        $this->percentage = $percentage;
    }

    function getVariableValues($individu)
    {
        foreach ($individu as $value) {
            $ret[] = $value['variableValue'];
        }
        return $ret;
    }

    function estimatingEfforts($population, $estimator, $testData)
    {
        $estimation = (new EstimatorFactory())->initializingEstimator($estimator);
        foreach ($population as $individu) {
            $estimatedEfforts[] =  [
                'estimatedEffort' => $estimation->estimating($this->getVariableIndividuValue($individu), $testData),
                'variableValue' => $this->getVariableValues($individu)
            ];
        }
        return $estimatedEfforts;
    }

    function getVariableIndividuValue($individu)
    {
        foreach ($individu as $val) {
            $ret[] = $val['variableValue'];
        }
        return $ret;
    }

    function findBestIndividus($population)
    {
        $min = min(array_column($population, 'ae'));
        $index = array_search($min, array_column($population, 'ae'));
        return $population[$index];
    }

    function getEstimatedValueOnly($estimatedEfforts)
    {
        foreach ($estimatedEfforts as $estimatedEffort) {
            $ret[] = $estimatedEffort['estimatedEffort'];
        }
        return $ret;
    }

    function offsprings($population, $variableRanges)
    {
        foreach ($population as $individu) {
            for ($i = 0; $i < $this->alpha; $i++) {
                $x = $this->offspringVariable($individu);
                $offsprings[][] = [
                    'variableValue' => $this->exceedLimits($x, $variableRanges)
                    // 'sigmaValue' => $individu[0]['sigmaValue']
                ];
            }
        }
        return $offsprings;
    }

    function offspringVariable($individu)
    {
        for ($i = 0; $i < count($individu['variableValue']); $i++) {
            $r1 = Randomizers::randomZeroToOneFraction();
            $r2 = Randomizers::randomZeroToOneFraction();
            $ret[] = floatval($individu['variableValue'][$i]) + $individu['sigmaValue'][$i] * $this->schwefel($r1, $r2);
        }
        return $ret;
    }

    function schwefel($r1, $r2)
    {
        return sqrt(-2 * log($r1) * sin(2) * pi() * $r2);
    }

    function exceedLimits($offSprings, $variableRanges)
    {
        if (!is_array($offSprings)) {
            if ($offSprings < $variableRanges[0]['lowerBound']) {
                return $variableRanges[0]['lowerBound'];
            }
            if ($offSprings < $variableRanges[1]['lowerBound']) {
                return $variableRanges[1]['lowerBound'];
            }
            if ($offSprings > $variableRanges[0]['upperBound']) {
                return $variableRanges[0]['upperBound'];
            }
            if ($offSprings > $variableRanges[1]['upperBound']) {
                return $variableRanges[1]['upperBound'];
            }
        } else {
            foreach ($offSprings as $key => $offSpring) {
                if ($offSpring < $variableRanges[$key]['lowerBound']) {
                    $offSprings[$key] = $variableRanges[$key]['lowerBound'];
                }
                if ($offSpring > $variableRanges[$key]['upperBound']) {
                    $offSprings[$key] = $variableRanges[$key]['upperBound'];
                }
            }
        }
        return $offSprings;
    }

    function calcGoodOffspringsPercentage($counts)
    {
        if (count($counts) === 1) {
            foreach (array_keys($counts) as $key) {
                if ($key === 0) {
                    return 0;   // seluruh offspring lebih baik dari offspring
                }
                if ($key === 1) {
                    return 1;   // seluruh populasi lebih baik dari populasi
                }
            }
        }
        if ($counts[0] >= round($this->percentage * $this->alpha)) {
            return 2;   // sebagian besar offspring lebih baik dari populasi
        }
    }

    function moveElement(&$array, $a, $b)
    {
        $out = array_splice($array, $a, 1);
        return array_splice($array, $b, 0, $out);
    }

    function moveAEToFirst($offsprings)
    {
        foreach ($offsprings as $key => $ae) {
            $this->moveElement($offsprings[$key], 2, 0);
        }
        return $offsprings;
    }

    function getSigma($individu)
    {
        foreach ($individu as $sigma) {
            $ret[] = $sigma['sigmaValue'];
        }
        return $ret;
    }

    function addSigmaValueToPopulation($population, $estimatedPopulation, $generateType)
    {
        if ($generateType === 'random') {
            foreach ($population as $key => $individu) {
                $estimatedPopulation[$key]['sigmaValue'] =
                    $this->getSigma($individu);
            }
        }

        if ($generateType === 'seeds') {
            foreach ($population as $key => $individu) {
                $estimatedPopulation[$key]['sigmaValue'] =
                    Randomizers::randomZeroToOneFraction();
            }
        }
        return $estimatedPopulation;
    }

    function addAEToPopulation($absoluteErrors, $population)
    {
        foreach ($absoluteErrors as $key => $ae) {
            $population[$key]['ae'] = $ae;
        }
        return $population;
    }

    function calcSigma($sigma, $addOrReduce)
    {
        if ($addOrReduce === 'add') {
            foreach ($sigma as $value) {
                $ret[] = $value * 1.1;
            }
        }
        if ($addOrReduce === 'reduce') {
            foreach ($sigma as $value) {
                $ret[] = $value * 0.9;
            }
        }
        return $ret;
    }

    function mutation($population, $chunkedOffsprings)
    {
        $counter = 0;
        foreach ($chunkedOffsprings as $key => $offsprings) {
            foreach ($offsprings as $ae) {
                if ($ae['ae'] < $population[$key]['ae']) {
                    $counter = $counter + 1;
                }
            }
            if (($counter / $this->alpha) > $this->percentage) {
                foreach (array_keys($offsprings) as $subkey) {
                    $offsprings[$subkey]['sigmaValue'] =
                        $this->calcSigma($population[$key]['sigmaValue'], 'add');
                }
            } else {
                foreach (array_keys($offsprings) as $subkey) {
                    $offsprings[$subkey]['sigmaValue'] =
                        $this->calcSigma($population[$key]['sigmaValue'], 'reduce');
                }
            }
            $chunkedOffsprings[$key] = $offsprings;
            $counter = 0;
        }
        //kembalikan chunked offsprings menjadi normal
        foreach ($chunkedOffsprings as $chunkeds) {
            foreach ($chunkeds as $offspring) {
                $ret[][] = $offspring;
            }
        }
        return $ret;
    }

    function getIndexOfMinBestIndividu($bests)
    {
        $globalBest = min(array_column($bests, 'ae'));
        return $bests[array_search($globalBest, array_column($bests, 'ae'))];
    }

    function optimizing($population, $estimator, $testData, $parameters, $variableRanges)
    {
        $fitnessEvaluation = new FitnessEvaluation;
        $estimatedPopulation = $this->estimatingEfforts($population, $estimator, $testData);
        $population = $this->addSigmaValueToPopulation($population, $estimatedPopulation, $parameters['generateType']);

        $aePopulation = $fitnessEvaluation->getAbsoluteErrors($this->getEstimatedValueOnly($estimatedPopulation), $testData);

        #TODO refaktor ke fungsi tersendiri
        foreach ($aePopulation as $key => $ae) {
            $population[$key]['ae'] = $ae;
        }

        $bestIndividus =
            $this->findBestIndividus($population);

        $bestIndividuIsFound = $fitnessEvaluation->solutionIsFound($bestIndividus['ae'], $parameters['stoppingValue']);

        $iter = 0;
        while ($bestIndividuIsFound === FALSE || $iter < $parameters['maxIter']) {
            //1. buat variabel offspring sebanyak alpha tiap individu
            $rawOffsprings = $this->offsprings($population, $variableRanges);

            //2. hitung estimated offspring
            $estimatedOffsprings = $this->estimatingEfforts($rawOffsprings, $estimator, $testData);

            $aeOffsprings = $fitnessEvaluation->getAbsoluteErrors($this->getEstimatedValueOnly($estimatedOffsprings), $testData);
            $rawOffspringsPopulation = $this->addAEToPopulation($aeOffsprings, $estimatedOffsprings);

            //3. compare ae population vs ae offsprings
            $mutationOffsprings = $this->mutation($population, array_chunk($rawOffspringsPopulation, $this->alpha));

            //3. seleksi elitism
            foreach ($mutationOffsprings as $key => $pop) {
                $mutationOffsprings[$key] = $pop[0];
            }
            $mutationOffspringToSelected = $this->moveAEToFirst($mutationOffsprings);

            $population = (new SelectionFactory())->initializingSelection('esElitism')->selectingIndividus($population, $estimator, $testData, $parameters['popSize'], $population, $mutationOffspringToSelected);

            foreach ($population as $key => $pop) {
                $population[$key]['ae'] = $population[$key][0];
                unset($population[$key][0]);
            }

            $bestIndividus =
                $this->findBestIndividus($population);

            $bestIndividuIsFound = $fitnessEvaluation->solutionIsFound($bestIndividus['ae'], $parameters['stoppingValue']);

            if ($bestIndividuIsFound) {
                return $bestIndividus;
            }

            $bests[] = $bestIndividus;
            $iter++;
        }
        return $this->getIndexOfMinBestIndividu($bests);
    }
}

class OptimizerFactory
{
    public function initializingOptimizer($algorithm)
    {
        // echo $algorithm;exit;
        $crossoverType = 'oneCutPoint';
        $crossoverRate = 0.8;
        $alpha = 3;
        $percentage = 0.2;
        $algorithms = [
            ['algorithm' => 'ga', 'select' => new GA($crossoverType, $crossoverRate)],
            ['algorithm' => 'es', 'select' => new ES($alpha, $percentage)],
            ['algorithm' => 'pso', 'select' => new ParticleSwarmOptimizer]
        ];
        $index = array_search($algorithm, array_column($algorithms, 'algorithm'));
        // if (!$index) {
        //     die('Algoritma ' . $algorithm . ' tidak ada');
        // }
        return $algorithms[$index]['select'];
    }
}
