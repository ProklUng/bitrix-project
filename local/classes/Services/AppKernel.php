<?php

namespace Local\Services;

use Bitrix\Main\Application;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class AppKernel
 * @package Local\Services
 *
 * @since 08.10.2020 kernel.site.host
 * @since 22.10.2020 kernel.schema
 * @since 25.10.2020 Наследование от HttpKernel.
 */
class AppKernel extends Kernel
{
    /**
     * @var string $environment Окружение.
     */
    protected $environment;

    /**
     * @var string $debug Отладка? Оно же служит для определения типа окружения.
     */
    protected $debug;

    /**
     * @var string $projectDir DOCUMENT_ROOT.
     */
    private $projectDir;

    /**
     * AppKernel constructor.
     *
     * @param string $debug Отладка? Это же определяет тип окружения.
     */
    public function __construct(string $debug)
    {
        $this->debug = (bool)$debug;
        $this->environment = $this->debug ? 'dev' : 'prod';

        parent::__construct($this->environment, $this->debug);
    }

    /**
     * Директория кэша.
     *
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/bitrix/cache/';
    }

    /**
     * Gets the application root dir.
     *
     * @return string The project root dir
     */
    public function getProjectDir(): string
    {
        if ($this->projectDir === null) {
            $this->projectDir = Application::getDocumentRoot();
        }

        return $this->projectDir;
    }

    /**
     * Параметры ядра. Пути, debug & etc.
     *
     * @return array
     */
    public function getKernelParameters(): array
    {
        return [
            'kernel.project_dir' => realpath($this->getProjectDir()) ?: $this->getProjectDir(),
            'kernel.environment' => $this->environment,
            'kernel.debug' => $this->debug,
            'kernel.cache_dir' => realpath($this->getCacheDir()),
            'kernel.http.host' => $_SERVER['HTTP_HOST'],
            'kernel.site.host' => $this->getSiteHost(),
            'kernel.schema' => $this->getSchema()
        ];
    }

    /**
     * Хост сайта.
     *
     * @return string
     *
     * @since 08.10.2020
     */
    private function getSiteHost() : string
    {
        return $this->getSchema() . $_SERVER['HTTP_HOST'];
    }

    /**
     * Schema http or https.
     *
     * @return string
     *
     * @since 22.10.2020
     */
    private function getSchema() : string
    {
        return (!empty($_SERVER['HTTPS'])
            && ($_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] === 443)
        ) ? 'https://' : 'http://';
    }

    /**
     * @inheritDoc
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }

    /**
     * Регистрация бандла.
     *
     * @return iterable
     */
    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir() . '/local/configs/bundles.php';

        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }
}
