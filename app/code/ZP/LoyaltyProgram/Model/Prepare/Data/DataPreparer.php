<?php
declare(strict_types=1);

namespace ZP\LoyaltyProgram\Model\Prepare\Data;

class DataPreparer
{
    public function arrayValuesToString(array $array): array
    {
        foreach ($array as $key => $value) {
            $array[$key] = (string)$value;
        }

        return $array;
    }

    public function arrayValuesToInteger(array $array): array
    {
        foreach ($array as $key => $value) {
            $array[$key] = (int)$value;
        }

        return $array;
    }

    public function makeArrayKeysLikeValues(array $array): array
    {
        $data = [];
        foreach ($array as $value) {
            $data[$value] = $value;
        }

        return $data;
    }

    public function makeArrayValuesLikeKeys(array $array): array
    {
        $data = [];
        foreach ($array as $key => $value) {
            $data[$key] = $key;
        }

        return $data;
    }

    /**
     * Method to combine arrays inside GrandArray without merging, in order to save data keys.
     * Can be used only for arrays that have two levels. And Keys inside array of level two MUST BE UNIQUE!!!
     * This method works only for arrays like this:
     *  "array([$unique1 => $value1, $unique2 => $value2, ...], [$unique3 => $value1, $unique4 => $value2, ...], [$unique5 => $value1, $unique6 => $value2, ...])"
     * @param array $grandArray
     * @return array
     * Returns array that has only 1 level.
     */
    public function combineArraysInsideArray(array $grandArray): array
    {
        $data = [];
        foreach ($grandArray as $array) {
            $data += $array;
        }

        return $data;
    }

    public function explodeArray(array $data): array
    {
        if (count($data) === 1) {
            $data = explode(',', array_shift($data));
        }

        return $this->arrayValuesToInteger($data);
    }
}
