<?php

namespace App\Services;


class NeuralNetwork
{
    private array $hiddenWeights; // أوزان الطبقة المخفية الأولى
    private array $hidden2Weights; // أوزان الطبقة المخفية الثانية
    private array $outputWeights; // أوزان طبقة المخرجات
    private array $hiddenBias; // انحيازات الطبقة المخفية الأولى
    private array $hidden2Bias; // انحيازات الطبقة المخفية الثانية
    private array $outputBias; // انحيازات طبقة المخرجات
    private float $learningRate; // معدل التعلم
    private int $epochs; // عدد العصور التدريبية
    private int $inputSize; // حجم مدخلات الشبكة
    private int $hiddenSize; // عدد وحدات الطبقة المخفية الأولى
    private int $hidden2Size; // عدد وحدات الطبقة المخفية الثانية
    private int $outputSize; // عدد وحدات المخرجات
    private const MODEL_VERSION = 2; // نسخة تنسيق حفظ النموذج

    public function __construct(
        int $inputSize = 784,
        int $hiddenSize = 128,
        int $hidden2Size = 64,
        int $outputSize = 10,
        float $learningRate = 0.05,
        int $epochs = 150
    ) {
        $this->inputSize = $inputSize;
        $this->hiddenSize = $hiddenSize;
        $this->hidden2Size = $hidden2Size;
        $this->outputSize = $outputSize;
        $this->learningRate = $learningRate;
        $this->epochs = $epochs;

        // أوزان الطبقة المخفية الأولى
        $this->hiddenWeights = [];
        for ($i = 0; $i < $this->hiddenSize; $i++) {
            $this->hiddenWeights[$i] = [];
            for ($j = 0; $j < $this->inputSize; $j++) {
                $this->hiddenWeights[$i][$j] = (mt_rand(-100, 100) / 1000);
            }
        }

        // أوزان الطبقة المخفية الثانية
        $this->hidden2Weights = [];
        for ($i = 0; $i < $this->hidden2Size; $i++) {
            $this->hidden2Weights[$i] = [];
            for ($j = 0; $j < $this->hiddenSize; $j++) {
                $this->hidden2Weights[$i][$j] = (mt_rand(-100, 100) / 1000);
            }
        }

        // أوزان طبقة المخرجات
        $this->outputWeights = [];
        for ($i = 0; $i < $this->outputSize; $i++) {
            $this->outputWeights[$i] = [];
            for ($j = 0; $j < $this->hidden2Size; $j++) {
                $this->outputWeights[$i][$j] = (mt_rand(-100, 100) / 1000);
            }
        }

        $this->hiddenBias = array_fill(0, $this->hiddenSize, 0.01);
        $this->hidden2Bias = array_fill(0, $this->hidden2Size, 0.01);
        $this->outputBias = array_fill(0, $this->outputSize, 0.01);
    }

    private function sigmoid(float $x): float
    {
        return 1 / (1 + exp(-$x)); // دالة تنشيط سيجمويد
    }

    private function sigmoidDerivative(float $output): float
    {
        return $output * (1 - $output); // مشتقة سيجمويد لاستخدامها في التعلم العكسي
    }

    private function softmax(array $outputs): array
    {
        $max = max($outputs); // لتثبيت الأعداد الكبيرة قبل الأس
        $exp = array_map(fn($x) => exp($x - $max), $outputs); // حساب الأس بعد التثبيت
        $sum = array_sum($exp); // مجموع القيم الأسية
        return array_map(fn($x) => $x / $sum, $exp); // تطبيع القيم إلى احتمالات
    }

    private function forwardHidden1(array $input): array
    {
        $hidden = [];
        for ($i = 0; $i < $this->hiddenSize; $i++) {
            $sum = $this->hiddenBias[$i];
            for ($j = 0; $j < $this->inputSize; $j++) {
                $sum += $input[$j] * $this->hiddenWeights[$i][$j];
            }
            $hidden[$i] = $this->sigmoid($sum);
        }
        return $hidden;
    }

    private function forwardHidden2(array $hidden1): array
    {
        $hidden2 = [];
        for ($i = 0; $i < $this->hidden2Size; $i++) {
            $sum = $this->hidden2Bias[$i];
            for ($j = 0; $j < $this->hiddenSize; $j++) {
                $sum += $hidden1[$j] * $this->hidden2Weights[$i][$j];
            }
            $hidden2[$i] = $this->sigmoid($sum);
        }
        return $hidden2;
    }

    private function forwardOutput(array $hidden2): array
    {
        $outputs = [];
        for ($i = 0; $i < $this->outputSize; $i++) {
            $sum = $this->outputBias[$i];
            for ($j = 0; $j < $this->hidden2Size; $j++) {
                $sum += $hidden2[$j] * $this->outputWeights[$i][$j];
            }
            $outputs[$i] = $sum;
        }
        return $outputs;
    }

