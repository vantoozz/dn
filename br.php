<?php

$start = microtime(true);

$inputFile = new SplFileObject($argv[1]);
$inputFile->setFlags(SplFileObject::DROP_NEW_LINE);
$inputText = explode(' ', mb_strtoupper($inputFile->fgets()));
$inputFile = null;

$vocabulary = [];
$lengths = [];

$file = new SplFileObject('vocabulary.txt');
$file->setFlags(SplFileObject::DROP_NEW_LINE);

while (!$file->eof()) {
    $line = $file->fgets();
    $length = mb_strlen($line);
    $vocabulary[$length][$line] = $line;
    $lengths[$length] = $length;
}

unset($vocabulary[0], $lengths[0]);

$file = null;

$totalDistance = 0;
$lengthsCount = count($lengths);

$cache = [];

foreach ($inputText as $inputWord) {
    /** @noinspection UnSafeIsSetOverArrayInspection */
    if (isset($cache[$inputWord])) {
        $totalDistance += $cache[$inputWord];
        continue;
    }

    $wordLength = mb_strlen($inputWord);
    /** @noinspection UnSafeIsSetOverArrayInspection */
    if (isset($vocabulary[$wordLength][$inputWord])) {
        continue;
    }

    $amplitude = 1;
    $possibleMin = 1;
    $lengthsToCheck = $lengthsCount;
    $searchLength = $wordLength;
    $min = PHP_INT_MAX;

    while ($min > $possibleMin) {

        if (isset($vocabulary[$searchLength])) {
            $minDistance = findMinDistance($vocabulary[$searchLength], $inputWord, $possibleMin);
            if ($minDistance < $min) {
                $min = $minDistance;
                if ($possibleMin === $min) {
                    break;
                }
            }

            if (!--$lengthsToCheck) {
                break;
            }
        }

        $amplitude = -$amplitude;

        $searchLength = $wordLength + $amplitude;
        $possibleMin = abs($wordLength - $searchLength);

        if (0 < $amplitude) {
            $amplitude++;
        }
    }

    $cache[$inputWord] = $min;

    $totalDistance += $min;
}

if (isset($argv[2])) {
    echo round(memory_get_peak_usage(true)/(1024 * 1024)) . "Mb\n";
    $time = microtime(true) - $start;
    echo $time . "\n";
    if ($time > 3) {
        throw new RuntimeException('Too slow');
    }
}

echo $totalDistance . "\n";

/**
 * @param array $vocabulary
 * @param string $inputWord
 * @param int $possibleMin
 * @return int
 */
function findMinDistance(array $vocabulary, $inputWord, $possibleMin)
{
    $min = PHP_INT_MAX;

    foreach ($vocabulary as $word) {
        $distance = levenshtein($word, $inputWord);
        if ($distance < $min) {
            $min = $distance;
            if ($possibleMin === $min) {
                return $min;
            }
        }
    }

    return $min;
}
