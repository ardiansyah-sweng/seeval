<?php

namespace algorithms\pso;

use EstimatorFactory;
use OptimizerInterface;
use FitnessEvaluation;
use Randomizers;
use Utils\ChaoticFactory;
use algorithms\pso\PopInitializationFactory;
use algorithms\pso\InertiaWeightFactory;

include "Utils/ChaoticMaps.php";
include "algorithms/pso/PopInitialization.php";
include "algorithms/pso/InertiaWeight.php";

class ParticleSwarmOptimizer implements OptimizerInterface
{
    function getVariableIndividuValue($individu)
    {
        foreach ($individu as $val) {
            $ret[] = $val['variableValue'];
        }
        return $ret;
    }

    function findBestIndividu($estimatedEfforts, $testData, $stoppingValue)
    {
        $fitnessEvaluation = new FitnessEvaluation;
        return $fitnessEvaluation->evaluatingFitness($estimatedEfforts, $testData, $stoppingValue);
    }

    function hasEstimatedEfforts($population, $estimator, $testData)
    {
        foreach ($population as $individu) {
            $variableIndividuValues = $this->getVariableIndividuValue($individu);
            $estimatedEfforts[] = (new EstimatorFactory())->initializingEstimator($estimator)->estimating($variableIndividuValues, $testData);
        }
        return $estimatedEfforts;
    }

    function isFound($bestIndividus, $stoppingValue)
    {
        $fitnessEvaluation = new FitnessEvaluation;
        return $fitnessEvaluation->solutionIsFound($bestIndividus, $stoppingValue);
    }

    function velocity($w, $r1, $r2, $Pbests, $Gbest, $particles, $numOfVariable)
    {
        for ($i = 0; $i < $numOfVariable; $i++) {
            if ((new ParameterSet())->getPSOParameter()['gbest'] === 'gbest') {
                $velocities[] = ($w * $particles['velocities'][$i]) +
                    (
                        ((new ParameterSet())->getPSOParameter()['c1'] * $r1) *
                        (floatval($Pbests[$i]) - floatval($particles['variable'][$i]))) +
                    (
                        ((new ParameterSet())->getPSOParameter()['c2'] * $r2) *
                        (floatval($Gbest[$i]) - floatval($particles['variable'][$i])));
            }

            if ((new ParameterSet())->getPSOParameter()['gbest'] === 'mbest') {
                $velocities[] = ($w * $particles['velocities'][$i]) +
                    (
                        ((new ParameterSet())->getPSOParameter()['c1'] * $r1) * ($Pbests[$i] - $particles['variable'][$i])) +
                    (((new ParameterSet())->getPSOParameter()['c2'] * $r2) * ($Gbest[$i] - $particles['variable'][$i]));
            }
        }
        return $velocities;
    }

    function findBestIndividus($population)
    {
        $min = min(array_column($population, 'ae'));
        $index = array_search($min, array_column($population, 'ae'));
        return $population[$index];
    }

    function findWorstIndividus($population)
    {
        $min = max(array_column($population, 'ae'));
        $index = array_search($min, array_column($population, 'ae'));
        return $population[$index];
    }

    function calcNewVariable($velocities, $variables, $numOfVariable, $variableRanges)
    {
        for ($i = 0; $i < $numOfVariable; $i++) {
            $variable = $velocities[$i] + floatval($variables[$i]);
            if ($variable > $variableRanges[$i]['upperBound']) {
                $variables[$i] = $variableRanges[$i]['upperBound'];
            }
            if ($variable < $variableRanges[$i]['lowerBound']) {
                $variables[$i] = $variableRanges[$i]['lowerBound'];
            }
        }
        return $variables;
    }

    function checkMaxVelocity($velocities, $variableRanges)
    {
        foreach ($velocities as $key => $velocity) {
            $vMax = $variableRanges[$key]['upperBound'] - ($variableRanges[$key]['lowerBound']);
            if ($velocity > $vMax) {
                $velocity = $vMax;
            }
            $velocities[$key] = $velocity;
        }
        return $velocities;
    }

    function getIndexOfMinBestIndividu($bests)
    {
        $globalBest = min(array_column($bests, 'ae'));
        return $bests[array_search($globalBest, array_column($bests, 'ae'))];
    }

