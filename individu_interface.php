<?php
require_once 'dataprocessor_interface.php';


interface IndividuInterface
{
    public function createIndividu($numOfVariable, $variableRanges);
}

class Randomizer
{
    static function randomZeroToOneFraction()
    {
        return (float) rand() / (float) getrandmax();
    }

    static function randomVariableValueByRange($variableRanges)
    {
        return mt_rand($variableRanges['lowerBound'] * 100, $variableRanges['upperBound'] * 100) / 100;
    }
}

class ESRandomIndividu implements IndividuInterface
{
    function createIndividu($numOfVariable, $variableRanges)
    {
        if (count($variableRanges) === $numOfVariable) {
            for ($i = 0; $i <= $numOfVariable - 1; $i++) {
                $ret[] = [
                    'variableValue' => Randomizer::randomVariableValueByRange($variableRanges[$i]),
                    'sigmaValue' => Randomizer::randomZeroToOneFraction()
                ];
            }
        } else {
            return FALSE;
        }
        return $ret;
    }
}

class GARandomIndividu implements IndividuInterface
{
    function createIndividu($numOfVariable, $variableRanges)
    {
        if (count($variableRanges) !== $numOfVariable) {
            return 'Unable to create individu...';
        }
        for ($i = 0; $i <= $numOfVariable - 1; $i++) {
            $individu[] = [
                'variableValue' => Randomizer::randomVariableValueByRange($variableRanges[$i])
            ];
        }
        return $individu;
    }
}

class GASeedsIndividu implements IndividuInterface
{
    function __construct($seedsFileName, $popSize)
    {
        $this->seedsFileName = $seedsFileName;
        $this->popSize = $popSize;
    }

    function labeling($individu)
    {
        $ret = [];
        foreach (explode(",", $individu) as $val) {
            $ret[] = ['variableValue' => $val];
        }
        return $ret;
    }

    function createIndividu($numOfVariable, $variableRanges)
    {
        $labeledPopulations = [];
        foreach (file($this->seedsFileName) as $individu) {
            $labeledPopulations[] = $this->labeling($individu);
        }
        return $labeledPopulations;
    }
}

class IndividuFactory
{
    public function initializingIndividu($individuType, $seedsFilename, $popSize)
    {
        $individuTypes = [
            ['individuType' => 'esRandom', 'select' => new ESRandomIndividu],
            ['individuType' => 'gaRandom', 'select' => new GARandomIndividu],
            ['individuType' => 'gaSeeds', 'select' => new GASeedsIndividu($seedsFilename, $popSize)]
        ];
        $index = array_search($individuType, array_column($individuTypes, 'individuType'));
        return $individuTypes[$index]['select'];
    }
}
