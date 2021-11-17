<?php
include 'individu_interface.php';

interface PopulationInterface
{
    public function createPopulation($parameters);
}

class RandomPopulation implements PopulationInterface
{
    function hasIndividu($parameters)
    {
        return (new IndividuFactory())->initializingIndividu($parameters['individuType'], '', $parameters['popSize']);
    }

    public function createPopulation($parameters)
    {
        if (!$this->hasIndividu($parameters)){
            return 'Individu does not exist...';
        }
        for ($i = 0; $i <= $parameters['popSize'] - 1; $i++) {
            $ret[] = $this->hasIndividu($parameters)->createIndividu($parameters['numOfVariable'], $parameters['variableRanges']);
        }
        return $ret;
    }
}

class SeedsPopulation implements PopulationInterface
{
    function __construct($seedsFileName)
    {
        $this->seedsFileName = $seedsFileName;
    }

    function hasLabeledPopulations($parameters)
    {
        return (new IndividuFactory($this->seedsFileName))->initializingIndividu($parameters['individuType'], $this->seedsFileName, $parameters['popSize'])->createIndividu($parameters['numOfVariable'], $parameters['variableRanges']);
    }

    function createPopulation($parameters)
    {
        $finalPopulations = [];
        foreach ($this->hasLabeledPopulations($parameters) as $key => $val){
            if ($key < $parameters['popSize']){
                $finalPopulations[] = $val;
            }
        }
        return $finalPopulations;
    }
}

class PopulationFactory
{
    public function initializingPopulation($generateType, $seedsFileName)
    {
        $populationTypes = [
            ['populationType' => 'random', 'select' => new RandomPopulation],
            ['populationType' => 'seeds', 'select' => new SeedsPopulation($seedsFileName)],
        ];
        $index = array_search($generateType, array_column($populationTypes, 'populationType'));
        return $populationTypes[$index]['select'];
    }
}
