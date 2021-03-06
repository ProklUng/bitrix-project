<?php

namespace Local\Bundles\RequestLogBundle\Service;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ResponseLogger
 * @package Local\Bundles\RequestLogBundle\Service
 */
class ResponseLogger
{
    private const FILENAME_SEPARATOR = '__';
    private const FILENAME_QS_SEPARATOR = '--';

    /**
     * @var Filesystem $filesystem Файловая система.
     */
    private $filesystem;

    /**
     * @var string $mocksDir Директория с моками.
     */
    private $mocksDir;

    /**
     * @var boolean $hashQueryParams
     */
    private $hashQueryParams;

    /**
     * @var boolean $useIndexedAssociativeArray
     */
    private $useIndexedAssociativeArray;

    /**
     * ResponseLogger constructor.
     *
     * @param string  $mocksDir                   Директория с моками.
     * @param boolean $hashQueryParams
     * @param boolean $useIndexedAssociativeArray
     */
    public function __construct(string $mocksDir, $hashQueryParams = false, $useIndexedAssociativeArray = false)
    {
        $this->mocksDir = rtrim($mocksDir, '/').'/';

        $this->hashQueryParams = (bool) $hashQueryParams;
        $this->useIndexedAssociativeArray = (bool) $useIndexedAssociativeArray;
        $this->filesystem = new Filesystem();
    }

    /**
     * Empty and recreate the mocks dir.
     *
     * @return void
     */
    public function clearMocksDir() : void
    {
        try {
            $this->filesystem->remove($this->mocksDir);
        } catch (IOException $e) {
            return;
        }

        $this->filesystem->mkdir($this->mocksDir);
    }

    /**
     * Copy all existing mocks onto a target directory.
     *
     * @param string $targetDir Директория-назначение.
     *
     * @return void
     */
    public function dumpMocksTo(string $targetDir) : void
    {
        if (!$this->filesystem->exists($this->mocksDir)) {
            return;
        }

        $this->filesystem->mirror($this->mocksDir, $targetDir, null, ['override' => true, 'delete' => true]);
    }

    /**
     * Creates a json log file containing the request and the response contents.
     *
     * @param Request  $request  Request.
     * @param Response $response Response.
     *
     * @return string The new mock file path
     */
    public function logResponse(Request $request, Response $response)
    {
        $filename = $this->getFilePathByRequest($request);
        $requestJsonContent = json_decode($request->getContent(), true);
        $responseJsonContent = json_decode($response->getContent(), true);

        $dumpFileContent = [
            'request' => [
                'uri' => $request->getRequestUri(),
                'method' => $request->getMethod(),
                'parameters' => $request->request->all(),
                'content' => $requestJsonContent ?: $request->getContent(),
            ],
            'response' => [
                'statusCode' => $response->getStatusCode(),
                'contentType' => $response->headers->get('Content-Type'),
                'content' => $responseJsonContent ?: $response->getContent(),
            ],
        ];

        $this->filesystem->dumpFile($this->mocksDir.$filename, self::jsonEncode($dumpFileContent, true));

        return $this->mocksDir.$filename;
    }

