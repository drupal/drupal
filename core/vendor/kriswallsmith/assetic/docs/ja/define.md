アセットの「オンザフライ」な定義
----------------------------------------

Asseticの使用方法二つ目は、独立したPHPファイルを使用する代わりに、
テンプレートで「オンザフライ」にアセット定義をする方法です。
このアプローチでは、PHPテンプレートは下記のようになります。

    <script src="<?php echo assetic_javascripts('js/*', 'yui_js') ?>"></script>

`assetic_javascripts()`の呼び出しは2つの目的を兼ねています。
まず、「フォーミュラローダー」により走査され、アセットの構築、ダンプ、及び出力を行うための「フォーミュラ(処方箋)」が抽出されます。
また、テンプレートのレンダー時にも実行され、アセットの出力パスが出力されます。

Asseticには下記のようなヘルパー関数があります。

 * `assetic_image()`
 * `assetic_javascripts()`
 * `assetic_stylesheets()`

アセットをオンザフライに定義するということは、より高度なテクニックであり、
そのため、重い仕事をするサービスに依存することになります。
そのうちの重要なものがアセットファクトリです。

### アセットファクトリ

アセットファクトリは、アセットオブジェクトを、配列とスカラ値のみから、
どのように作成するのか把握しています。
`assetic_*`ヘルパー関数で使用する記法と同様のものとなります。

    use Assetic\Factory\AssetFactory;

    $factory = new AssetFactory('/path/to/web');
    $js = $factory->createAsset(array(
        'js/jquery.js',
        'js/jquery.plugin.js',
        'js/application.js',
    ));

### フィルタマネージャー

ファクトリによって作成されたアセットに対しても、フィルタを適用することができます。
そのためには、`FilterManager`を設定して、名前を定義しフィルタを構成します。

    use Assetic\FilterManager;
    use Assetic\Filter\GoogleClosure\ApiFilter as ClosureFilter;

    $fm = new FilterManager();
    $fm->set('closure', new ClosureFilter());
    $factory->setFilterManager($fm);

    $js = $factory->createAsset('js/*', 'closure');

上記の例では、Google Closure Compilerフィルタをインスタンス化し、
フィルタマネージャーを通じて`closure`という名前をつけています。
このフィルタマネージャーをアセットファクトリに渡すことで、
アセット作成時には、`closure`という名前でフィルタを使用できるようになります。

### デバッグモード

アセットファクトリは、デバッグモードというコンセプトも取り入れており、
デバッグモードの設定により、ファクトリが作成するアセットから、
特定のフィルタを除外することができます。

たとえば、YUI Compressorは大変素晴らしいのですが、圧縮されたJavascriptを
デバッグするのは大変難しく、プロダクション環境でのみの使用が適切でしょう。

    use Asset\Factory\AssetFactory;

    $factory = new AssetFactory('/path/to/web', true); // デバッグモードON
    $factory->setFilterManager($fm);
    $js = $factory->createAsset('js/*', '?closure');

フィルタ名`closure`の前にクエスチョンマークを記述すると、ファクトリに対して、
このフィルタはオプションであり、
デバッグモードがOFFの時にのみ適用するように通知することができます。

### アセットマネージャーとアセットリファレンス

アセットファクトリにはもう一つ特別な記法があり、別の場所で定義した
アセットを参照することができるようになります。
これを「アセットリファレンス」と呼び、アセットマネージャーを通じて、
フィルタマネージャーと同様の、名前によるアセットの構成が可能です。

    use Assetic\AssetManager;
    use Assetic\Asset\FileAsset;
    use Assetic\Factory\AssetFactory;

    $am = new AssetManager();
    $am->set('jquery', new FileAsset('/path/to/jquery.js'));

    $factory = new AssetFactory('/path/to/web');
    $factory->setAssetManager($am);

    $js = $factory->createAsset(array(
        '@jquery',
        'js/application.js',
    ));

### テンプレートからのアセット抽出

テンプレート内でアセット群を定義したら、「フォーミュラローダー」サービスを使用して、
アセットの定義を抽出します。

    use Assetic\Factory\Loader\FunctionCallsFormulaLoader;
    use Assetic\Factory\Resource\FileResource;

    $loader = new FunctionCallsFormulaLoader($factory);
    $formulae = $loader->load(new FileResource('/path/to/template.php'));

これらのフォーミュラ自体は、それ自体で使途はあまりなく、
アセットファクトリが目的のアセットオブジェクトを作成するに足る情報しか持っていません。
`LazyAssetManager`でラップすることで有益なものとなります。

### レイジーなアセットマネージャー

このサービスは、アセットファクトリと、1つ以上のフォーミュラローダーから成っており、
裏方のサービス間のグルとして動作しますが、表面上では、通常のアセットマネージャーと同じように使用することができます。

    use Assetic\Asset\FileAsset;
    use Assetic\Factory\LazyAssetManager;
    use Assetic\Factory\Loader\FunctionCallsFormulaLoader;
    use Assetic\Factory\Resource\DirectoryResource;

    $am = new LazyAssetManager($factory);
    $am->set('jquery', new FileAsset('/path/to/jquery.js'));
    $am->setLoader('php', new FunctionCallsFormulaLoader($factory));
    $am->addResource(new DirectoryResource('/path/to/templates', '/\.php$/'), 'php');

### アセットライター

作成したアセットマネージャーが、テンプレート内で定義した全てのアセットを把握したら、
アセットライターを使用して、テンプレートが参照することになる実際のファイルを作成します。

    use Assetic\AssetWriter;

    $writer = new AssetWriter('/path/to/web');
    $writer->writeManagerAssets($am);

上記のスクリプトを実行すると、アセットマネージャー内のすべてのアセットがメモリに読み込まれ、
指定したフィルタが適用された後、公開領域に静的ファイルとしてダンプされ、準備完了となります。
