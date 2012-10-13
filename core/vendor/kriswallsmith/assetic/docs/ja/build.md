アセットのビルドとダンプ
---------------------------

Asseticを使う一番単純な方法は、次の2ステップからなります。

 1. 公開領域内にPHPスクリプトを作成し、Assetic OOP APIを使用してアセットの作成・出力を行う
 2. テンプレートから上記のファイルを参照する

例えば、公開領域内に`assets/javascripts.php`ファイルを作成し、
下記のようなコードを記述します。

    use Assetic\Asset\AssetCollection;
    use Assetic\Asset\FileAsset;
    use Assetic\Filter\Yui\JsCompressorFilter as YuiCompressorFilter;

    $js = new AssetCollection(array(
        new FileAsset(__DIR__.'/jquery.js'),
        new FileAsset(__DIR__.'/application.js'),
    ), array(
        new YuiCompressorFilter('/path/to/yuicompressor.jar'),
    ));

    header('Content-Type: application/js');
    echo $js->dump();

HTMLテンプレート側では、単に`<script>`タグを用いて、生成されたJavascriptをインクルードすることになります。

    <script src="/assets/javascripts.php"></script>

Next: [コンセプト](concepts.md)
