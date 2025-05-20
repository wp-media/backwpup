<?php

namespace Liborm85\ComposerVendorCleaner;

use Liborm85\ComposerVendorCleaner\Finder\Glob;

class GlobFilter
{
    const ORDER_NONE = '';
    const ORDER_ASCENDING = 'asc';
    const ORDER_DESCENDING = 'desc';

    /**
     * @var string[]
     */
    private $includeRegex = [];

    /**
     * @var string[]
     */
    private $excludeRegex = [];

    /**
     * @param string $globPattern
     * @param bool $matchCase
     * @return void
     */
    public function addInclude($globPattern, $matchCase = true)
    {
        $this->includeRegex[] = $this->globPatternToRegexPattern($globPattern, $matchCase);
    }

    /**
     * @param string $globPattern
     * @param bool $matchCase
     * @return void
     */
    public function addExclude($globPattern, $matchCase = true)
    {
        $this->excludeRegex[] = $this->globPatternToRegexPattern($globPattern, $matchCase);
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->includeRegex = [];
        $this->excludeRegex = [];
    }

    /**
     * @param string[] $entries
     * @param string $order
     * @return string[]
     */
    public function getFilteredEntries($entries, $order = self::ORDER_NONE)
    {
        if (empty($entries) || empty($this->includeRegex)) {
            return [];
        }

        $includedEntries = $this->filterEntries($this->includeRegex, $entries);

        if (empty($this->excludeRegex)) {
            if ($order) {
                $this->sort($includedEntries, $order);
            }

            return $includedEntries;
        }

        $excludedEntries = $this->filterEntries($this->excludeRegex, $entries);

        $entries = array_values(array_diff($includedEntries, $excludedEntries));

        if ($order) {
            $this->sort($entries, $order);
        }

        return $entries;
    }

    /**
     * @param string[] $regexPatterns
     * @param string[] $entries
     * @return string[]
     */
    private function filterEntries($regexPatterns, $entries)
    {
        $filteredEntries = [];
        foreach ($regexPatterns as $regexPattern) {
            $filteredEntries += preg_grep($regexPattern, $entries);
        }

        return array_unique($filteredEntries);
    }

    /**
     * @param string $globPattern
     * @param bool $matchCase
     * @return string
     */
    private function globPatternToRegexPattern($globPattern, $matchCase = true)
    {
        $regexPattern = Glob::toRegex($globPattern, false);
        if (!$matchCase) {
            $regexPattern .= 'i';
        }

        return $regexPattern;
    }

    /**
     * @param string[] $array
     * @param string $order
     * @return void
     */
    private function sort(&$array, $order)
    {
        if ($order === self::ORDER_ASCENDING) {
            sort($array);
        } elseif ($order === self::ORDER_DESCENDING) {
            rsort($array);
        }
    }
}
