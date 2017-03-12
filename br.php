<?php

$start = microtime(true);

$accuracy = 100;
if (isset($argv[2])) {
    $accuracy = (int)$argv[2];
    if (1 > $accuracy || 100 < $accuracy) {
        throw new InvalidArgumentException('Accuracy must be between 1 and 100');
    }
}

$accuracy /= 100;


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
}

unset($vocabulary[0]);

$file = null;

$totalDistance = 0;
$lengthsCount = count($vocabulary);

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

    $deviation = $wordLength - $wordLength * $accuracy;

    $amplitude = 1;
    $possibleMin = 1;
    $satisfiableMin = $possibleMin + $deviation;
    $lengthsToCheck = $lengthsCount;
    $searchLength = $wordLength;
    $min = PHP_INT_MAX;

    while ($min > $satisfiableMin) {


        if (isset($vocabulary[$searchLength])) {
            $minDistance = findMinDistance($vocabulary[$searchLength], $inputWord, $satisfiableMin);
            if ($minDistance < $min) {
                $min = $minDistance;
                if ($min <= $satisfiableMin) {
                    break;
                }
            }

            if (!--$lengthsToCheck) {
                break;
            }
        }

        $amplitude = -$amplitude;

        $searchLength = $wordLength + $amplitude;
        $possibleMin = abs($wordLength - $searchLength) + $deviation;
        $satisfiableMin = $possibleMin + $deviation;

        if (0 < $amplitude) {
            $amplitude++;
        }
    }

    $distance = $min;

    if ($deviation > 0 && $possibleMin < $min) {
        $avg = random_int($possibleMin * 100, $min * 100) / 100;
        $distance = $possibleMin + ($avg - $possibleMin) * $accuracy;
    }

    $cache[$inputWord] = $distance;

    $totalDistance += $distance;
}

$totalDistance = round($totalDistance);

echo $totalDistance . "\n";

/**
 * @param array $vocabulary
 * @param string $inputWord
 * @param int $satisfiableMin
 * @return int
 */
function findMinDistance(array $vocabulary, $inputWord, $satisfiableMin)
{
    $min = PHP_INT_MAX;

    foreach ($vocabulary as $word) {
        $distance = levenshtein($word, $inputWord);
        if ($distance < $min) {
            $min = $distance;
            if ($min <= $satisfiableMin) {
                return $min;
            }
        }
    }

    return $min;
}
