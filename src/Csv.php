<?php

namespace Uniforcemusic\Csv;

class Csv
{
    public const DEFAULT_DELIMITER = ',';

    protected string $delimiter = ',';
    protected array $keys = [];
    protected array $rows = [];

    /* Static init methods */
    public static function parseFromFile(string $filePath, string $delimiter = ','): self
    {
        if (!file_exists($filePath)) {
            throw new \Exception('Csv file path is invalid');
        }

        $csvString = file_get_contents($filePath);

        return new static($csvString, $delimiter);
    }

    public static function parseFromString(string $string, string $delimiter = ','): self
    {
        return new static($string, $delimiter);
    }

    /* Getters and setters */
    public function __construct(string $string, string $delimiter = null)
    {
        $newLine = $this->detectNewline($string);
        $delimiter = $delimiter ?? $this->detectDelimiter($string, $newLine);

        $lines = explode($newLine, $string);
        $keys = $this->split($lines[0], $delimiter);
        $rows = (count($lines) > 1) ? array_slice($lines, 1) : [];

        foreach ($rows as $index => $values) {
            $rows[$index] = $this->matchValuesToKeys($keys, $this->split($values, $delimiter));
        }

        $this->delimiter = $delimiter;
        $this->keys = $keys;
        $this->rows = $rows;
    }

    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    public function setDelimiter($delimiter): self
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    public function getKeys(): array
    {
        return $this->keys;
    }

    public function setKeys(array $keys): self
    {
        $this->keys = $keys;

        return $this;
        return $this;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function setRows(array $rows): self
    {
        $this->rows = array_values($rows);

        return $this;
    }

    public function getString(): string
    {
        $keys = $this->stringifyKeys($this->delimiter);
        $rows = $this->stringifyRows($this->delimiter);

        if ($this->delimiter !== static::DEFAULT_DELIMITER) {
            return $this->joinStrings([
                sprintf('sep=%s', $this->delimiter),
                $keys,
                $rows
            ]);
        }

        return $this->joinStrings([
            $keys,
            $rows
        ]);
    }

    public function writeToFile(string $filePath): self
    {
        file_put_contents($filePath, $this->getString());

        return $this;
    }

    /* Deserialize methods */
    protected function matchValuesToKeys(array $keys, array $values): array
    {
        $array = [];

        foreach ($keys as $index => $key) {
            if (isset($values[$index])) {
                $array[$key] = $values[$index];
            } else {
                $array[$key] = '';
            }
        }

        return $array;
    }

    protected function detectNewline(string $string): string
    {
        if (strpos($string, "\r\n") !== false) {
            return "\r\n";
        } else {
            return "\n";
        }
    }

    protected function detectDelimiter(string $string, string $newLine): string
    {
        $lines = $this->split($string, $newLine);

        if (strpos($lines[0], "sep=") !== false) {
            preg_match('/sep=(.[^\)]*)/', $lines[0], $matches);

            return $matches[1];
        }

        return $this->delimiter;
    }

    protected function split(string $string, string $separator): array
    {
        return str_getcsv($string, $separator);
    }

    /* Serialize methods */
    protected function stringifyKeys(string $delimiter): string
    {
        return $this->join($this->keys, $delimiter);
    }

    protected function stringifyRows(string $delimiter): string
    {
        $rowStrings = [];

        foreach ($this->rows as $values) {
            $rowStrings[] = $this->join(
                $this->matchKeyOrder($this->keys, $values),
                $delimiter
            );
        }

        return $this->joinStrings($rowStrings);
    }

    protected function matchKeyOrder(array $keys, array $values): array
    {
        $sortedValues = [];

        foreach ($values as $valueKey => $value) {
            if (!in_array($valueKey, $keys)) {
                continue;
            }

            $keyIndex = array_search($valueKey, $keys);

            $sortedValues[$keyIndex] = [$valueKey, $value];
        }

        ksort($sortedValues);

        $sortedKeyValuePairs = [];

        foreach ($sortedValues as $valuePair) {
            $sortedKeyValuePairs[$valuePair[0]] = $valuePair[1];
        }

        return $sortedKeyValuePairs;
    }

    protected function joinStrings(array $strings): string
    {
        return implode(PHP_EOL, $strings);
    }

    protected function join(array $items, string $glue): string
    {
        /* Serialize items with the separator in their field */
        $serializedItems = array_map(function ($item) use ($glue) {
            $serializedItem = sprintf('"%s"', str_replace('"', '""', $item));

            return (strpos($item, $glue) !== false) ? $serializedItem : $item;
        }, $items);

        return implode($glue, $serializedItems);
    }

    /* Data manipulation methods */
    public function filter($callback = 'empty'): self
    {
        $rows = $this->rows;

        foreach ($rows as $index => $values) {
            if (!$callback($values, $index)) {
                unset($rows[$index]);
            }
        }

        $this->rows = array_values($rows);

        return $this;
    }

    public function map(callable $callback): self
    {
        $rows = $this->rows;

        foreach ($rows as $index => $values) {
            $rows[$index] = $callback($values, $index);
        }

        $this->rows = array_values($rows);

        return $this;
    }

    public function add(array $values): self
    {
        $this->rows[] = $this->matchKeyOrder($this->keys, $values);

        return $this;
    }
}
