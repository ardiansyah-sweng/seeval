<?php

namespace algorithms\pso;

interface InertiaWeightInterface
{
    public function inertiaWeighting($parameterSet, $iter, $maxIter);
}

class LinearDecreasing implements InertiaWeightInterface
{
    function inertiaWeighting($parameterSet, $iter, $maxIter)
    {
        return $parameterSet->getPSOParameter()['inertiaMax'] - ((($parameterSet->getPSOParameter()['inertiaMax'] - $parameterSet->getPSOParameter()['inertiaMin']) * $iter) / $maxIter);
    }
}

/**
 * Zhang J, Sheng J, Lu J, Shen L. UCPSO: A Uniform Initialized Particle Swarm Optimization Algorithm with Cosine Inertia Weight. Gastaldo P, editor. Comput Intell Neurosci [Internet]. 2021 Mar 18;2021:1–18. Available from: https://www.hindawi.com/journals/cin/2021/8819333/
 */
class RankBased implements InertiaWeightInterface
{
    function __construct($particles, $particle, $popSize, $chaoticValue)
    {
        $this->particles = $particles;
        $this->particle = $particle;
        $this->popSize = $popSize;
        $this->chaoticValue = $chaoticValue;
    }

    function RIW()
    {
        array_multisort(array_column($this->particles, 'ae'), SORT_ASC, $this->particles);
        $rank = array_search($this->particle['ae'], array_column($this->particles, 'ae'));
        $b = 1;
        if ($rank <= ($this->popSize / 4)) {
            $b = 2 / 3;
        }
        if ($rank >= (3 * $this->popSize) / 4) {
            $b = 1.5;
        }
        return $b;
    }

    function inertiaWeighting($parameterSet, $iter, $maxIter)
    {
        $w_ini = $parameterSet->getPSOParameter()['inertiaMax'];
        $w_fin = $parameterSet->getPSOParameter()['inertiaMin'];
        $w_cos = ((($w_ini + $w_fin) / 2) + (($w_ini - $w_fin) / 2)) * $this->chaoticValue;
        $b = $this->RIW();
        return $b * $w_cos;
    }
}

/**
 * Liu H, Zhang XW, Tu LP. A modified particle swarm optimization using adaptive strategy. Expert Syst Appl [Internet]. 2020;152(15 August 2020):113353. Available from: https://doi.org/10.1016/j.eswa.2020.113353
 */
class Chaotic implements InertiaWeightInterface
{
    function __construct($chaoticValue, $chaoticMap)
    {
        $this->chaoticValue = $chaoticValue;
        $this->chaoticMap = $chaoticMap;
    }
    function inertiaWeighting($parameterSet, $iter, $maxIter)
    {
        return $this->chaoticValue * $parameterSet->getPSOParameter()['inertiaMin'] + ((($parameterSet->getPSOParameter()['inertiaMax'] - $parameterSet->getPSOParameter()['inertiaMin']) * $iter) / $maxIter);
    }
}

class InertiaWeightFactory
{
    public function initializePopulation($chaoticValue, $inertiaType, $particles, $particle, $popSize)
    {
        $chaoticMap = 'cosine';
        $inertiaTypes = [
            ['inertiaType' => 'ldw', 'inertiaWeight' => new LinearDecreasing],
            ['inertiaType' => 'rankBased', 'inertiaWeight' => new RankBased($particles, $particle, $popSize, $chaoticValue)],
            ['inertiaType' => 'chaotic', 'inertiaWeight' => new Chaotic($chaoticValue, $chaoticMap)]
        ];
        $index = array_search($inertiaType, array_column($inertiaTypes, 'inertiaType'));
        return $inertiaTypes[$index]['inertiaWeight'];
    }
}
