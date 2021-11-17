<?php
require_once 'randomizer.php';

##TODO change from crossover into reproduction
interface CrossoverInterface
{
    public function crossover($population, $crossoverRate, $lengthOfChromosome);
}

class CrossoverGenerator
{
    function hasParents($population, $crossoverRate)
    {
        for ($i = 0; $i <= count($population) - 1; $i++) {
            $randomZeroToOne = Randomizers::randomZeroToOneFraction();
            if ($randomZeroToOne < $crossoverRate) {
                $parents[$i] = $randomZeroToOne;
            }
        }
        return $parents;
    }

    function generateCrossover($population, $crossoverRate)
    {
        $ret = [];
        $count = 0;
        $parents = $this->hasParents($population, $crossoverRate);
        while ($count < 1 && count($parents)===0){
            $parents = $this->hasParents($population, $crossoverRate);
            if (count($parents) > 0){
                break;
            }
            $count = 0;
        }
        foreach (array_keys($parents) as $key) {
            $keys[] = $key;
        }
        foreach ($keys as $key => $val) {
            foreach ($keys as $subval) {
                if ($val !== $subval) {
                    $ret[] = [$val, $subval];
                }
            }
            array_shift($keys);
        }
        return $ret;
    }
}

class OneCutPoint implements CrossoverInterface
{
    function isMaxIndex($cutPointIndex, $lengthOfChromosome)
    {
        if ($cutPointIndex === $lengthOfChromosome - 1) {
            return TRUE;
        }
    }

    function offspring($parent1, $parent2, $cutPointIndex, $offspring, $lengthOfChromosome)
    {
        $ret = [];
        ## TODO refaktor tenanan bro!
        if ($offspring === 1) {
            if ($this->isMaxIndex($cutPointIndex, $lengthOfChromosome)) {
                foreach ($parent2 as $key => $val) {
                    if ($key < $cutPointIndex) {
                        $ret[] = $val['variableValue'];
                    }
                }
                $ret[] = $parent1[$cutPointIndex]['variableValue'];
            } else {
                foreach ($parent1 as $key => $val) {
                    if ($key <= $cutPointIndex) {
                        $ret[] = $val['variableValue'];
                    }
                    if ($key > $cutPointIndex) {
                        $ret[] = $parent2[$key]['variableValue'];
                    }
                }
            }
        }

        if ($offspring === 2) {
            if ($this->isMaxIndex($cutPointIndex, $lengthOfChromosome)) {
                foreach ($parent1 as $key => $val) {
                    if ($key < $cutPointIndex) {
                        $ret[] = $val['variableValue'];
                    }
                }
                $ret[] = $parent2[$cutPointIndex]['variableValue'];
            } else {
                foreach ($parent2 as $key => $val) {
                    if ($key <= $cutPointIndex) {
                        $ret[] = $val['variableValue'];
                    }
                    if ($key > $cutPointIndex) {
                        $ret[] = $parent1[$key]['variableValue'];
                    }
                }
            }
        }
        return $ret;
    }

    public function crossover($population, $crossoverRate, $lengthOfChromosome)
    {
        $crossoverGenerator = new CrossoverGenerator;
        $parents = $crossoverGenerator->generateCrossover($population, $crossoverRate);
        $ret = [];
        foreach ($parents as $parent) {
            $cutPointIndex = Randomizers::getCutPointIndex($lengthOfChromosome);
            // echo 'Cut:' . $cutPointIndex;
            // echo '<br>';
            // echo 'Parents: <br>';
            // print_r($population[$parent[0]]);
            $parent1 = $population[$parent[0]];
            // echo '<br>';
            // print_r($population[$parent[1]]);
            $parent2 = $population[$parent[1]];
            // echo '<br>';
            // echo 'Offspring:<br>';
            $offspring1 = $this->offspring($parent1, $parent2, $cutPointIndex, 1, $lengthOfChromosome);
            $offspring2 = $this->offspring($parent1, $parent2, $cutPointIndex, 2, $lengthOfChromosome);
            //print_r($offspring1);
            //echo '<br>';
            //print_r($offspring2);
            //echo '<p></p>';
            $ret[] = $offspring1;
            $ret[] = $offspring2;
        }
        return $ret;
    }
}

class TwoCutPoint implements CrossoverInterface
{
    public function crossover($population, $crossoverRate, $lengthOfChromosome)
    {
        //write your code here
    }
}

class CrossoverFactory
{
    function initializingCrossover($crossoverType)
    {
        $crossoverTypes = [
            ['crossoverType' => 'oneCutPoint', 'select' => new OneCutPoint],
            ['crossoverType' => 'twoCutPoint', 'select' => new TwoCutPoint]
        ];
        $index = array_search($crossoverType, array_column($crossoverTypes, 'crossoverType'));
        return $crossoverTypes[$index]['select'];
    }
}
