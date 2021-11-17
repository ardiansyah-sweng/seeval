<?php
include 'randomizer.php';

/**
 * Evolutionary Strategies
 */
class ES implements OptimizerInterface
{
    function __construct($alpha, $percentage)
    {
        $this->alpha = $alpha;
        $this->percentage = $percentage;
    }

    function getVariableIndividuValue($individu)
    {
        foreach ($individu as $val) {
            $ret[] = $val['variableValue'];
        }
        return $ret;
    }

    function getSigmaIndividuValue($individu)
    {
        foreach ($individu as $val) {
            $ret[] = $val['sigmaValue'];
        }
        return $ret;
    }

    function schwefel($r1, $r2)
    {
        return sqrt(-2 * log($r1) * sin(2) * pi() * $r2);
    }

    function offspringVariable($individu)
    {
        $r1 = Randomizers::randomZeroToOneFraction();
        $r2 = Randomizers::randomZeroToOneFraction();
        return $individu['variableValue'] + $individu['sigmaValue'] * $this->schwefel($r1, $r2);
    }

    function exceedLimits($offSprings, $variableRanges)
    {
        if (!is_array($offSprings)) {
            if ($offSprings < $variableRanges[0]['lowerBound']) {
                return $variableRanges[0]['lowerBound'];
            }
            if ($offSprings > $variableRanges[0]['upperBound']) {
                return $variableRanges[0]['upperBound'];
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

    function offsprings($population, $variableRanges)
    {
        //print_r($population);
        foreach ($population as $key => $individu) {
            for ($i = 0; $i < $this->alpha; $i++) {
                //echo $key . ' ';
                $x = $this->offspringVariable($individu[0]);
                $x = $this->exceedLimits($x, $variableRanges);
                //echo ($x);
                //echo "\n";
                $offsprings[][] = [
                    'variableValue' => $x
                ];
            }
        }
        return ($offsprings);

        // foreach ($individuForMutation as $individu) {
        //     $offSprings[] = $this->offspring($individu);
        // }
        // return $this->exceedLimits($offSprings, $variableRanges);
    }

    function isBestBetween($populationEstimatedEffort, $offSpringEstimatedEfforts)
    {
        foreach ($offSpringEstimatedEfforts as $offSpringEstimatedEffort) {
            echo $populationEstimatedEffort . ' vs ' . $offSpringEstimatedEffort . '<br>';
            if ($populationEstimatedEffort < $offSpringEstimatedEffort) {
                $result[] = 1;
            } else {
                $result[] = 0;
            }
        }
        return $result;
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
        if ( $counts[0] >= round($this->percentage * $this->alpha) ){
            return 2;   // sebagian besar offspring lebih baik dari populasi
        }
    }

    function calcNewSigma($offspringsPercentage, $sigmaValue, $population)
    {
        $coeffIncrease = 1.1;
        $coeffDecrease = 0.9;
        if ($offspringsPercentage === 0 || $offspringsPercentage === 2){
            $population[0]['sigmaValue'] = $coeffIncrease * $sigmaValue;
        } 
        if ($offspringsPercentage === 1) {
            $population[0]['sigmaValue'] = $coeffDecrease * $sigmaValue;
        }
        return $population;
    }

    function getSigmaValue($population)
    {
        foreach ($population as $val){
            return ($val[0]['sigmaValue']);
        }
    }

    function comparingOffspringsWithPopulation($population, $offspringsVariable, $estimator, $testData)
    {
        $pop = $this->estimatingEfforts($population, $estimator, $testData);
        print_r($pop);
        echo '<p></p>';
        $off = $this->estimatingEfforts($offspringsVariable, $estimator, $testData);
        print_r($offspringsVariable);
        echo '<p></p>';

        foreach ($pop as $key => $val) {
            $comparison = $this->isBestBetween($val, array_chunk($off, $this->alpha)[$key]);
            $counts = array_count_values($comparison);
            $offspringPercentage = $this->calcGoodOffspringsPercentage($counts);
            $population[$key] = $this->calcNewSigma($offspringPercentage, $this->getSigmaValue($population), $population[$key]);
            echo '<p>';
        }
        //print_r($population);
        
        $selection = new SelectionFactory;
        $selection->initializingSelection('esElitism')->selectingIndividus($off, $estimator, $testData, count($population), $population, $offspringsVariable);
        exit();

    }

    function estimatingEfforts($population, $estimator, $testData)
    {
        $estimatorFactory = new EstimatorFactory;
        $estimation = $estimatorFactory->initializingEstimator($estimator);
        foreach ($population as $individu) {
            $variableIndividuValues = $this->getVariableIndividuValue($individu);
            $estimatedEfforts[] = $estimation->estimating($variableIndividuValues, $testData);
        }
        return $estimatedEfforts;
    }

    function findBestIndividus($estimatedEfforts, $testData, $stoppingValue)
    {
        $fitnessEvaluation = new FitnessEvaluation;
        return $fitnessEvaluation->evaluatingFitness($estimatedEfforts, $testData, $stoppingValue);
    }

    function isFound($bestIndividus, $stoppingValue)
    {
        $fitnessEvaluation = new FitnessEvaluation;
        return $fitnessEvaluation->solutionIsFound($bestIndividus, $stoppingValue);
    }

    function getIndexOfMinBestIndividu($bests)
    {
        $globalBest = min(array_column($bests, 'minAEOfBestIndividu'));
        return $bests[array_search($globalBest, array_column($bests, 'indexOfBestIndividu'))];
    }

    function optimizing($population, $estimator, $testData, $parameters, $variableRanges)
    {
        echo 'Initial population';
        echo '<br>';
        print_r($population);
        echo '<p></p>';
        $estimatedEfforts = $this->estimatingEfforts($population, $estimator, $testData);
        $bestIndividus = $this->findBestIndividus($estimatedEfforts, $testData, $parameters['stoppingValue']);
        $bestIndividuIsFound = $this->isFound($bestIndividus, $parameters['stoppingValue']);

        $iter = 0;
        while ($bestIndividuIsFound === false || $iter <= $parameters['maxIter']) {
            if ($bestIndividuIsFound) {
                echo 'ditemukan';
                return $bestIndividus;
                break;
            }
            //start mutation
            $offsprings = $this->offsprings($population, $variableRanges);
            $this->comparingOffspringsWithPopulation($population, $offsprings, $estimator, $testData);

            //$estimatedEfforts = $this->estimatingEfforts($offsprings, $estimator, $testData);


            exit();

            // $index = $bestIndividus['indexOfBestIndividu'];
            // $selectedIndividuForMutation = $this->mutation($population[$index], $variableRanges);
            // print_r($selectedIndividuForMutation); exit();
            // echo "\n";


            $bests[] = $bestIndividus;
            $iter++;
        }
        return $this->getIndexOfMinBestIndividu($bests);
    }
}
