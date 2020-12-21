<?php

namespace Local\ServiceProvider;

use Bitrix\Main\Application;
use CMain;
use Exception;
use InvalidArgumentException;
use Local\ServiceProvider\Bundles\BundlesLoader;
use Local\ServiceProvider\Extra\DoctrineDbalExtension;
use Local\ServiceProvider\Extra\ExtraFeature;
use Local\Services\AppKernel;
use Local\Util\ErrorScreen;
use Local\Util\LoaderContent;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\DependencyInjection\ControllerArgumentValueResolverPass;
use Symfony\Component\HttpKernel\DependencyInjection\RegisterControllerArgumentLocatorsPass;
use Symfony\Component\HttpKernel\DependencyInjection\RemoveEmptyControllerArgumentLocatorsPass;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyInfo\DependencyInjection\PropertyInfoPass;
use Symfony\Component\Routing\DependencyInjection\RoutingResolverPass;
use Symfony\Component\Serializer\DependencyInjection\SerializerPass;

/**
 * Class ServiceProvider
 * @package Local\ServiceProvider
 *
 * @since 11.09.2020 Подключение возможности обработки событий HtppKernel через Yaml конфиг.
 * @since 21.09.2020 Исправление ошибки: сервисы, помеченные к автозагрузке не запускались в
 * случае компилированного контейнера.
 * @since 28.09.2020 Доработка.
 * @since 24.10.2020 Загрузка "автономных" бандлов Symfony.
 * @since 08.11.2020 Устранение ошибки, связанной с многократной загрузкой конфигурации бандлов.
 * @since 12.11.2020 Значение debug передаются снаружи. Рефакторинг.
 * @since 14.11.2020 Загрузка конфигураций бандлов.
 * @since 12.12.2020 Полноценный контейнер в kernel.
 * @since 12.12.2020 DoctrineDbalExtension.
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

    /**
     * @var BundlesLoader $bundlesLoader Загрузчик бандлов.
     */
    protected $bundlesLoader;

    /**
     * @var ExtraFeature $frameworkExtension Из FrameworkExtension.
     */
    protected $frameworkExtension;

    /**
     * @var DoctrineDbalExtension $doctrineDbalExtension Doctrine DBAL extension.
     */
    protected $doctrineDbalExtension;

    /** @const string SERVICE_CONFIG_FILE Конфигурация сервисов. */
    protected const SERVICE_CONFIG_FILE = 'local/configs/services.yaml';

    /** @const string COMPILED_CONTAINER_PATH Файл с сскомпилированным контейнером. */
    protected const COMPILED_CONTAINER_FILE = '/container.php';
    /** @const string COMPILED_CONTAINER_DIR Путь к скомпилированному контейнеру. */
    protected const COMPILED_CONTAINER_DIR = '/bitrix/cache/s1';

    /** @const string CONFIG_EXTS Расширения конфигурационных файлов. */
    protected const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /** @var string $filename Yaml файл конфигурации. */
    protected $filename;

    /** @var string|null $projectRoot DOCUMENT_ROOT */
    protected $projectRoot;

    /**
     * @var array Конфигурация бандлов.
     */
    private $bundles = [];

    /**
     * @var array $compilerPassesBag Набор Compiler Pass.
     */
    protected $compilerPassesBag = [];

    /**
     * @var string[] $postLoadingPassesBag Пост-обработчики (PostLoadingPass) контейнера.
     */
    protected $postLoadingPassesBag = [];

    /** @var string $environment Среда */
    protected $environment;

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
        $this->environment = $_ENV['DEBUG'] ? 'dev' : 'prod';

        $this->errorHandler = new ErrorScreen(
            new LoaderContent(),
            new CMain()
        );

        $this->filesystem = new Filesystem();
        $this->frameworkExtension = new ExtraFeature();
        $this->doctrineDbalExtension = new DoctrineDbalExtension();

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
     * @throws Exception Ошибки контейнера.
     *
     * @since 28.09.2020 Набор стандартных Compile Pass. Кастомные Compiler Pass.
     * @since 11.09.2020 Подключение возможности обработки событий HtppKernel через Yaml конфиг.
     */
    protected function loadContainer(string $fileName)
    {
        self::$containerBuilder = new ContainerBuilder();

        $this->setDefaultParamsContainer();

        // Инициализация автономных бандлов.
        $this->loadSymfonyBundles();

        // Набор стандартных Compile Pass
        $passes = new PassConfig();
        $allPasses = $passes->getPasses();

        foreach ($allPasses as $pass) {
            // Тонкость: MergeExtensionConfigurationPass добавляется в BundlesLoader.
            // Если не проигнорировать здесь, то он вызовется еще раз.
            if (get_class($pass) === MergeExtensionConfigurationPass::class) {
                continue;
            }
            self::$containerBuilder->addCompilerPass($pass);
        }

        $this->standartSymfonyPasses();

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

        $registerListenersPass = new RegisterListenersPass();
        $registerListenersPass->setHotPathEvents([
            KernelEvents::REQUEST,
            KernelEvents::CONTROLLER,
            KernelEvents::CONTROLLER_ARGUMENTS,
            KernelEvents::RESPONSE,
            KernelEvents::FINISH_REQUEST,
        ]);

        self::$containerBuilder->addCompilerPass($registerListenersPass);

        $loader = new YamlFileLoader(self::$containerBuilder, new FileLocator(
            $this->projectRoot
        ));

        try {
            $loader->load($fileName);

            // Подгрузить конфигурации из папки config.
            $this->configureContainer(
                self::$containerBuilder,
                $this->getContainerLoader(self::$containerBuilder)
            );

            // FrameworkExtension.
            $this->registerFrameworkExtensions();

            // Doctrine DBAL
            $this->registerDoctrineDbalExtension();

            // Контейнер в AppKernel, чтобы соответствовать Symfony.
            if (self::$containerBuilder->has('kernel')) {
                $kernelService = self::$containerBuilder->get('kernel');
                if ($kernelService) {
                    $kernelService->setContainer(self::$containerBuilder);
                }
            }

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

            // Дополнить переменные приложения сведениями о зарегистрированных бандлах.
            self::$containerBuilder->get('kernel')->registerStandaloneBundles();

            self::$containerBuilder->getParameterBag()->add(
                self::$containerBuilder->get('kernel')->getKernelParameters()
            );

            // Boot bundles.
            $this->bundlesLoader->boot(self::$containerBuilder);
            $this->bundlesLoader->registerExtensions(self::$containerBuilder);

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
     * Параметры контейнера и регистрация сервиса kernel.
     *
     * @return void
     *
     * @throws Exception Ошибки контейнера.
     *
     * @since 12.11.2020 Полная переработка. Регистрация сервиса.
     */
    private function setDefaultParamsContainer() : void
    {
        if (!self::$containerBuilder->hasDefinition('kernel')) {
            self::$containerBuilder->register('kernel', AppKernel::class)
                ->addTag('service.bootstrap')
                ->setAutoconfigured(true)
                ->setPublic(true)
                ->setArguments([$_ENV['DEBUG']])
            ;
        }

        self::$containerBuilder->getParameterBag()->add(
            self::$containerBuilder->get('kernel')->getKernelParameters()
        );
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
     * Стандартные Symfony манипуляции над контейнером.
     *
     * @return void
     *
     * @since 28.09.2020
     *
     * @see FrameworkBundle
     */
    private function standartSymfonyPasses(): void
    {
        // Пассы Symfony.
        $standartCompilerPasses = [
            [
                'pass' => ControllerArgumentValueResolverPass::class,
            ],
            [
                'pass' => RegisterControllerArgumentLocatorsPass::class,
            ],
            [
                'pass' => RoutingResolverPass::class,
            ],
            [
                'pass' => SerializerPass::class,
            ],
            [
                'pass' => PropertyInfoPass::class,
            ],
            [
                'pass' => RemoveEmptyControllerArgumentLocatorsPass::class,
                'phase' => PassConfig::TYPE_BEFORE_REMOVING,
            ],
        ];

        self::$containerBuilder->registerForAutoconfiguration(AbstractController::class)
            ->addTag('controller.service_arguments');

        self::$containerBuilder->registerForAutoconfiguration(ArgumentValueResolverInterface::class)
            ->addTag('controller.argument_value_resolver');

        self::$containerBuilder->registerForAutoconfiguration(ServiceLocator::class)
            ->addTag('container.service_locator');

        self::$containerBuilder->registerForAutoconfiguration(EventSubscriberInterface::class)
            ->addTag('kernel.event_subscriber');

        // Применяем compiler passes.
        foreach ($standartCompilerPasses as $pass) {
            self::$containerBuilder->addCompilerPass(
                new $pass['pass'],
                $pass['phase'] ?? PassConfig::TYPE_BEFORE_OPTIMIZATION
            );
        }
    }

    /**
     * Загрузка "автономных" бандлов Symfony.
     *
     * @return void
     *
     * @throws InvalidArgumentException  Не найден класс бандла.
     *
     * @since 24.10.2020
     */
    private function loadSymfonyBundles() : void
    {
        $this->bundlesLoader = new BundlesLoader(
            self::$containerBuilder
        );

        $this->bundlesLoader->load(); // Загрузить бандлы.

        $this->bundles = $this->bundlesLoader->bundles();
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
     * Загрузка конфигураций в различных форматах из папки configs.
     *
     * @param ContainerBuilder $container Контейнер.
     * @param LoaderInterface  $loader    Загрузчик.
     *
     * @return void
     * @throws Exception Ошибки контейнера.
     *
     * @since 06.11.2020
     */
    private function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $confDir = $_SERVER['DOCUMENT_ROOT'] .'/local/configs';
        $container->setParameter('container.dumper.inline_class_loader', true);

        $loader->load($confDir . '/packages/*' . self::CONFIG_EXTS, 'glob');
        if (is_dir($confDir . '/packages/' . $this->environment)) {
            $loader->load($confDir . '/packages/' . $this->environment . '/**/*' . self::CONFIG_EXTS, 'glob');
        }

        $loader->load($confDir . '/services_'. $this->environment. self::CONFIG_EXTS, 'glob');
    }

    /**
     * Returns a loader for the container.
     *
     * @param ContainerBuilder $container Контейнер.
     *
     * @return DelegatingLoader The loader
     * @throws Exception Ошибки контейнера.
     *
     * @since 06.11.2020
     */
    protected function getContainerLoader(ContainerBuilder $container): DelegatingLoader
    {
        $locator = new \Symfony\Component\HttpKernel\Config\FileLocator(
            self::$containerBuilder->get('kernel')
        );

        $resolver = new LoaderResolver([
            new XmlFileLoader($container, $locator),
            new YamlFileLoader($container, $locator),
            new IniFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container),
        ]);

        return new DelegatingLoader($resolver);
    }

    /**
     * Регистрация Framework Extensions.
     *
     * @return void
     *
     * @since 28.11.2020
     * @since 21.12.2020 Нативная поддержка нативных аннотированных роутов.
     *
     * @throws LogicException
     */
    private function registerFrameworkExtensions() : void
    {
        $this->frameworkExtension->registerCacheConfiguration(
            self::$containerBuilder->getParameter('cache'),
            self::$containerBuilder
        );

        if (self::$containerBuilder->hasParameter('routing')) {
            $this->frameworkExtension->loadAnnotationRoute(
                self::$containerBuilder->getParameter('routing'),
                self::$containerBuilder
            );
        }
    }

    /**
     * Инициализация DoctrineDbalExtension.
     *
     * @return void
     * @throws Exception
     *
     * @since 16.12.2020
     */
    private function registerDoctrineDbalExtension() : void
    {
        if (self::$containerBuilder->hasParameter('dbal')) {
            $dbalConfig = self::$containerBuilder->getParameter('dbal');

            $this->doctrineDbalExtension->dbalLoad(
                $dbalConfig,
                self::$containerBuilder
            );
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