<?php

namespace Local\ServiceProvider;

use Bitrix\Main\Application;
use CMain;
use Exception;
use InvalidArgumentException;
use Local\ServiceProvider\Bundles\BundlesLoader;
use Local\Util\ErrorScreen;
use Local\Util\LoaderContent;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ServiceProvider
 * @package Local\ServiceProvider
 *
 * @since 11.09.2020 Подключение возможности обработки событий HtppKernel через Yaml конфиг.
 * @since 21.09.2020 Исправление ошибки: сервисы, помеченные к автозагрузке не запускались в
 * случае компилированного контейнера.
 * @since 28.09.2020 Доработка.
 * @since 24.10.2020 Загрузка "автономных" бандлов Symfony.
 */
class ServiceProvider
{
    /**
     * @var ContainerBuilder $containerBuilder Контейнер.
     */
    protected static $containerBuilder;

    /**
     * @var ErrorScreen $errorHandler Обработчик ошибок.
     */
    protected $errorHandler;

    /**
     * @var Filesystem $filesystem Файловая система.
     */
    protected $filesystem;

    /** @const string SERVICE_CONFIG_FILE Конфигурация сервисов. */
    protected const SERVICE_CONFIG_FILE = 'local/configs/services.yaml';

    /** @const string COMPILED_CONTAINER_PATH Файл с сскомпилированным контейнером. */
    protected const COMPILED_CONTAINER_FILE = '/container.php';
    /** @const string COMPILED_CONTAINER_DIR Путь к скомпилированному контейнеру. */
    protected const COMPILED_CONTAINER_DIR = '/bitrix/cache/s1';

    /** @var string $filename Yaml файл конфигурации. */
    protected $filename;

    /** @var string|null $projectRoot DOCUMENT_ROOT */
    protected $projectRoot;

    /**
     * @var array $compilerPassesBag Набор Compiler Pass.
     */
    protected $compilerPassesBag = [];

    /**
     * @var string[] $postLoadingPassesBag Пост-обработчики (PostLoadingPass) контейнера.
     */
    protected $postLoadingPassesBag = [];

    /**
     * ServiceProvider constructor.
     *
     * @param string $filename Конфиг.
     *
     * @throws Exception Ошибка инициализации контейнера.
     */
    public function __construct(
        string $filename = self::SERVICE_CONFIG_FILE
    ) {
        // Buggy local fix.
        $_ENV['DEBUG'] = env('DEBUG', false);

        $this->errorHandler = new ErrorScreen(
            new LoaderContent(),
            new CMain()
        );

        $this->filesystem = new Filesystem();

        if (!$filename) {
            $filename = self::SERVICE_CONFIG_FILE;
        }

        if (self::$containerBuilder !== null) {
            return;
        }

        // Кастомные Compile pass & PostLoadingPass.
        $customCompilePassesBag = new CustomCompilePassBag();

        $this->compilerPassesBag = $customCompilePassesBag->getCompilerPassesBag();
        $this->postLoadingPassesBag = $customCompilePassesBag->getPostLoadingPassesBag();

        $this->projectRoot = Application::getDocumentRoot();

        $result = $this->initContainer($filename);
        if (!$result) {
            $this->errorHandler->die('Container DI inititalization error.');
            throw new Exception('Container DI inititalization error.');
        }
    }

    /**
     * Сервис по ключу.
     *
     * @param string $id ID сервиса.
     *
     * @return mixed
     * @throws Exception Ошибки контейнера.
     */
    public function get(string $id)
    {
        return self::$containerBuilder->get($id);
    }

    /**
     * Контейнер.
     *
     * @return ContainerBuilder
     */
    public function container(): ContainerBuilder
    {
        return self::$containerBuilder ?: $this->initContainer($this->filename);
    }

    /**
     * Жестко установить контейнер.
     *
     * @param PsrContainerInterface $container Контейнер.
     *
     * @return void
     */
    public function setContainer(PsrContainerInterface $container): void
    {
        self::$containerBuilder  = $container;
    }

    /**
     * Инициализировать контейнер.
     *
     * @param string $fileName Yaml конфиг.
     *
     * @return mixed
     *
     * @since 28.09.2020 Доработка.
     */
    protected function initContainer(string $fileName)
    {
        // Если в dev режиме, то не компилировать контейнер.
        if (env('DEBUG', false) === true) {
            if (self::$containerBuilder !== null) {
                return self::$containerBuilder;
            }

            // Загрузить, инициализировать и скомпилировать контейнер.
            self::$containerBuilder = $this->initialize($fileName);

            // Исполнить PostLoadingPasses.
            $this->runPostLoadingPasses();

            return self::$containerBuilder;
        }

        // Создать директорию
        // для компилированного контейнера.
        $this->createCacheDirectory();

        /** Путь к скомпилированному контейнеру. */
        $compiledContainerFile = $this->projectRoot . self::COMPILED_CONTAINER_DIR
                                 . self::COMPILED_CONTAINER_FILE;

        $containerConfigCache = new ConfigCache($compiledContainerFile, true);
        // Класс скомпилированного контейнера.
        $classCompiledContainerName = 'MyCachedContainer';

        if (!$containerConfigCache->isFresh()) {
            // Загрузить, инициализировать и скомпилировать контейнер.
            self::$containerBuilder = $this->initialize($fileName);

            // Опция в конфиге - компилировать ли контейнер.
            if (!self::$containerBuilder->getParameter('compile.container')) {
                return self::$containerBuilder;
            }

            $dumper = new PhpDumper(self::$containerBuilder);

            $containerConfigCache->write(
                $dumper->dump(['class' => $classCompiledContainerName]),
                self::$containerBuilder->getResources()
            );
        }

        // Подключение скомпилированного контейнера.
        require_once $compiledContainerFile;

        $classCompiledContainerName = '\\'.$classCompiledContainerName;

        self::$containerBuilder = new $classCompiledContainerName();

        // Исполнить PostLoadingPasses.
        $this->runPostLoadingPasses();

        return self::$containerBuilder;
    }

