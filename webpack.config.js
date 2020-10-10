// webpack.config.js
const Encore = require("@symfony/webpack-encore");
const {
  saveWebpackConfig,
  configureDevServer,
  addBundleAnalyzerPlugin,
  resolvePath,
  prepareAliases,
  addSVGSpritemapPlugin,
  configureWatchOptions,
  clean,
} = require("./webpack.parts");

//��������� ����������, ����� ����� �������� ������� �����
const IS_DEV = Encore.isDev() && !Encore.isDevServer();
const IS_DEV_SERVER = Encore.isDevServer();
const IS_PRODUCTION = Encore.isProduction();

//���������� ������� ������������ webpack � ����
const SAVE_WEBPACK_CONFIG_ENABLED = false;
const SAVE_WEBPACK_CONFIG_FILENAME = "webpack.config.json";

//���������� ������ ��� ������� ������ webpack
const BUNDLE_ANALYZER_PLUGIN_ENABLED = false;
const BUNDLE_ANALYZER_PLUGIN_REPORT_FILENAME = "report.html";
const BUNDLE_ANALYZER_PLUGIN_MODE = "static";

const PATHS = {
  src: {
    local: "local/assets",
    mainJs: "local/assets/main.js",
    scripts: "local/assets/scripts",
    scss: "local/assets/scss",
    images: "local/assets/images",
    icons: "local/assets/images/icons",
    mixin: "local/assets/mixin",
    components: "local/assets/components",
  },
  output: {
    local: IS_PRODUCTION ? "local/dist" : "local/build",
  },
};

// prettier-ignore
const PUBLIC_PATH = !IS_DEV ? "/local/dist" : "/local/build";

const ALIASES = {
  "@": PATHS.src.local,
  "@mixin": PATHS.src.mixin,
  "@scss": PATHS.src.scss,
  "@images": PATHS.src.images,
  "@scripts": PATHS.src.scripts,
  "@components": PATHS.src.components,
};

if (!IS_DEV_SERVER) {
  clean();
}

Encore
  // prettier-ignore
  .setOutputPath(PATHS.output.local)
  .setPublicPath(PUBLIC_PATH)
  .addEntry("main", resolvePath(PATHS.src.mainJs))

  // �������� �������� runtime ����  �� ��� ��� ���� ����� �� ������� ��������� ���������� ������ � entrypoints.json
  //TODO: �������� ����� ��������� ��������� ���������� ������ ��� entrypoints.json
  .disableSingleRuntimeChunk()

  // �������� �������� �� local/assets � �����, ���� ������ ���������� ������
  //TODO: ������ �� �����?!
  .copyFiles({
    from: resolvePath(PATHS.src.images),
    context: "images",
    pattern: /^(?!icons(.*)\.svg)$/i,
  })
  .addAliases(prepareAliases(ALIASES))
  .enableVueLoader(() => {}, { runtimeCompilerBuild: true, useJsx: false })
  .enablePostCssLoader()
  .enableSassLoader(() => {}, {
    resolveUrlLoader: false,
  })
  .autoProvideVariables({
    $: "jquery",
    jQuery: "jquery",
    "window.jQuery": "jquery",
    BX: "BX",
    "window.BX": "BX",
  })
  .configureDefinePlugin((options) => {
    options.DEBUG = false;
    options["process.env.DEBUG"] = JSON.stringify("false");
  })
  .configureUrlLoader({
    images: {
      limit: 8192,
      esModule: false,
    },
    fonts: {
      limit: 8192,
      esModule: false,
    },
  })
  .enableSourceMaps(IS_DEV || IS_DEV_SERVER)
  .addExternals({
    BX: "BX",
    ymaps: "ymaps",
    jquery: "jQuery",
  })
  .configureFilenames({
    js: "js/[name].js?v=[hash]",
    css: "css/[name].css?v=[hash]",
    images: "images/[path][name].[hash:8].[ext]",
    fonts: "fonts/[path][name].[hash:8].[ext]",
  })
  .enableVersioning(!IS_DEV_SERVER)
  //TODO: ������������� ��� chunks: "all" ����� ��������� ��������� ���������� ������ ��� entrypoints.json
  .splitEntryChunks()
  .configureSplitChunks((splitChunks) => {
    splitChunks.chunks = "async";
  });

addSVGSpritemapPlugin(PATHS.src.icons, IS_PRODUCTION);

// ��������� ��� watch ������
if (IS_DEV_SERVER) {
  configureWatchOptions();
}

// ��������� ��� ������ dev-server
if (IS_DEV_SERVER) {
  configureDevServer(PATHS.output.local);
}

if (BUNDLE_ANALYZER_PLUGIN_ENABLED) {
  addBundleAnalyzerPlugin(
    BUNDLE_ANALYZER_PLUGIN_REPORT_FILENAME,
    BUNDLE_ANALYZER_PLUGIN_MODE
  );
}

let config = Encore.getWebpackConfig();

config.node = false;
config.optimization.nodeEnv = IS_PRODUCTION ? "production" : "development";

/**
 * �������� node �� ��������� :
 * {
 *   console: false,
 *   global: true,
 *   process: true,
 *   __filename: 'mock',
 *   __dirname: 'mock',
 *   Buffer: true,
 *   setImmediate: true
 * }
 */

// ���������� ������� webpack. ��� �������
if (SAVE_WEBPACK_CONFIG_ENABLED) {
  saveWebpackConfig(SAVE_WEBPACK_CONFIG_FILENAME, config);
}

module.exports = config;