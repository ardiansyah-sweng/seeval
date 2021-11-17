<?php

class SeedsGenerator
{
    public $sizeOfPopulation;
    public $numOfVariable;
    public $numOfSeeds;
    public $pathToSeeds;

    // public $variableRanges = [
    //     ['lowerBound' => 0.91, 'upperBound' => 1],
    //     ['lowerBound' => 0.89, 'upperBound' => 1],
    //     ['lowerBound' => 0.96, 'upperBound' => 1],
    //     ['lowerBound' => 0.85, 'upperBound' => 1],
    //     ['lowerBound' => 0.91, 'upperBound' => 1],
    //     ['lowerBound' => 0.96, 'upperBound' => 1],
    //     ['lowerBound' => 0.9, 'upperBound' => 1],
    //     ['lowerBound' => 0.98, 'upperBound' => 1],
    //     ['lowerBound' => 0.98, 'upperBound' => 1],
    //     ['lowerBound' => 0.96, 'upperBound' => 1],
    //     ['lowerBound' => 0.95, 'upperBound' => 1],
    //     ['lowerBound' => 0.97, 'upperBound' => 1],
    //     ['lowerBound' => 0.98, 'upperBound' => 1]
    // ];

    public $variableRanges = [
        ['lowerBound' => 0, 'upperBound' => 10],
        ['lowerBound' => 0.3, 'upperBound' => 2],
    ];

    function generateRandomVariable($variableRanges)
    {
        return mt_rand($variableRanges['lowerBound'] * 100, $variableRanges['upperBound'] * 100) / 100;
    }

    public function writeToTXTFile($seedsIteration)
    {
        for ($i = 0; $i < $this->sizeOfPopulation; $i++) {
            for ($j = 0; $j < $this->numOfVariable; $j++) {
                $variables[] = $this->generateRandomVariable($this->variableRanges[$j]);
            }
            $fp = fopen('seeds_master.txt', 'a');
            fputcsv($fp, $variables);
            fclose($fp);
            $variables = [];
        }

        $file_content = file_get_contents('seeds_master.txt');
        file_put_contents($this->pathToSeeds . 'seeds' . $seedsIteration . '.txt', $file_content);
    }

    function createSeedsFile()
    {
        for ($i = 0; $i < $this->numOfSeeds; $i++) {
            $this->writeToTXTFile($i);
            $f = @fopen("seeds_master.txt", "r+");
            if ($f !== false) {
                ftruncate($f, 0);
                fclose($f);
            }
        }
    }
}

$seedsGenerator = new SeedsGenerator;
$seedsGenerator->sizeOfPopulation = 2500;
$seedsGenerator->numOfVariable = 2;
$seedsGenerator->numOfSeeds = 30;
$seedsGenerator->pathToSeeds = 'cocomo_evaluation/';
$seedsGenerator->createSeedsFile();
