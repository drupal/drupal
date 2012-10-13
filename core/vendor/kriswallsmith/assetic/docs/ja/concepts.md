Assetic OOP APIを使用するためには、まず、[アセット」と「フィルタ」の2つの重要なコンセプトを理解する必要があります。

### アセット

アセットとは、読み込み、及びダンプが可能な、コンテンツとメタデータを内包しているオブジェクトの事を指します。
大体の場合において3つのカテゴリー、すなわち、Javascriptとスタイルシート、画像のどれかに属することになるでしょう。
読み込みの方法としては、ファイルシステムからがほとんどですが、
HTTPやデータベース経由でも、文字列としてでも読み込みが可能で、事実上あらゆるものが読み込み可能です。
Asseticのアセットインターフェースを満足させさえすれば良いのです。


### フィルタ
 
フィルタは、アセットが読み込まれる、かつ/もしくは、ダンプされる際に、
アセットコンテンツに対して作用するオブジェクトです。
アセットと同様に、Asseticのフィルタインターフェースを実装することで、
どのような作用も可能になります。

フィルタを用いて、アセットに適用できるツール群の一覧です。

 * CoffeeScript
 * CssEmbed
 * CssMin
 * Google Closure Compiler
 * jpegoptim
 * jpegtran
 * Less
 * LessPHP
 * optipng
 * Packager
 * pngout
 * SASS
 * Sprockets (version 1)
 * Stylus
 * YUI Compressor


### アセットとフィルタの使用

まずはアセットオブジェクトを作成することから始まります。
多くの場合は`FileAsset`をインスタンス化し、ファイルシステムのパスを第一引数に渡します。

    $asset = new Assetic\Asset\FileAsset('/path/to/main.css');

アセットオブジェクトを作成したら、`ensureFilter()`を呼び、フィルタを追加します。
例えば、アセットコンテンツにYUI Compressorを適用してみましょう。

    $yui = new Assetic\Filter\Yui\CssCompressorFilter('/path/to/yui.jar');
    $asset->ensureFilter($yui);

任意のフィルタを追加したら、完成したアセットをブラウザに出力してみましょう。

    header('Content-Type: text/css');
    echo $asset->dump();

### アセットコレクション

1つのファイルに同じ種類のアセットをまとめて、不要なHTTPリクエストを抑えてみるのも良いでしょう。
Asseticでは`AsseticColletion`クラスを使用することで可能となります。
Assetic内部的には、このクラス自体は他のアセットと同様に、アセットインターフェースを実装したものですが、
複数のアセットを1つにまとめることが可能になります。

    use Assetic\Asset\AssetCollection;

    $asset = new AssetCollection(array(
        new FileAsset('/path/to/js/jquery.js'),
        new FileAsset('/path/to/js/jquery.plugin.js'),
        new FileAsset('/path/to/js/application.js'),
    ));

### ネストしたアセットコレクション

コレクションクラス自体がアセットインターフェースを実装し、コレクション内のアセットも同様に
アセットインターフェースを実装しているので、簡単にネストすることができます。

    use Assetic\Asset\AssetCollection;
    use Assetic\Asset\GlobAsset;
    use Assetic\Asset\HttpAsset;

    $asset = new AssetCollection(array(
        new HttpAsset('http://example.com/jquery.min.js'),
        new GlobAsset('/path/to/js/*'),
    ));

`HttpAsset`は、HTTP経由でファイルを読み込むアセットクラス。
`GlobAsset`は、ファイルシステムのglobを基にファイル群を読み込むアセットコレクションクラス。
両者ともにアセットインターフェースを実装しています。

このネストしたアセットコレクションという概念は、コレクションそれぞれに異なる
フィルタ群を適用しようとしたときに、効果を発揮します。
例えば、スタイルシートがSAASで記述されたものと、vanilla CSSを用いて記述されたものからなる
アプリケーションを考えた場合、次のようにして、全てを1つのシームレスなCSSアセットにまとめることができます。

    use Assetic\Asset\AssetCollection;
    use Assetic\Asset\GlobAsset;
    use Assetic\Filter\SassFilter;
    use Assetic\Filter\Yui\CssCompressorFilter;

    $css = new AssetCollection(array(
        new GlobAsset('/path/to/sass/*.sass', array(new SassFilter())),
        new GlobAsset('/path/to/css/*.css'),
    ), array(
        new YuiCompressorFilter('/path/to/yuicompressor.jar'),
    ));

上記の例では、1つにまとめられたCSSを、さらにYUI compressorフィルタを適用することで、全体を圧縮しています。

### アセットコレクションのイテレーション

アセットコレクションは、旧来のPHP配列のように、イテレートできます。

    echo "Source paths:\n";
    foreach ($collection as $asset) {
        echo ' - '.$asset->getSourcePath()."\n";
    }

アセットコレクションのイテレーションは再帰的で、「葉」にあたるアセットの取得を行います。
また、気の利いたフィルタを内蔵しているので、同じアセットがコレクション内に複数存在する場合でも、
一度だけのインクルードが保証されます。

Next: [アセットを「オンザフライ」で定義する](define.md)
