<?php

namespace Droath\ProjectX\Discovery;

use Symfony\Component\Finder\Finder;

/**
 * PHP class discovery.
 */
class PhpClassDiscovery
{
    /**
     * The search depth.
     *
     * @var integer
     */
    protected $searchDepth = 0;

    /**
     * The search matches.
     *
     * @var array
     */
    protected $searchMatches = [];

    /**
     * The search locations.
     *
     * @var array
     */
    protected $searchLocations = [];

    /**
     * The search pattern.
     *
     * @var string
     */
    protected $searchPattern = '*.php';

    /**
     * Load discovered classes flag.
     *
     * @var bool
     */
    protected $loadClasses = false;

    /**
     * Load discovered classes.
     *
     * @return self
     */
    public function loadClasses()
    {
        $this->loadClasses = true;

        return $this;
    }

    /**
     * Set search depth.
     *
     * @param string $depth
     */
    public function setSearchDepth($depth)
    {
        $this->searchDepth = $depth;

        return $this;
    }

    /**
     * Set the search pattern.
     *
     * @param string $pattern
     */
    public function setSearchPattern($pattern)
    {
        $this->searchPattern = $pattern;

        return $this;
    }

    /**
     * Add search location.
     *
     * @throws \InvalidArgumentException
     *
     * @param string $location
     *   The location path on which to conduct the search.
     */
    public function addSearchLocation($location)
    {
        if (!file_exists($location)) {
            throw new \InvalidArgumentException(
                sprintf("The location path %s is not valid.", $location)
            );
        }

        $this->searchLocations[] = $location;

        return $this;
    }

    /**
     * Add search locations
     *
     * @param array $locations
     *   An array of locations on which to conduct the search.
     */
    public function addSearchLocations(array $locations)
    {
        foreach ($locations as $location) {
            $this->addSearchLocation($location);
        }

        return $this;
    }

    /**
     * Match class namespace
     *
     * @param string $namespace
     *   The class fully qualified namespace.
     *
     * @return self
     */
    public function matchClass($namespace)
    {
        $this->searchMatches['class'][] = $namespace;

        return $this;
    }

    /**
     * Match class namespaces.
     *
     * @param array $classes
     *   An array of class fully qualified namespaces.
     *
     * @return self
     */
    public function matchClasses(array $namespaces)
    {
        $this->searchMatches['class'] = $namespaces;

        return $this;
    }

    /**
     * Match class extends namespace.
     *
     * @param string $namespace
     *   The class extends fully qualified namespace.
     *
     * @return self
     */
    public function matchExtend($namespace)
    {
        $this->searchMatches['extends'][] = $namespace;

        return $this;
    }

    /**
     * Match class extends namespaces.
     *
     * @param array $namespaces
     *   An array of class extends namespaces.
     *
     * @return self
     */
    public function matchExtends(array $namespaces)
    {
        $this->searchMatches['extends'] = $namespaces;

        return $this;
    }

    /**
     * Match class implements namespace.
     *
     * @param array $namespaces
     *   The class implements fully qualified namespace.
     *
     * @return self
     */
    public function matchImplement($namespace)
    {
        $this->searchMatches['implements'][] = $namespace;

        return $this;
    }

    /**
     * Match class implements namespaces.
     *
     * @param array $namespaces
     *   An array of class implements fully qualified namespaces.
     *
     * @return self
     */
    public function matchImplements(array $namespaces)
    {
        $this->searchMatches['implements'] = $namespaces;

        return $this;
    }

    /**
     * Discover PHP classed based on searching criteria.
     *
     * @return array
     *   An array of class namespaces keyed by the PHP file path.
     */
    public function discover()
    {
        $classes = [];

        foreach ($this->doFileSearch() as $file) {
            $classinfo = $this->parse($file);
            $classname = $classinfo['class'];
            $classpath = $file->getRealPath() ?: "$classname.php";

            if (!empty($this->searchMatches)) {
                foreach ($this->searchMatches as $type => $value) {
                    if (!isset($classinfo[$type])) {
                        continue;
                    }
                    $instances = !is_array($classinfo[$type])
                        ? [$classinfo[$type]]
                        : $classinfo[$type];

                    $matches = array_intersect($value, $instances);

                    if (empty($matches)) {
                        continue;
                    }

                    $classes[$classpath] = $classname;
                }
            } else {
                $classes[$classpath] = $classname;
            }
        }

        $this->requireClasses($classes);

        return $classes;
    }


