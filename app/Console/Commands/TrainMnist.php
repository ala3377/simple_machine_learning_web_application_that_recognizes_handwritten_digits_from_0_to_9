<?php

namespace App\Console\Commands;

use App\Services\NeuralNetwork;
use Illuminate\Console\Command;

class TrainMnist extends Command
{
    protected $signature = 'train:mnist {--limit=5000} {--epochs=10} {--batch=64}';
    protected $description = 'Download MNIST and train the neural network offline (storage/app/mnist)';

    public function handle()
    {
        $this->info('Preparing MNIST data...');

        $storage = storage_path('app/mnist');
        if (!is_dir($storage)) {
            mkdir($storage, 0755, true);
        }

        $mirrors = [
            'train-images-idx3-ubyte.gz' => [
                'https://storage.googleapis.com/cvdf-datasets/mnist/train-images-idx3-ubyte.gz',
                'https://github.com/myleott/mnist_png/raw/master/mnist/train-images-idx3-ubyte.gz',
                'https://yann.lecun.com/exdb/mnist/train-images-idx3-ubyte.gz',
            ],
            'train-labels-idx1-ubyte.gz' => [
                'https://storage.googleapis.com/cvdf-datasets/mnist/train-labels-idx1-ubyte.gz',
                'https://github.com/myleott/mnist_png/raw/master/mnist/train-labels-idx1-ubyte.gz',
                'https://yann.lecun.com/exdb/mnist/train-labels-idx1-ubyte.gz',
            ],
        ];

        foreach ($mirrors as $name => $urls) {
            $path = $storage . DIRECTORY_SEPARATOR . $name;
            if (file_exists($path)) {
                continue;
            }

            $this->info('Downloading ' . $name);
            $downloaded = false;
            foreach ($urls as $url) {
                $this->line('Trying: ' . $url);
                $data = $this->downloadFile($url);
                if ($data !== false) {
                    file_put_contents($path, $data);
                    $downloaded = true;
                    break;
                }
            }

            if (!$downloaded) {
                $this->error('Failed to download ' . $name . '. Please download it manually and place it in ' . $storage);
                return 1;
            }
        }

        $limit = (int)$this->option('limit');
        $epochs = (int)$this->option('epochs');
        $batch = (int)$this->option('batch');

        // عدل هذا المتغير حسب هدفك: 10 للأرقام، 10000 للأعداد من 0 إلى 9999
        $outputSize = 10; // أو 10000 مستقبلاً

        ini_set('memory_limit', '1024M');

        $this->info('Creating network and training...');
        // الطبقة الأولى 128، الثانية 64، وعدد المخرجات مرن
        $net = new NeuralNetwork(784, 128, 64, $outputSize, 0.1, $epochs);
        $this->trainMnistFromFiles(
            $net,
            $storage . DIRECTORY_SEPARATOR . 'train-images-idx3-ubyte.gz',
            $storage . DIRECTORY_SEPARATOR . 'train-labels-idx1-ubyte.gz',
            $limit,
            $batch,
            $epochs,
            function (int $epoch, int $totalEpochs) {
                $this->info('Epoch ' . $epoch . ' / ' . $totalEpochs . ' complete');
            }
        );
        $net->save(storage_path('app/neural_network_model.json'));

        $this->info('Training complete; model saved to storage/app/neural_network_model.json');
        return 0;
    }

    private function downloadFile(string $url)
    {
        $context = stream_context_create([
            'http' => ['timeout' => 30, 'follow_location' => true],
            'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $data = @file_get_contents($url, false, $context);
        if ($data !== false) {
            return $data;
        }

        if (function_exists('curl_version')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        }

        return false;
    }

    private function loadMnist(string $imagesGz, string $labelsGz, int $limit = 0): array
    {
        $images = [];
        $labels = [];

        $imgFp = gzopen($imagesGz, 'rb');
        $lblFp = gzopen($labelsGz, 'rb');
        if (!$imgFp || !$lblFp) {
            throw new \RuntimeException('Cannot open MNIST files');
        }

        $magic = $this->readInt($imgFp);
        $count = $this->readInt($imgFp);
        $rows = $this->readInt($imgFp);
        $cols = $this->readInt($imgFp);

        $magicLbl = $this->readInt($lblFp);
        $countLbl = $this->readInt($lblFp);

        $n = min($count, $countLbl);
        if ($limit > 0) {
            $n = min($n, $limit);
        }

        for ($i = 0; $i < $n; $i++) {
            $img = gzread($imgFp, $rows * $cols);
            if ($img === false || strlen($img) < $rows * $cols) break;
            $pixels = array_map(function ($v) { return $v / 255.0; }, array_map('ord', str_split($img)));
            $images[] = $pixels;
            $lbl = gzread($lblFp, 1);
            $labels[] = ord($lbl);
        }

        gzclose($imgFp);
        gzclose($lblFp);

        return [$images, $labels];
    }

    private function trainMnistFromFiles(NeuralNetwork $net, string $imagesGz, string $labelsGz, int $limit, int $batchSize, int $epochs, ?callable $progressCallback = null): void
    {
        for ($epoch = 1; $epoch <= $epochs; $epoch++) {
            $imgFp = gzopen($imagesGz, 'rb');
            $lblFp = gzopen($labelsGz, 'rb');
            if (!$imgFp || !$lblFp) {
                throw new \RuntimeException('Cannot open MNIST files');
            }

            $this->readInt($imgFp);
            $count = $this->readInt($imgFp);
            $rows = $this->readInt($imgFp);
            $cols = $this->readInt($imgFp);

            $this->readInt($lblFp);
            $countLbl = $this->readInt($lblFp);

            $n = min($count, $countLbl);
            if ($limit > 0) {
                $n = min($n, $limit);
            }

            $batchImages = [];
            $batchLabels = [];

            for ($i = 0; $i < $n; $i++) {
                $img = gzread($imgFp, $rows * $cols);
                if ($img === false || strlen($img) < $rows * $cols) {
                    break;
                }

                $pixels = array_map(function ($v) {
                    return $v / 255.0;
                }, array_map('ord', str_split($img)));

                $batchImages[] = $pixels;
                $lbl = gzread($lblFp, 1);
                $batchLabels[] = ord($lbl);

                if (count($batchImages) >= $batchSize) {
                    $net->trainEpoch($batchImages, $batchLabels, $batchSize);
                    $batchImages = [];
                    $batchLabels = [];
                }
            }

            if (count($batchImages) > 0) {
                $net->trainEpoch($batchImages, $batchLabels, $batchSize);
            }

            gzclose($imgFp);
            gzclose($lblFp);

            if (is_callable($progressCallback)) {
                $progressCallback($epoch, $epochs);
            }
        }
    }

    private function readInt($fp): int
    {
        $data = gzread($fp, 4);
        if ($data === false || strlen($data) < 4) return 0;
        $vals = unpack('N', $data);
        return $vals[1];
    }
}
