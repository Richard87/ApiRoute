<?php


namespace Richard87\ApiRoute\Service;


use Richard87\ApiRoute\Attributes\ApiRoute;

class FindClassDescriptors
{
    public function __construct(
        private ClassDescriptor $classDescriptor
    ){}

    /**
     * @param string|array $path
     * @return ApiRoute[]
     */
    public function findAttributes(string|array $path): array
    {
        if (is_array($path)) {
            $results = array_map([$this,'findAttributesInPath'], $path);
            return array_merge(...$results);
        }

        return $this->findAttributesInPath($path);
    }

    /**
     * @param string $path
     * @return ApiRoute[]
     */
    private function findAttributesInPath(string $path): array
    {
        $apiRoutes = [];

        $it    = new \RecursiveDirectoryIterator($path, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);

        /** @var \SplFileInfo $fileInfo */
        foreach ($files as $path => $fileInfo) {
            if ($fileInfo->isDir() || $fileInfo->getExtension() !== "php") {
                continue;
            }

            $class = $this->findClass($path);
            if (!$class) {
                continue;
            }

            $apiRoutes[] = $this->classDescriptor->mapClass($class);
        }
        return array_merge(...$apiRoutes);
    }

    /**
     * Returns the full class name for the first class in the file.
     *
     * Copied from Symfony
     *
     * @param string $file
     * @return string|null
     */
    protected function findClass(string $file): ?string
    {
        $class = false;
        $namespace = false;
        $tokens = token_get_all(file_get_contents($file));

        if (1 === \count($tokens) && \T_INLINE_HTML === $tokens[0][0]) {
            throw new \InvalidArgumentException(sprintf('The file "%s" does not contain PHP code. Did you forgot to add the "<?php" start tag at the beginning of the file?', $file));
        }

        $nsTokens = [\T_NS_SEPARATOR => true, \T_STRING => true];
        if (\defined('T_NAME_QUALIFIED')) {
            $nsTokens[T_NAME_QUALIFIED] = true;
        }

        for ($i = 0; isset($tokens[$i]); ++$i) {
            $token = $tokens[$i];

            if (!isset($token[1])) {
                continue;
            }

            if (true === $class && \T_STRING === $token[0]) {
                return $namespace.'\\'.$token[1];
            }

            if (true === $namespace && isset($nsTokens[$token[0]])) {
                $namespace = $token[1];
                while (isset($tokens[++$i][1], $nsTokens[$tokens[$i][0]])) {
                    $namespace .= $tokens[$i][1];
                }
                $token = $tokens[$i];
            }

            if (\T_CLASS === $token[0]) {
                // Skip usage of ::class constant and anonymous classes
                $skipClassToken = false;
                for ($j = $i - 1; $j > 0; --$j) {
                    if (!isset($tokens[$j][1])) {
                        break;
                    }

                    if (\T_DOUBLE_COLON === $tokens[$j][0] || \T_NEW === $tokens[$j][0]) {
                        $skipClassToken = true;
                        break;
                    }

                    if (!\in_array($tokens[$j][0], [\T_WHITESPACE, \T_DOC_COMMENT, \T_COMMENT], true)) {
                        break;
                    }
                }

                if (!$skipClassToken) {
                    $class = true;
                }
            }

            if (\T_NAMESPACE === $token[0]) {
                $namespace = true;
            }
        }

        return null;
    }
}