    /**
     * Perform the file search on defined search locations.
     *
     * @return \Symfony\Component\Finder\Finder
     *   The Symfony finder object.
     */
    protected function doFileSearch()
    {
        if (empty($this->searchLocations)) {
            throw new \RuntimeException(
                'No search locations have been defined.'
            );
        }

        return (new Finder())
            ->name($this->searchPattern)
            ->in($this->searchLocations)
            ->depth($this->searchDepth)
            ->files();
    }

    /**
     * Require classes that don't already exist.
     *
     * @param array $classes
     *   An array of classes to require.
     */
    protected function requireClasses(array $classes)
    {
        if ($this->loadClasses) {
            foreach ($classes as $classpath => $classname) {
                if (class_exists($classname)) {
                    continue;
                }

                require_once "$classpath";
            }
        }
    }

    /**
     * Parse PHP contents and extract the tokens.
     *
     * @param \SplFileInfo $file
     *   The file object on which to parse.
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     *   An array of extracted PHP tokens.
     */
    protected function parse(\SplFileInfo $file)
    {
        if ($file->getExtension() !== 'php') {
            throw new \InvalidArgumentException(
                'Invalid file type.'
            );
        }
        $info = [];
        $tokens = token_get_all($file->getContents());

        for ($i = 0; $i < count($tokens); ++$i) {
            $token = is_array($tokens[$i])
                ? $tokens[$i][0]
                : $tokens[$i];

            switch ($token) {
                case T_NAMESPACE:
                    $info[$tokens[$i][1]] = $this->getTokenValue(
                        $tokens,
                        [';'],
                        $i
                    );
                    continue;

                case T_USE:
                    $info[$tokens[$i][1]][] = $this->getTokenValue(
                        $tokens,
                        [';', '{', T_AS],
                        $i
                    );
                    continue;

                case T_CLASS:
                    $classname = $this->getTokenValue(
                        $tokens,
                        [T_EXTENDS, T_IMPLEMENTS, '{'],
                        $i
                    );

                    // Resolve the class fully qualified namespace.
                    $info[$tokens[$i][1]] = $this->resolveNamespace(
                        $info,
                        $classname
                    );

                    continue;

                case T_EXTENDS:
                    $classname = $this->getTokenValue(
                        $tokens,
                        [T_IMPLEMENTS, '{'],
                        $i
                    );

                    // Resolve the extends class fully qualified namespace.
                    $info[$tokens[$i][1]] = $this->resolveNamespace(
                        $info,
                        $classname
                    );

                    continue;

                case T_IMPLEMENTS:
                    $interface = $this->getTokenValue(
                        $tokens,
                        ['{'],
                        $i
                    );

                    // Resolve the interface fully qualified namespace.
                    $info[$tokens[$i][1]][] = $this->resolveNamespace(
                        $info,
                        $interface
                    );

                    continue;
            }
        }

        return $info;
    }

    /**
     * Resolve the classname to it's fully qualified namespace.
     *
     * @param array $info
     *   The classname token information.
     * @param string $classname
     *   The classname on which to resolve the FQN.
     *
     * @return string
     *   The fully qualified namespace for the given classname.
     */
    protected function resolveNamespace(array $token_info, $classname)
    {
        // Resolve the namespace based on the use directive.
        if (isset($token_info['use'])
            && !empty($token_info['use'])
            && strpos($classname, DIRECTORY_SEPARATOR) === false) {
            foreach ($token_info['use'] as $use) {
                if (strpos($use, $classname) === false) {
                    continue;
                }

                return $use;
            }
        }

        // Prefix the classname with the class namespace if defined.
        if (isset($token_info['namespace'])) {
            $classname = $token_info['namespace'] . "\\$classname";
        }

        return $classname;
    }

    /**
     * Get the PHP token value.
     *
     * @param array $tokens
     *   An array of PHP tokens.
     * @param array $endings
     *   An array of endings that should be searched.
     * @param int $iteration
     *   The token iteration count.
     * @param bool $skip_whitespace
     *   A flag to determine if whitespace should be skipped.
     *
     * @return string
     *   The PHP token content value.
     */
    protected function getTokenValue(array $tokens, array $endings, $iteration, $skip_whitespace = true)
    {
        $value = null;
        $count = count($tokens);

        for ($i = $iteration + 1; $i < $count; ++$i) {
            $token = is_array($tokens[$i])
                ? $tokens[$i][0]
                : trim($tokens[$i]);

            if ($token === T_WHITESPACE
                && $skip_whitespace) {
                continue;
            }

            if (in_array($token, $endings)) {
                break;
            }

            $value .= isset($tokens[$i][1]) ? $tokens[$i][1] : $token;
        }

        return $value;
    }
}