    function createInitialVelocities($variables, $initialVelocity)
    {
        foreach ($variables as $variable) {
            $velocities[] = floatval($variable) * $initialVelocity;
        }
        return $velocities;
    }

    function updatePbests($particles, $Pbests)
    {
        if ($particles['ae'] < $Pbests['ae']) {
            return $particles;
        } else {
            return $Pbests;
        }
    }

    function reRandomize($cpbestIndex, $pbests)
    {
        $counter = 0;
        while ($counter < (new ParameterSet())->getPSOParameter()['maxCounter']) {
            if ($cpbestIndex[0] == $cpbestIndex[1]) {
                $cpbestIndex = $this->getCpbestIndex($pbests);
                $counter = 0;
            } else {
                return $cpbestIndex;
            }
        }
    }

    function getCpbestIndex($pbests)
    {
        $numOfCPbest = 2;
        for ($i = 0; $i < $numOfCPbest; $i++) {
            $cpbestIndex[] = array_rand($pbests);
        }
        return $cpbestIndex;
    }

    function getNewSpbest($pbests, $cpbestIndex)
    {
        if ($pbests[$cpbestIndex[0]]['ae'] < $pbests[$cpbestIndex[1]]['ae']) {
            $cpbest = $pbests[$cpbestIndex[0]];
        }
        if ($pbests[$cpbestIndex[0]]['ae'] > $pbests[$cpbestIndex[1]]['ae']) {
            $cpbest =  $pbests[$cpbestIndex[1]];
        }
        if ($pbests[$cpbestIndex[0]]['ae'] == $pbests[$cpbestIndex[1]]['ae']) {
            $cpbest =  $pbests[$cpbestIndex[0]];
        }
        foreach ($pbests as $key => $pbest) {
            if ($pbest['ae'] > $cpbest['ae']) {
                $pbests[$key] = $cpbest;
            }
        }
        return $pbests;
    }

    function getSPbest($pbests)
    {
        $cpbestIndex = $this->reRandomize($this->getCpbestIndex($pbests), $pbests);
        return $this->getNewSpbest($pbests, $cpbestIndex);
    }

    function getMbest($parameters, $mbests)
    {
        foreach ($mbests as $mbest) {
            foreach ($mbest as $variable) {
                $variables[] = $variable['variable'];
            }
        }
        $temp = 0;
        for ($i = 0; $i < $parameters['numOfVariable']; $i++) {
            foreach ($variables as $variable) {
                foreach ($variable as $key => $var) {
                    if ($i === $key) {
                        $temp = $temp + $var;
                    }
                }
            }
            $sum[$i] = $temp;
            $temp = 0;
        }

        foreach ($sum as $val) {
            $ret[] = $val / $parameters['datasetSize'];
        }
        return $ret;
    }

    function Nbest($Gbests, $Pbests)
    {
        for ($i = 0; $i < 2; $i++) {
            $randomPbestIndexes[$i] = array_rand($Pbests);
        }

        if ($randomPbestIndexes[0] == $randomPbestIndexes[1]) {
            $randomPbestIndexes = $this->reRandomize($randomPbestIndexes, $Pbests);
        }
        $pbest1 = $Pbests[$randomPbestIndexes[0]];
        $pbest2 = $Pbests[$randomPbestIndexes[1]];
        return $Gbests['ae'] + ($pbest1['ae'] - $pbest2['ae']);
    }

    function adaptivePositionUpdating($particles, $particle, $datasetSize, $chaoticValue, $velocities, $Gbest)
    {
        $mean = array_sum(array_column($particles, 'ae')) / $datasetSize;
        $p = exp($particle['ae']) / $mean;
        if ($p > (new Randomizers())->randomZeroToOneFraction()) {
            foreach ($particle['variable'] as $key => $variable) {
                $variables[] = $chaoticValue * $variable + (1 - $chaoticValue) * $velocities[$key] + $Gbest[$key];
            }
            return $variables;
        }
    }

