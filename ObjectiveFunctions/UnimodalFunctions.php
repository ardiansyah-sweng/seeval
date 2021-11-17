<?php

namespace ObjectiveFunctions;

Interface UnimodalFunctionsInterface
{
    function unimodal($variables);
}

/**
 * Dimension: 30
 * Range: [-100, 100]
 * fmin = 0
 */
class UnimodalF1 implements UnimodalFunctionsInterface
{
    function unimodal($variables)
    {
       foreach ($variables as $variable){
           $results[] = pow($variable, 2);
       }
       return array_sum($results);
    }
}

/**
 * Dimension: 30
 * Range: [-10, 10]
 * fmin = 0
 */
class UnimodalF2 implements UnimodalFunctionsInterface
{
    function unimodal($variables)
    {
        foreach ($variables as $variable){
            $absoluteNumbers[] = $variable;
        }

        foreach ($variables as $variable){
            $results[] = abs($variable) + array_product($absoluteNumbers);
        }
        return $results;
    }
}

class UnimodalF3 implements UnimodalFunctionsInterface
{
    function unimodal($variables)
    {
        foreach ($variables as $variable){
            //
        }
    }
}
class UnimodalF4 implements UnimodalFunctionsInterface
{
    function unimodal($variables)
    {
        //   
    }
}
class UnimodalF5 implements UnimodalFunctionsInterface
{
    function unimodal($variables)
    {
        //   
    }
}
class UnimodalF6 implements UnimodalFunctionsInterface
{
    function unimodal($variables)
    {
        //   
    }
}
class UnimodalF7 implements UnimodalFunctionsInterface
{
    function unimodal($variables)
    {
        //   
    }
}

class UnimodalFunctionsFactory
{
    public function initializingUnimodalFunctions($function)
    {
        $functions = [
            ['function' => 'f1', 'select' => new UnimodalF1],
            ['function' => 'f1', 'select' => new UnimodalF2],
            ['function' => 'f1', 'select' => new UnimodalF3],
            ['function' => 'f1', 'select' => new UnimodalF4],
            ['function' => 'f1', 'select' => new UnimodalF5],
            ['function' => 'f1', 'select' => new UnimodalF6],
            ['function' => 'f1', 'select' => new UnimodalF7],
        ];
        $index = array_search($function, array_column($functions, 'function'));
        return $functions[$index]['select'];
    }
}