    /**
     * Creates a filename string from a request object, with the following schema :
     * `uri/segments?query=string&others#METHOD-md5Content-md5JsonParams.json`.
     *
     * Examples :
     *  GET http://domain.name/categories => /categories/GET__.json
     *  GET http://domain.name/categories/1 => /categories/GET__1.json
     *  GET http://domain.name/categories/1/articles => /categories/1/GET__articles.json
     *  GET http://domain.name/categories/1/articles?order[title]=desc => /categories/1/GET__articles--order[title]=desc.json
     *  POST http://domain.name/categories with content => /categories/POST____a142b.json
     *
     * @param Request $request Request.
     *
     * @return string
     */
    public function getFilePathByRequest(Request $request)
    {
        $requestPathInfo = trim($request->getPathInfo(), '/');
        $requestMethod = $request->getMethod();
        $requestContent = $request->getContent();
        $requestQueryParameters = $request->query->all();
        $requestParameters = $request->request->all();

        $filename = $requestPathInfo;

        // Store base endpoint calls with its children
        if ('' !== $filename && 0 === substr_count($filename, '/')) {
            $filename .= '/';
        }

        // Add query parameters
        if (count($requestQueryParameters)) {
            $requestQueryParametersString = self::httpBuildQuery(self::sortArray($requestQueryParameters));

            // Url encode filename if needed
            if ($this->hashQueryParams) {
                $requestQueryParametersString = $this->generateFilenameHash($requestQueryParametersString);
            }

            $filename .= self::FILENAME_QS_SEPARATOR.$requestQueryParametersString;
        }

        // Add request content hash
        if ($requestContent) {
            // If JSON, sort data
            $jsonContent = json_decode($requestContent, true);
            if (null !== $jsonContent) {
                $filename .= self::FILENAME_SEPARATOR.$this->generateFilenameHash(self::jsonEncode(self::sortArray($jsonContent)));
            } else {
                $filename .= self::FILENAME_SEPARATOR.$this->generateFilenameHash($requestContent);
            }
        }

        // Add request parameters hash
        if ($requestParameters) {
            $filename .= self::FILENAME_SEPARATOR.$this->generateFilenameHash(self::jsonEncode(self::sortArray($requestParameters)));
        }

        // Add HTTP method
        $filenameArray = explode('/', $filename);

        $filenameArray[count($filenameArray) - 1] = $requestMethod.self::FILENAME_SEPARATOR.end($filenameArray);
        $filename = implode($filenameArray, '/');

        // Add extension
        $filename .= '.json';

        return $filename;
    }

    /**
     * @param string $mocksDir Относительный путь к директории с моками.
     *
     * @return void
     */
    public function setMocksDir(string $mocksDir): void
    {
        $this->mocksDir = $mocksDir;
    }

    /**
     * Json encodes and returns a string.
     *
     * @param array   $data   Данные.
     * @param boolean $pretty Красивый json.
     *
     * @return string
     */
    private function jsonEncode(array $data, $pretty = false) : string
    {
        $options = JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES;

        if ($pretty) {
            $options += JSON_PRETTY_PRINT;
        }

        return (string)json_encode($data, $options);
    }

    /**
     * Transforms an array onto query string.
     * We are not using http_build_query as this function transforms `foo=[bar]` onto `foo[0]=bar`, and we want `foo[]=bar`.
     *
     * @param mixed   $data       Данные.
     * @param string  $keyPrefix  Префикс.
     * @param boolean $isChildren
     *
     * @return string
     */
    private function httpBuildQuery($data, string $keyPrefix = '', $isChildren = false)
    {
        if (!is_array($data)) {
            return '';
        }

        $result = [];
        $isNonAssociativeArray = self::isNonAssociativeArray($data);

        foreach ($data as $key => $value) {
            if ($isChildren) {
                $key = $isNonAssociativeArray && !$this->useIndexedAssociativeArray ? $keyPrefix.'[]' : $keyPrefix."[$key]";
            } elseif (is_int($key)) {
                $key = $keyPrefix.$key;
            }

            if (is_array($value) || is_object($value)) {
                $result[] = $this->httpBuildQuery($value, $key, true);
                continue;
            }

            $result[] = urlencode($key).'='.urlencode($value);
        }

        return implode('&', $result);
    }

    /**
     * Returns a hash from a string.
     *
     * @param string $data Данные.
     *
     * @return string
     */
    private function generateFilenameHash(string $data) : string
    {
        return substr(sha1($data), 0, 5);
    }

    /**
     * Sorts an associative array by key and a flat array by values.
     *
     * @param mixed $data Данные.
     *
     * @return mixed
     */
    private static function sortArray($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        if (self::isNonAssociativeArray($data)) {
            sort($data);
        } else {
            ksort($data);
        }

        foreach ($data as $k => $v) {
            $data[$k] = self::sortArray($data[$k]);
        }

        return $data;
    }

    /**
     * Returns true if the array is detected as non-associative.
     *
     * @param mixed $data Данные.
     *
     * @return boolean
     */
    private static function isNonAssociativeArray($data)
    {
        return is_array($data) && array_keys($data) === range(0, count($data) - 1);
    }
}
