<?php
require_once 'randomizer.php';

interface MutationInterface
{
    public function changeGen($genValue);
}

class BinaryMutation implements MutationInterface
{
    function changeGen($genValue)
    {
        if ($genValue === 0){
            return 1;
        } else {
            return 0;
        }
    }
}

class RealMutation implements MutationInterface
{
    function __construct($indexOfGen, $variableRanges)
    {
        $this->indexOfGen = $indexOfGen;
        $this->variableRanges = $variableRanges;
    }

    function changeGen($genValue)
    {
        $ranges = $this->variableRanges[$this->indexOfGen];
        return Randomizers::randomVariableValueByRange($ranges);
    }
}

class MutationFactory
{
    function __construct($indexOfGen, $variableRanges)
    {
        $this->indexOfGen = $indexOfGen;
        $this->variableRanges = $variableRanges;
    }
    function initializingMutation($mutationType)
    {
        $mutationTypes = [
            ['mutationType' => 'binary', 'select'=> new BinaryMutation],
            ['mutationType' => 'real', 'select' => new RealMutation($this->indexOfGen, $this->variableRanges)]
        ];
        $index = array_search($mutationType, array_column($mutationTypes, 'mutationType'));
        return $mutationTypes[$index]['select'];
    }
}

class Mutation
{
    function __construct($population, $lengthOfIndividu, $variableRanges)
    {
        $this->population = $population;
        $this->lengthOfIndividu = $lengthOfIndividu;
        $this->variableRanges = $variableRanges;
    }

    function calcMutationRate()
    {
        return 1 / $this->lengthOfIndividu;
    }

    function calcNumOfMutation($mutationRate)
    {
        return round($mutationRate * count($this->population));
    }

    function mutation()
    {
        $mutationType = ['binary', 'real'];
        $mutationRate = $this->calcMutationRate();
        $numOfMutation = $this->calcNumOfMutation($mutationRate);
        $ret = [];
        for ($i = 0; $i <= $numOfMutation-1; $i++){
            $indexOfIndividu = Randomizers::getRandomIndexOfIndividu(count($this->population));
            $indexOfGen = Randomizers::getCutPointIndex($this->lengthOfIndividu);
            $mutatedIndividu = $this->population[$indexOfIndividu];
            $valueOfGen = $mutatedIndividu[$indexOfGen]['variableValue'];
            $mutationFactory = new MutationFactory($indexOfGen, $this->variableRanges);
            $mutatedGen = $mutationFactory->initializingMutation($mutationType[1])->changeGen($valueOfGen);
            $mutatedIndividu[$indexOfGen]['variableValue'] = $mutatedGen;

            $ret[] = $mutatedIndividu;
        }
        return $ret;
    }
}