    /**
     * Train using mini-batch SGD. Batch size of 1 equals plain SGD.
     * This reduces PHP call overhead by accumulating gradients per-batch.
     *
     * @param array $trainingData Array of input vectors (each length inputSize)
     * @param array $trainingLabels Array of integer labels
     * @param int $batchSize Number of samples per gradient update
     */
    public function train(array $trainingData, array $trainingLabels, int $batchSize = 1, ?callable $progressCallback = null): void
    {
        for ($epoch = 0; $epoch < $this->epochs; $epoch++) {
            $this->trainEpoch($trainingData, $trainingLabels, $batchSize);

            if (is_callable($progressCallback)) {
                $progressCallback($epoch + 1, $this->epochs); // إشعار تقدم التدريب إن وُجد callback
            }
        }
    }

    public function trainEpoch(array $trainingData, array $trainingLabels, int $batchSize = 1): void
    {
        $n = count($trainingData);
        if ($n === 0) return;
        $indices = range(0, $n - 1);
        shuffle($indices);
        for ($start = 0; $start < $n; $start += $batchSize) {
            $actualBatch = min($batchSize, $n - $start);
            $dOutputW = array_fill(0, $this->outputSize, array_fill(0, $this->hidden2Size, 0.0));
            $dOutputB = array_fill(0, $this->outputSize, 0.0);
            $dHidden2W = array_fill(0, $this->hidden2Size, array_fill(0, $this->hiddenSize, 0.0));
            $dHidden2B = array_fill(0, $this->hidden2Size, 0.0);
            $dHiddenW = array_fill(0, $this->hiddenSize, array_fill(0, $this->inputSize, 0.0));
            $dHiddenB = array_fill(0, $this->hiddenSize, 0.0);
            for ($b = 0; $b < $actualBatch; $b++) {
                $idx = $indices[$start + $b];
                $input = $trainingData[$idx];
                $target = array_fill(0, $this->outputSize, 0);
                $target[$trainingLabels[$idx]] = 1;
                $hidden1 = $this->forwardHidden1($input);
                $hidden2 = $this->forwardHidden2($hidden1);
                $logits = $this->forwardOutput($hidden2);
                $probabilities = $this->softmax($logits);
                $outputErrors = array_fill(0, $this->outputSize, 0.0);
                for ($j = 0; $j < $this->outputSize; $j++) {
                    $outputErrors[$j] = $probabilities[$j] - $target[$j];
                }
                for ($j = 0; $j < $this->outputSize; $j++) {
                    for ($k = 0; $k < $this->hidden2Size; $k++) {
                        $dOutputW[$j][$k] += $outputErrors[$j] * $hidden2[$k];
                    }
                    $dOutputB[$j] += $outputErrors[$j];
                }
                $hidden2Errors = array_fill(0, $this->hidden2Size, 0.0);
                for ($k = 0; $k < $this->hidden2Size; $k++) {
                    $sum = 0.0;
                    for ($j = 0; $j < $this->outputSize; $j++) {
                        $sum += $outputErrors[$j] * $this->outputWeights[$j][$k];
                    }
                    $hidden2Errors[$k] = $sum * $this->sigmoidDerivative($hidden2[$k]);
                }
                for ($k = 0; $k < $this->hidden2Size; $k++) {
                    for ($i = 0; $i < $this->hiddenSize; $i++) {
                        $dHidden2W[$k][$i] += $hidden2Errors[$k] * $hidden1[$i];
                    }
                    $dHidden2B[$k] += $hidden2Errors[$k];
                }
                $hidden1Errors = array_fill(0, $this->hiddenSize, 0.0);
                for ($i = 0; $i < $this->hiddenSize; $i++) {
                    $sum = 0.0;
                    for ($k = 0; $k < $this->hidden2Size; $k++) {
                        $sum += $hidden2Errors[$k] * $this->hidden2Weights[$k][$i];
                    }
                    $hidden1Errors[$i] = $sum * $this->sigmoidDerivative($hidden1[$i]);
                }
                for ($i = 0; $i < $this->hiddenSize; $i++) {
                    for ($j = 0; $j < $this->inputSize; $j++) {
                        $dHiddenW[$i][$j] += $hidden1Errors[$i] * $input[$j];
                    }
                    $dHiddenB[$i] += $hidden1Errors[$i];
                }
            }
            $scale = $this->learningRate / max(1, $actualBatch);
            for ($j = 0; $j < $this->outputSize; $j++) {
                for ($k = 0; $k < $this->hidden2Size; $k++) {
                    $this->outputWeights[$j][$k] -= $scale * $dOutputW[$j][$k];
                }
                $this->outputBias[$j] -= $scale * $dOutputB[$j];
            }
            for ($k = 0; $k < $this->hidden2Size; $k++) {
                for ($i = 0; $i < $this->hiddenSize; $i++) {
                    $this->hidden2Weights[$k][$i] -= $scale * $dHidden2W[$k][$i];
                }
                $this->hidden2Bias[$k] -= $scale * $dHidden2B[$k];
            }
            for ($i = 0; $i < $this->hiddenSize; $i++) {
                for ($j = 0; $j < $this->inputSize; $j++) {
                    $this->hiddenWeights[$i][$j] -= $scale * $dHiddenW[$i][$j];
                }
                $this->hiddenBias[$i] -= $scale * $dHiddenB[$i];
            }
        }
    }


