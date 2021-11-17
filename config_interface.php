<?php
/**
 * Interface untuk pemilihan setting parameter
 * Return raw data dalam array
 */
interface ConfigInterface
{
    public function selectingConfig($parameters);
}

class GAConfiguration implements ConfigInterface
{
    public function selectingConfig($parameters)
    {
        return $parameters;
    }
}

class ESConfiguration implements ConfigInterface
{
    public function selectingConfig($parameters)
    {
        return $parameters;
    }
}

/**
 * Dataset processor selection
 *
 */
class ConfigFactory
{
    public function initializingConfig($configType)
    {
        $configTypes = [
            ['configType' => 'ga', 'selectingConfig' => new GAConfiguration],
            ['configType' => 'es', 'selectingConfig' => new ESConfiguration]
        ];
        $index = array_search($configType, array_column($configTypes, 'configType'));
        return $configTypes[$index]['selectingConfig'];
    }
}

## Penggunaan
// $configTypes = [
//     'ga',
//     'es',
// ];

// $parameters = [
//     'popSize' => 10,
//     'stoppingValue' => 10
// ];

// $configFactory = new ConfigFactory;
// $config = $configFactory->initializingConfig($configTypes[0]);
// $configuration = $config->selectingConfig($parameters);
// print_r($configuration);
