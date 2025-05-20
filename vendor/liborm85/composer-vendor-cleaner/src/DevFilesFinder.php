<?php

namespace Liborm85\ComposerVendorCleaner;

class DevFilesFinder
{
    /**
     * @var string[][]
     */
    private $devFiles;

    /**
     * @var bool
     */
    private $matchCase;

    /**
     * @param string[][] $devFiles
     * @param bool $matchCase
     */
    public function __construct($devFiles, $matchCase)
    {
        $this->devFiles = $devFiles;
        $this->matchCase = $matchCase;
    }

    /**
     * @param string $packageName
     * @return string[]
     */
    public function getGlobPatternsForPackage($packageName)
    {
        $globPatterns = [];
        foreach ($this->devFiles as $packageGlob => $devFile) {
            if ($this->isGlobPatternForPackage($packageName, $packageGlob)) {
                $globPatterns = array_merge($globPatterns, $devFile);
            }
        }

        return $globPatterns;
    }

    /**
     * @param string $packageName
     * @param string $packageGlob
     * @return bool
     */
    public function isGlobPatternForPackage($packageName, $packageGlob)
    {
        $globFilter = new GlobFilter();
        $packageGlobPattern = rtrim($packageGlob, '/');
        if ($packageGlobPattern === '') {
            $globFilter->addInclude('*', $this->matchCase);
            $globFilter->addInclude('*/*', $this->matchCase);
        } elseif (strpos($packageGlobPattern, '/') === false) {
            $globFilter->addInclude($packageGlobPattern, $this->matchCase);
            $globFilter->addInclude($packageGlobPattern . '/*', $this->matchCase);
        } else {
            $globFilter->addInclude($packageGlobPattern, $this->matchCase);
        }

        return !empty($globFilter->getFilteredEntries([$packageName]));
    }

    /**
     * @param string[] $entries
     * @param string[] $globPatterns
     * @return string[]
     */
    public function getFilteredEntries($entries, $globPatterns)
    {
        $globPatterns = $this->buildGlobPatternForFilter($globPatterns);

        $globFilter = new GlobFilter();
        foreach ($globPatterns as $globPattern) {
            if (substr($globPattern, 0, 1) === '!') {
                $globFilter->addExclude(substr($globPattern, 1), $this->matchCase);
            } else {
                $globFilter->addInclude($globPattern, $this->matchCase);
            }
        }

        return $globFilter->getFilteredEntries($entries, GlobFilter::ORDER_DESCENDING);
    }

    /**
     * @param string[] $patterns
     * @return string[]
     */
    private function buildGlobPatternForFilter($patterns)
    {
        $globPatterns = [];
        foreach ($patterns as $pattern) {
            $filePatternPrefix = '';
            $filePatternSuffix = '';
            $isExcludePattern = false;
            if (substr($pattern, 0, 1) === '!') {
                $isExcludePattern = true;
                $pattern = substr($pattern, 1);
            }

            if (substr($pattern, 0, 1) !== '/') {
                $filePatternPrefix = '/**/';
            }

            if (substr($pattern, -1) === '/') {
                $filePatternSuffix = '**';
            }

            $globPattern = '/' . ltrim($filePatternPrefix . $pattern . $filePatternSuffix, '/');

            if ($isExcludePattern) {
                $globPattern = '!' . $globPattern;
            }

            $globPatterns[] = $globPattern;
        }

        return $globPatterns;
    }

}
