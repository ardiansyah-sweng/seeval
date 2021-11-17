<?php

namespace algorithms\pso;

interface PopInitializationInterface
{
    public function initializingPopulation();
}

/**
 * Based on Schi & Eberhart
 */
class StandardInitialization implements PopInitializationInterface
{
    function __construct($population)
    {
        $this->population = $population;
    }

    function initializingPopulation()
    {
        foreach ($this->population as $variables) {
            $ret[] = $variables['variableValue'];
        }
        return $ret;
    }
}

/**
 * Zhang J, Sheng J, Lu J, Shen L. UCPSO: A Uniform Initialized Particle Swarm Optimization Algorithm with Cosine Inertia Weight. Gastaldo P, editor. Comput Intell Neurosci [Internet]. 2021 Mar 18;2021:1–18. Available from: https://www.hindawi.com/journals/cin/2021/8819333/
 */
class UniformInitialization implements PopInitializationInterface
{
    function __construct($parameters, $generateType, $population)
    {
        $this->parameters = $parameters;    
        $this->generateType = $generateType;
        $this->population = $population;
    }

    function randomVariables($variableRanges)
    {
        foreach ($variableRanges as $variableRange) {
            $variables[] = mt_rand($variableRange['lowerBound'] * 100, $variableRange['upperBound'] * 100) / 100;
        }
        return $variables;
    }

    function createUniformVariable($numOfVariable, $X1, $r, $popSize, $variableRanges)
    {
        for ($i = 0; $i < $numOfVariable; $i++) {
            $uniformVariables[$i] = $X1[$i] + $r[$i] / $popSize * ($variableRanges[$i]['upperBound'] - $variableRanges[$i]['lowerBound']);
        }
        return $uniformVariables;
    }

    function adjustingUniformVariables($uniformVariables, $variableRanges)
    {
        foreach ($uniformVariables as $key => $variable) {
            if ($variable > $variableRanges[$key]['upperBound']) {
                $variables[$key] = $variable - ($variableRanges[$key]['upperBound'] - $variableRanges[$key]['lowerBound']);
            } else {
                $variables[$key] = $variable;
            }
        }
        return $variables;
    }

    function initializingPopulation()
    {
        $X1 = $this->randomVariables($this->parameters['variableRanges']);
        for ($i = 1; $i < $this->parameters['popSize']; $i++) {
            $R[$i] = $this->randomVariables($this->parameters['variableRanges']);
        }
        if ($this->generateType === 'seeds'){
            $R[$i] = $this->population;
        }
        foreach ($R as $key => $r) {
            $uniformVariables = $this->createUniformVariable($this->parameters['numOfVariable'], $X1, $r, $this->parameters['popSize'], $this->parameters['variableRanges']);
            $adjustedUniformVariables = $this->adjustingUniformVariables($uniformVariables, $this->parameters['variableRanges']);
            if (($key - 1) == 0) {
                return $X1;
            }
            return $adjustedUniformVariables;
        }
    }
}

class PopInitializationFactory
{
    public function initializePopulation($population, $parameters, $popInitializerType, $generateType)
    {
        $popInitializerTypes = [
            ['initializerType' => 'spso', 'initializer' => new StandardInitialization($population)],
            ['initializerType' => 'uniform', 'initializer' => new UniformInitialization($parameters, $generateType, $population)]
        ];
        $index = array_search($popInitializerType, array_column($popInitializerTypes, 'initializerType'));
        return $popInitializerTypes[$index]['initializer'];
    }
}
