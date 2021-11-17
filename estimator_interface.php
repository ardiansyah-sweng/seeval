<?php
require_once 'dataprocessor_interface.php';

interface EstimatorInterface
{
    public function estimating($variableData, $testData);
}

class UCP implements EstimatorInterface
{
    function __construct($productivityFactor)
    {
        $this->productivityFactor = $productivityFactor;
    }

    function getTestDataValue($testData)
    {
        return [
            $testData['simple'],
            $testData['average'],
            $testData['complex']
        ];
    }

    function calculateUseCase($variableValues, $testDataValues)
    {
        foreach ($variableValues as $key => $variableValue) {
            $ret[] = floatval($variableValue) * floatval($testDataValues[$key]);
        }
        return $ret;
    }

    function estimating($variableValues, $testData)
    {
        $useCases = $this->calculateUseCase($variableValues, $this->getTestDataValue($testData));
        $UUCW = array_sum($useCases);
        $UUCP = $UUCW + $testData['uaw'];
        $UCP = $UUCP * $testData['tcf'] * $testData['ecf'];
        $estimatedEffort = $UCP * $this->productivityFactor;
        return $estimatedEffort;
    }
}

class Cocomo implements EstimatorInterface
{
    // static function randomZeroToOneFraction()
    // {
    //     return (float) rand() / (float) getrandmax();
    // }

    function convertingLetterToNumber($testData)
    {
        $cocomoNasa93 = new CocomoNasa93Processor;
        foreach ($cocomoNasa93->getScales() as $key => $scales) {
            foreach (array_keys($scales) as $subKey) {
                if ($subKey === $testData[$key]) {
                    $converteds[$key] = $scales[$subKey];
                }
            }
        }
        return $converteds;
    }

    function getScaleFactors($converteds)
    {
        $SFComponents = ['prec', 'flex', 'resl', 'team', 'pmat'];
        foreach(array_keys($converteds) as $key => $val){
            if ($key < count($SFComponents)){
                $SF[$val] = $converteds[$val];
            }
        }
        return $SF;
    }

    function getEffortMultiplier($converteds)
    {
        $EMComponents = ['rely', 'data', 'cplx', 'ruse', 'docu', 'time', 'stor', 'pvol', 'acap', 'pcap', 'pcon', 'apex', 'plex', 'ltex', 'tool', 'site', 'sced'];
        foreach (array_keys($converteds) as $key => $val) {
            if ($key < count($EMComponents)) {
                $EM[$val] = $converteds[$val];
            }
        }
        return $EM;
    }

    function estimatingEffort($variableData, $SF, $kloc, $effortMultipliers)
    {
        $scaleEffortExponent = floatval($variableData[1]) + 0.01 * array_sum($SF);
        return floatval($variableData[0]) * pow($kloc, $scaleEffortExponent) * array_product($effortMultipliers);
    }

    function estimating($variableData, $testData)
    {
        $converteds = $this->convertingLetterToNumber($testData);
        if (!$converteds){
            return 'Failed to converting letter to number';
        }
        $estimatedEffort = $this->estimatingEffort($variableData, $this->getScaleFactors($converteds), $testData['kloc'], $this->getEffortMultiplier($converteds));
        return $estimatedEffort;
    }
}

class Agile implements EstimatorInterface
{
    function isFF($variableData)
    {
        $ffLength = 4;
        foreach ($variableData as $key => $val) {
            if ($key < $ffLength) {
                $ff[] = $val;
            }
        }
        return $ff;
    }

    function isDFF($variableData)
    {
        $dffLength = 9;
        foreach ($variableData as $key => $val) {
            if ($key < $dffLength) {
                $dff[] = $val;
            }
        }
        return $dff;
    }

    function calcAbsoluteError($variableData, $testData)
    {
        $deceleration = array_product($this->isFF($variableData)) * array_product($this->isDFF($variableData));
        $velocity =  pow($testData['Vi'], $deceleration);
        $estimatedTime = $testData['effort'] / $velocity;
        return abs($estimatedTime - floatval($testData['actualEffort']));
    }

    function estimating($variableData, $testData)
    {
        return $this->calcAbsoluteError($variableData, $testData);
    }
}

class EstimatorFactory
{
    public function initializingEstimator($estimator)
    {
        $productivityFactor = 20;
        $estimators = [
            ['estimator' => 'ucp', 'select' => new UCP($productivityFactor)],
            ['estimator' => 'cocomo', 'select' => new Cocomo],
            ['estimator' => 'agile', 'select' => new Agile],
        ];
        $index = array_search($estimator, array_column($estimators, 'estimator'));
        return $estimators[$index]['select'];
    }
}
