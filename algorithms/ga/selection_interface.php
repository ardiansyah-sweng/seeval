<?php
require_once 'estimator_interface.php';
require_once 'OptimizerInterface.php';

interface SelectionInterface
{
    public function selectingIndividus($combinedPopulation, $estimator, $testData, $popSize, $population, $offspringsVariable);
}

class ElitismSelection implements SelectionInterface
{
    function getIndividuByPopSize($sortedEstimatedEfforts, $popSize)
    {
        $keys = [];
        $ret = [];
        foreach (array_keys($sortedEstimatedEfforts) as $key) {
            $keys[] = $key;
        }
        $populationKeys = array_slice($keys, 0, $popSize);
        foreach ($populationKeys as $key) {
            $ret[] = [
                'key' => $key,
                'estimatedEffort' => $sortedEstimatedEfforts[$key]
            ];
        }
        return $ret;
    }

    function getVariableValueFromPopulation($combinedOffsprings, $bestSoFarIndividuKeys)
    {
        $ret = [];
        foreach ($bestSoFarIndividuKeys as $bestSoFarIndividuKey) {
            foreach ($combinedOffsprings as $key => $combinedOffspring) {
                if ($key === $bestSoFarIndividuKey['key']) {
                    $ret[] = $combinedOffspring;
                }
            }
        }
        return $ret;
    }

    function hasEstimator($estimator)
    {
        $estimatorFactory = new EstimatorFactory;
        return $estimatorFactory->initializingEstimator($estimator);
    }

    function hasIndividuValues($individu, $algorithmTypes)
    {
        $optimizerFactory = new OptimizerFactory;
        return $optimizerFactory->initializingOptimizer($algorithmTypes[0])->getVariableIndividuValue($individu);
    }

    function selectingIndividus($combinedOffsprings, $estimator, $testData, $popSize, $population, $offspringsVariable)
    {
        ## TODO refaktor biar dimasukkan bagian setting parameters
        $algorithmTypes = ['ga', 'es'];
        if (!$this->hasEstimator($estimator)) {
            return 'Estimator does not exist...';
        }

        foreach ($combinedOffsprings as $key => $individu) {
            if (!$this->hasIndividuValues($individu, $algorithmTypes)) {
                return 'Individu does not exist...';
            }
            $estimatedEfforts[$key][] = $this->hasEstimator($estimator)->estimating($this->hasIndividuValues($individu, $algorithmTypes), $testData);
        }

        ## TODO refactor dg menambahkan try catch jika $estimatedEffort == null
        asort($estimatedEfforts);
        $sortedEstimatedEfforts = $this->getIndividuByPopSize($estimatedEfforts, $popSize);

        return $this->getVariableValueFromPopulation($combinedOffsprings, $sortedEstimatedEfforts);
    }
}

class ESElitismSelection implements SelectionInterface
{
    function selectingIndividus($combinedPopulation, $estimator, $testData, $popSize, $population, $offsprings)
    {
        asort($offsprings);
        return array_slice($offsprings, 0, $popSize);
    }
}

class BinaryTournamentSelection implements SelectionInterface
{
    function selectingIndividus($combinedPopulation, $estimator, $testData, $popSize, $population, $offspringsVariable)
    {
        //
    }
}

class RouletteWheelSelection implements SelectionInterface
{
    function selectingIndividus($combinedPopulation, $estimator, $testData, $popSize, $population, $offspringsVariable)
    {
    }
}

class SelectionFactory
{
    function initializingSelection($selectionType)
    {
        $selectionTypes = [
            ['selectionType' => 'elitism', 'select' => new ElitismSelection],
            ['selectionType' => 'esElitism', 'select' => new ESElitismSelection],
            ['selectionType' => 'binary', 'select' => new BinaryTournamentSelection],
            ['selectionType' => 'roulette', 'select' => new RouletteWheelSelection]
        ];
        $index = array_search($selectionType, array_column($selectionTypes, 'selectionType'));
        return $selectionTypes[$index]['select'];
    }
}