    /**
     * Загрузить контейнер.
     *
     * @param string $fileName Конфиг.
     *
     * @return boolean|ContainerBuilder
     *
     * @since 11.09.2020 Подключение возможности обработки событий HtppKernel через Yaml конфиг.
     * @since 28.09.2020 Набор стандартных Compile Pass. Кастомные Compiler Pass.
     */
    protected function loadContainer(string $fileName)
    {
        self::$containerBuilder = new ContainerBuilder();

        // Пассы бандлов нужно пускать раньше всех остальных.
        $this->loadSymfonyBundles();

        // Набор стандартных Compile Pass
        $passes = new PassConfig();
        $allPasses = $passes->getPasses();

        foreach ($allPasses as $pass) {
            self::$containerBuilder->addCompilerPass($pass);
        }

        // Локальные compile pass.
        foreach ($this->compilerPassesBag as $compilerPass) {
            $passInitiated = !empty($compilerPass['params']) ? new $compilerPass['pass'](...$compilerPass['params'])
                :
                new $compilerPass['pass'];

            // Фаза. По умолчанию PassConfig::TYPE_BEFORE_OPTIMIZATION
            $phase = !empty($compilerPass['phase']) ? $compilerPass['phase'] : PassConfig::TYPE_BEFORE_OPTIMIZATION;

            self::$containerBuilder->addCompilerPass($passInitiated, $phase);
        }

        // Подключение возможности обработки событий HtppKernel через Yaml конфиг.
        // tags:
        //      - { name: kernel.event_listener, event: kernel.request, method: handle }
        self::$containerBuilder->register('event_dispatcher', EventDispatcher::class);
        self::$containerBuilder->addCompilerPass(new RegisterListenersPass());

        $loader = new YamlFileLoader(self::$containerBuilder, new FileLocator(
            $this->projectRoot
        ));

        try {
            $loader->load($fileName);

            return self::$containerBuilder;
        } catch (Exception $e) {
            $this->errorHandler->die('Ошибка загрузки Symfony Container: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Загрузить, инициализировать и скомпилировать контейнер.
     *
     * @param string $fileName Конфигурационный файл.
     *
     * @return null|ContainerBuilder
     *
     * @since 28.09.2020
     */
    private function initialize(string $fileName): ?ContainerBuilder
    {
        try {
            $this->loadContainer($fileName);
            $this->setDefaultParamsContainer();
            self::$containerBuilder->compile(true);
        } catch (Exception $e) {
            $this->errorHandler->die(
                $e->getMessage().'<br><br><pre>'.$e->getTraceAsString().'</pre>'
            );

            return null;
        }

        return self::$containerBuilder;
    }

    /**
     * Параметры из сервиса kernel (если он существует).
     *
     * @return void
     *
     * @throws Exception Ошибки контейнера.
     */
    private function setDefaultParamsContainer() : void
    {
        // Параметры из сервиса kernel.
        $parameterBag = self::$containerBuilder->getParameterBag();
        try {
            $kernelService = self::$containerBuilder->get('kernel');
        } catch (Exception $e) {
            return;
        }

        if ($parameterBag !== null && $kernelService !== null) {
            $parameterBag->add($kernelService->getKernelParameters());
        }
    }

    /**
     * Если надо создать директорию для компилированного контейнера.
     *
     * @return void
     */
    protected function createCacheDirectory() : void
    {
        if (!$this->filesystem->exists($this->projectRoot . self::COMPILED_CONTAINER_DIR)) {
            try {
                $this->filesystem->mkdir($this->projectRoot . self::COMPILED_CONTAINER_DIR);
            } catch (IOExceptionInterface $exception) {
                $this->errorHandler->die('An error occurred while creating your directory at '.$exception->getPath());
            }
        }
    }

    /**
     * Загрузка "автономных" бандлов Symfony.
     *
     * @return void
     *
     * @throws InvalidArgumentException Не найден класс бандла.
     *
     * @since 24.10.2020
     */
    private function loadSymfonyBundles() : void
    {
        $bundlesLoader = new BundlesLoader(
            self::$containerBuilder
        );

        $bundlesLoader->load(); // Загрузить бандлы.
        $bundlesLoader->registerExtensions(); // Зарегистрировать extensions.
    }

    /**
     * Запустить PostLoadingPasses.
     *
     * @return void
     *
     * @since 28.09.2020
     */
    private function runPostLoadingPasses(): void
    {
        // Отсортировать по приоритету.
        usort($this->postLoadingPassesBag, function ($a, $b) {
            return $a['priority'] > $b['priority'];
        });

        // Запуск.
        foreach ($this->postLoadingPassesBag as $postLoadingPass) {
            if (class_exists($postLoadingPass['pass'])) {
                $class = new $postLoadingPass['pass'];
                $class->action(self::$containerBuilder);
            }
        }
    }

    /**
     * Статический фасад получение контейнера.
     *
     * @param string $method Метод. В данном случае instance().
     * @param mixed  $args   Аргументы (конфигурационный файл).
     *
     * @return mixed | void
     * @throws Exception Ошибки контейнера.
     */
    public static function __callStatic(string $method, $args = null)
    {
        if ($method === 'instance') {
            if (!empty(self::$containerBuilder)) {
                return self::$containerBuilder;
            }

            $self = new self(...$args);

            return $self->container();
        }
    }
}