    public function predict(array $input): int
    {
        $hidden1 = $this->forwardHidden1($input);
        $hidden2 = $this->forwardHidden2($hidden1);
        $logits = $this->forwardOutput($hidden2);
        $probabilities = $this->softmax($logits);
        $maxIndex = 0;
        $maxValue = $probabilities[0];
        for ($i = 1; $i < $this->outputSize; $i++) {
            if ($probabilities[$i] > $maxValue) {
                $maxValue = $probabilities[$i];
                $maxIndex = $i;
            }
        }
        return $maxIndex;
    }

    public function predictProbabilities(array $input): array
    {
        $hidden1 = $this->forwardHidden1($input);
        $hidden2 = $this->forwardHidden2($hidden1);
        $logits = $this->forwardOutput($hidden2);
        return $this->softmax($logits);
    }

    public function save(string $filename): void
    {
        $data = [
            'version' => self::MODEL_VERSION,
            'inputSize' => $this->inputSize,
            'hiddenSize' => $this->hiddenSize,
            'hidden2Size' => $this->hidden2Size,
            'outputSize' => $this->outputSize,
            'hiddenWeights' => $this->hiddenWeights,
            'hidden2Weights' => $this->hidden2Weights,
            'outputWeights' => $this->outputWeights,
            'hiddenBias' => $this->hiddenBias,
            'hidden2Bias' => $this->hidden2Bias,
            'outputBias' => $this->outputBias,
        ];
        file_put_contents($filename, json_encode($data));
    }

    public function load(string $filename): bool
    {
        if (!file_exists($filename)) return false;
        $data = json_decode(file_get_contents($filename), true);
        if (!is_array($data) || !isset($data['version']) || $data['version'] !== self::MODEL_VERSION) return false;
        $this->inputSize = $data['inputSize'];
        $this->hiddenSize = $data['hiddenSize'];
        $this->hidden2Size = $data['hidden2Size'];
        $this->outputSize = $data['outputSize'];
        $this->hiddenWeights = $data['hiddenWeights'];
        $this->hidden2Weights = $data['hidden2Weights'];
        $this->outputWeights = $data['outputWeights'];
        $this->hiddenBias = $data['hiddenBias'];
        $this->hidden2Bias = $data['hidden2Bias'];
        $this->outputBias = $data['outputBias'];
        return true;
    }

    /**
     * Generate a simple synthetic 28x28 digit pixel array (0..1) for quick training/testing.
     * Kept here so offline `train:mnist` can use it for demo training.
     */
    public static function generateSyntheticDigitPixels(int $digit, int $sample): array
    {
        $image = imagecreatetruecolor(28, 28); // إنشاء صورة جديدة بحجم 28x28
        $white = imagecolorallocate($image, 255, 255, 255); // لون خلفية أبيض
        $black = imagecolorallocate($image, 0, 0, 0); // لون الرقم أسود
        imagefilledrectangle($image, 0, 0, 28, 28, $white); // تعبئة الخلفية باللون الأبيض

        $x = 5 + (($sample % 3) - 1); // تغيير موقع الرقم أفقيًا
        $y = 4 + (int)(intdiv($sample, 3) % 3) - 1; // تغيير موقع الرقم رأسيًا
        imagestring($image, 5, max(0, min(10, $x)), max(0, min(14, $y)), (string)$digit, $black); // كتابة الرقم على الصورة

        $pixels = [];
        for ($yy = 0; $yy < 28; $yy++) {
            for ($xx = 0; $xx < 28; $xx++) {
                $rgb = imagecolorat($image, $xx, $yy); // قراءة لون البكسل
                $r = ($rgb >> 16) & 0xFF; // قيمة الأحمر
                $g = ($rgb >> 8) & 0xFF; // قيمة الأخضر
                $b = $rgb & 0xFF; // قيمة الأزرق
                $gray = ($r + $g + $b) / 3; // تحويل إلى تدرج رمادي
                $pixels[] = $gray / 255.0; // تطبيع قيمة البكسل
            }
        }

        imagedestroy($image); // تحرير الذاكرة المستخدمة للصورة
        return $pixels; // إعادة مصفوفة البكسلات
    }
}
