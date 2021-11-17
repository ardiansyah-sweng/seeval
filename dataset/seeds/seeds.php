<?php

class Data
{
    function getDataUCP()
    {
         //$path = 'dataset/seeds/ucp/';
        $path = 'dataset/seeds/ucp_evaluation/';
        $files = array_diff(scandir($path), array('.', '..'));
        foreach ($files as $fileName) {
            $ret[] = $path . '' . $fileName;
        }
        return $ret;
    }

    function getDataAgile()
    {
        //$path = 'dataset/seeds/agile100/';
        $path = 'dataset/seeds/agile_evaluation/';
        $files = array_diff(scandir($path), array('.', '..'));
        foreach ($files as $fileName) {
            $ret[] = $path . '' . $fileName;
        }
        return $ret;
    }

    function getDataCocomo()
    {
        $path = 'dataset/seeds/cocomo_evaluation/';
        //$path = 'dataset/seeds/cocomo_sachan/';
        $files = array_diff(scandir($path), array('.', '..'));
        foreach ($files as $fileName){
            $ret[] = $path.''.$fileName;
        }
        return $ret;
    }
}