    function aConstant($I, $maxIter)
    {
        if (($I <= $maxIter) / 6) {
            return 4 / 3;
        }
        if (($maxIter / 6) < $I && $I <= (5 * $maxIter) / 6) {
            return 16 / 3;
        }
        if ((5 * $maxIter) / 6 < $I && $I <= $maxIter) {
            return 2 / 9;
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

    public function optimizing($population, $estimator, $testData, $parameters, $variableRanges)
    {
        $parameterSet = new ParameterSet;
        $chaos = new ChaoticFactory;
        $analitics = [];

        for ($iter = 0; $iter <= $parameters['maxIter']; $iter++) {
            //Modified randomize parameters r1 & r2
            if ($iter === 0) {
                $I[$iter] = 0;
                if ($parameterSet->getPSOParameter()['inertia'] === 'rankBased') {
                    $chaoticValue[$iter] = $chaos->initializeChaotic($parameterSet->getPSOParameter()['chaoticMap'], $iter, $I[$iter])->chaotic($parameters['maxIter']);
                } else {
                    $chaoticValue[$iter] = 0.7;
                }
            }
            if ($iter > 0) {
                $I[$iter] = $I[$iter - 1] + $this->aConstant($I[$iter - 1], $parameters['maxIter']);

                $chaoticValue[$iter] = $chaos->initializeChaotic($parameterSet->getPSOParameter()['chaoticMap'], $iter, $I[$iter - 1])->chaotic($chaoticValue[$iter - 1]);
            }

            $r1[$iter] = $chaos->initializeChaotic($parameterSet->getPSOParameter()['r1'], $iter, $I[$iter])->chaotic($chaoticValue[$iter]);
            $r2[$iter] = $chaos->initializeChaotic($parameterSet->getPSOParameter()['r2'], $iter, $I[$iter])->chaotic($chaoticValue[$iter]);
            $initialVelocity = (new Randomizers())->randomZeroToOneFraction();

            for ($i = 0; $i < $parameters['popSize']; $i++) {
                if ($iter === 0) {
                    //$chaoticValue[$iter] = $chaos->initializeChaotic($parameterSet->getPSOParameter()['chaoticMap'], $iter, $I[$iter])->chaotic($chaoticValue[$iter]);

                    //generate initial population
                    $variables = (new PopInitializationFactory())->initializePopulation($population[$i], $parameters, $parameterSet->getPSOParameter()['initialPop'], $parameters['generateType'])->initializingPopulation();
                    $velocities = $this->createInitialVelocities($variables, $initialVelocity);
                }
                if ($iter > 0) {
                    //updating population
                    //$chaoticValue[$iter] = $chaotic->chaotic($chaoticValue[$iter - 1]);


                    //$chaoticValue[$iter] = $chaos->initializeChaotic($parameterSet->getPSOParameter()['chaoticMap'], $iter, $I[$iter])->chaotic($chaoticValue[$iter - 1]);

                    // $w = $this->inertia($parameters, $iter, $chaoticValue[$iter]);
                    if ($parameterSet->getPSOParameter()['gbest'] === 'gbest' && $parameters['generateType'] === 'random') {
                        $GBests = $GBest[$iter - 1]['variable'];
                    }
                    if ($parameterSet->getPSOParameter()['gbest'] === 'gbest' && $parameters['generateType'] === 'seeds') {
                        $GBests = $GBest[$iter - 1]['variable'];
                    }
                    if ($parameterSet->getPSOParameter()['gbest'] === 'mbest') {
                        $GBests = $GBest[$iter - 1];
                    }

                    $w = (new InertiaWeightFactory())->initializePopulation($chaoticValue[$iter], $parameterSet->getPSOParameter()['inertia'], $particles[$iter - 1], $particles[$iter - 1][$i], $parameters['popSize'])->inertiaWeighting((new ParameterSet()),
                        $iter,
                        $parameters['maxIter']
                    );

                    $velocities = $this->velocity($w, $r1[$iter], $r2[$iter], $Pbests[$iter - 1][$i]['variable'], $GBests, $particles[$iter - 1][$i], $parameters['numOfVariable']);
                    $velocities = $this->checkMaxVelocity($velocities, $parameters['variableRanges']);
                    $variables = $this->calcNewVariable($velocities, $particles[$iter - 1][$i]['variable'], $parameters['numOfVariable'], $parameters['variableRanges']);

                    if ($parameterSet->getPSOParameter()['positionAdaptation'] === 'adaptive') {
                        $adaptivePositionAdaptation = $this->adaptivePositionUpdating($particles[$iter - 1], $particles[$iter - 1][$i], $parameters['datasetSize'], $chaoticValue[$iter], $velocities, $GBests);
                        if ($adaptivePositionAdaptation) {
                            $variables = $adaptivePositionAdaptation;
                        }
                    }
                }

                $estimatedEffort = (new EstimatorFactory())->initializingEstimator($estimator)->estimating($variables, $testData);
                $ae = abs($estimatedEffort - floatval($testData['actualEffort']));

                $particles[$iter][$i] = [
                    'estimatedEffort' => $estimatedEffort,
                    'ae' => $ae,
                    'variable' => $variables,
                    'velocities' => $velocities
                ];

                if ($iter === 0) {
                    $Pbests[$iter][$i] = $particles[$iter][$i];
                } else {
                    $Pbests[$iter][$i] = $this->updatePbests($particles[$iter - 1][$i], $Pbests[$iter - 1][$i]);
                }
            }

            $GBest[$iter] = $this->findBestIndividus($Pbests[$iter]);
            ## TODO refactoring into interface
            if ($parameterSet->getPSOParameter()['pbest'] === 'nbest') {
                $Pbests[$iter] = $this->getSPbest($Pbests[$iter]);
                $mbests[] = $particles[$iter];
                $Mbests[$iter] = $this->getMbest($parameters, $mbests);
                $GWorsts[$iter] = $this->findWorstIndividus($Pbests[$iter]);
                $Nbest[$iter] = $this->Nbest($GBest[$iter], $Pbests[$iter]);

                if ($Nbest[$iter] < $GWorsts[$iter]['ae']) {
                    $Gworst = $Nbest[$iter];
                } else {
                    $Gworst = $GWorsts[$iter]['ae'];
                }

                if ($Gworst < $parameters['stoppingValue']) {
                    return $GWorsts[$iter];
                }
                $GBest[$iter] = $Mbests[$iter];

                $bests[] = $GWorsts[$iter];
            }

            if ($parameterSet->getPSOParameter()['pbest'] === 'spbest') {
                $Pbests[$iter] = $this->getSPbest($Pbests[$iter]);
                if ($GBest[$iter]['ae'] < $parameters['stoppingValue']) {
                    return $GBest[$iter];
                }
                $bests[] = $GBest[$iter];
            }

            if ($parameterSet->getPSOParameter()['pbest'] === 'pbest') {
                if ($GBest[$iter]['ae'] < $parameters['stoppingValue']) {
                    return $GBest[$iter];
                }
                $bests[] = $GBest[$iter];
            }

            $analitics[] = $GBest[$iter]['ae'];
            if ($this->analytics($iter, $analitics)) {
                break;
            }
        }
        return $this->getIndexOfMinBestIndividu($bests);
    }
}

class ParameterSet
{
    function getPSOParameter()
    {
        //$chaotics =  array('bernoulli', 'chebyshev', 'circle', 'gauss', 'logistic', 'sine', 'singer', 'sinu', 'cosine', spso);
        //foreach ($chaotics as $chaotic) {
        $r1 = 'spso';
        $r2 = 'spso';
        $inertia = 'rankBased'; //ldw, rankBased, chaotic
        $chaoticMap = 'cosine';
        $initialPop = 'uniform'; //spso, uniform
        $pbest = 'spbest'; //spbest, pbest, nbest
        $gbest = 'gbest'; //gbest, mbest
        $positionAdaptation = 'spso'; //spso, adaptive
        return [
            'inertiaMax' => 0.9,
            'inertiaMin' => 0.4,
            'c1'         => 2,
            'c2'         => 2,
            'r1'         => $r1,
            'r2'         => $r2,
            'initialPop' => $initialPop,
            'pbest'      => $pbest,
            'inertia'    => $inertia,
            'gbest'      => $gbest,
            'chaoticMap' => $chaoticMap,
            'positionAdaptation' => $positionAdaptation,
            'maxCounter' => 1000
        ];
        //}
    }
}
