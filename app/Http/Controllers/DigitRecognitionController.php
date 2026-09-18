<?php

namespace App\Http\Controllers;

use App\Services\NeuralNetwork; // استيراد خدمة الشبكة العصبية
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request; // استيراد كائن طلب لارافيل

class DigitRecognitionController extends Controller
{
    private NeuralNetwork $neuralNetwork; // كائن الشبكة العصبية المستخدم للتنبؤ
    private string $modelPath; // مسار ملف النموذج المحفوظ

    public function __construct()
    {
        $this->modelPath = storage_path('app/neural_network_model.json'); // تحديد مسار حفظ النموذج
        $fallbackModelPath = base_path('neural_network_model.json'); // دعم النموذج المنشأ بواسطة بايثون في جذر المشروع
        // Use a very small number of epochs for on-request training to avoid timeouts.
        // Full training should be done offline (artisan command) when needed.
        $this->neuralNetwork = new NeuralNetwork(784, 128, 64, 10, 0.05, 5); // إنشاء الشبكة العصبية مع إعدادات افتراضية صحيحة
        // Attempt to load a saved model; do NOT perform heavy training inside constructor.
        if (file_exists($this->modelPath)) {
            $this->neuralNetwork->load($this->modelPath); // تحميل النموذج المحفوظ إن كان موجوداً
        } elseif (file_exists($fallbackModelPath)) {
            $this->neuralNetwork->load($fallbackModelPath); // تحميل النموذج الموجود في جذر المشروع
            if (!file_exists(dirname($this->modelPath))) {
                @mkdir(dirname($this->modelPath), 0755, true);
            }
            @copy($fallbackModelPath, $this->modelPath); // نسخ النموذج إلى مكان التخزين الافتراضي لتحديث التطبيق
        }
    }

    /**
     * عرض صفحة التطبيق الرئيسية
     */
    public function index()
    {
        return view('digit-recognition.index'); // إعادة عرض الصفحة التي تحتوي على لوحة الرسم
    }

    /**
     * معالجة طلب التعرف على الرقم
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recognize(Request $request)
    {
        try {
            // التحقق من صحة البيانات المرسلة
            $request->validate([
                'image' => 'required|string', // يجب أن تتضمن الطلب بيانات الصورة كنص
            ]);

            // استخراج بيانات الصورة من الطلب
            $imageData = $request->input('image');

            // تحويل بيانات الصورة إلى مصفوفة بكسلات صالحة للنموذج
            $pixels = $this->imageDataToPixels($imageData);

            // تنبؤ الرقم باستخدام الشبكة العصبية
            $predictedDigit = $this->neuralNetwork->predict($pixels);

            // الحصول على الاحتمالات لكافة الأرقام
            $probabilities = $this->neuralNetwork->predictProbabilities($pixels);

            return response()->json([
                'success' => true,
                'predicted_digit' => $predictedDigit,
                'confidence' => round($probabilities[$predictedDigit] * 100, 2), // نسبة الثقة
                'probabilities' => array_map(fn($p) => round($p * 100, 2), $probabilities), // احتمالات لكل رقم
            ]);
        } catch (\Throwable $e) {
            Log::error('Digit recognition error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'error' => 'Recognition failed.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحويل بيانات الصورة إلى مصفوفة بكسلات
     *
     * @param string $imageData بيانات الصورة (Base64 أو Canvas)
     * @return array مصفوفة البكسلات (784 عنصر لصورة 28x28)
     */
    private function imageDataToPixels(string $imageData): array
    {
        if (strpos($imageData, 'data:image') === 0) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $imageData = base64_decode($imageData);

            $image = imagecreatefromstring($imageData);
            if ($image === false) {
                throw new \Exception('فشل في معالجة الصورة');
            }

            $raw = imagecreatetruecolor(280, 280);
            $white = imagecolorallocate($raw, 255, 255, 255);
            imagefill($raw, 0, 0, $white);
            imagecopyresampled($raw, $image, 0, 0, 0, 0, 280, 280, imagesx($image), imagesy($image));
            imagedestroy($image);

            $bbox = $this->findBoundingBox($raw, 220);
            if ($bbox !== null) {
                [$cropX, $cropY, $cropW, $cropH] = $bbox;
                $cropped = imagecreatetruecolor($cropW, $cropH);
                $whiteCropped = imagecolorallocate($cropped, 255, 255, 255);
                imagefill($cropped, 0, 0, $whiteCropped);
                imagecopy($cropped, $raw, 0, 0, $cropX, $cropY, $cropW, $cropH);
            } else {
                $cropped = $raw;
            }

            $scaled = $this->resizeToFit($cropped, 20);
            if ($cropped !== $raw) {
                imagedestroy($cropped);
            }

            $resized = imagecreatetruecolor(28, 28);
            $whiteResized = imagecolorallocate($resized, 255, 255, 255);
            imagefill($resized, 0, 0, $whiteResized);
            $offsetX = (int) floor((28 - imagesx($scaled)) / 2);
            $offsetY = (int) floor((28 - imagesy($scaled)) / 2);
            imagecopy($resized, $scaled, $offsetX, $offsetY, 0, 0, imagesx($scaled), imagesy($scaled));
            imagedestroy($scaled);
            imagedestroy($raw);

            $pixels = $this->imageToPixelArray($resized);
            imagedestroy($resized);

            return $this->centerPixelMass($pixels);
        }

        if (is_array(json_decode($imageData, true))) {
            $pixels = json_decode($imageData, true);
            return array_map(fn($p) => 1.0 - ($p / 255.0), $pixels);
        }

        throw new \Exception('صيغة البيانات غير مدعومة');
    }

    private function findBoundingBox($image, int $threshold): ?array
    {
        $minX = imagesx($image);
        $minY = imagesy($image);
        $maxX = 0;
        $maxY = 0;

        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = ($r + $g + $b) / 3;

                if ($gray < $threshold) {
                    $minX = min($minX, $x);
                    $minY = min($minY, $y);
                    $maxX = max($maxX, $x);
                    $maxY = max($maxY, $y);
                }
            }
        }

        if ($minX > $maxX || $minY > $maxY) {
            return null;
        }

        return [$minX, $minY, $maxX - $minX + 1, $maxY - $minY + 1];
    }

    private function resizeToFit($image, int $boxSize)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        if ($width === 0 || $height === 0) {
            return $image;
        }

        $ratio = min($boxSize / $width, $boxSize / $height);
        $targetW = max(1, (int) round($width * $ratio));
        $targetH = max(1, (int) round($height * $ratio));

        $scaled = imagecreatetruecolor($targetW, $targetH);
        $white = imagecolorallocate($scaled, 255, 255, 255);
        imagefill($scaled, 0, 0, $white);
        imagecopyresampled($scaled, $image, 0, 0, 0, 0, $targetW, $targetH, $width, $height);

        return $scaled;
    }

    private function imageToPixelArray($image): array
    {
        $pixels = [];
        for ($y = 0; $y < 28; $y++) {
            for ($x = 0; $x < 28; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = ($r + $g + $b) / 3;
                $pixels[] = 1.0 - ($gray / 255.0);
            }
        }
        return $pixels;
    }

    private function centerPixelMass(array $pixels): array
    {
        $sum = 0.0;
        $m10 = 0.0;
        $m01 = 0.0;

        foreach ($pixels as $index => $value) {
            $x = $index % 28;
            $y = intdiv($index, 28);
            $sum += $value;
            $m10 += $x * $value;
            $m01 += $y * $value;
        }

        if ($sum <= 0.0) {
            return $pixels;
        }

        $centerX = $m10 / $sum;
        $centerY = $m01 / $sum;
        $shiftX = (int) round(13.5 - $centerX);
        $shiftY = (int) round(13.5 - $centerY);

        if ($shiftX === 0 && $shiftY === 0) {
            return $pixels;
        }

        $centered = array_fill(0, 28 * 28, 0.0);
        foreach ($pixels as $index => $value) {
            if ($value <= 0.0) {
                continue;
            }
            $x = $index % 28;
            $y = intdiv($index, 28);
            $newX = $x + $shiftX;
            $newY = $y + $shiftY;
            if ($newX < 0 || $newX >= 28 || $newY < 0 || $newY >= 28) {
                continue;
            }
            $newIndex = $newY * 28 + $newX;
            $centered[$newIndex] = min(1.0, $centered[$newIndex] + $value);
        }

        return $centered;
    }

    /**
     * تدريب النموذج ببيانات افتراضية
     * هذا مثال بسيط - يمكن استبداله ببيانات حقيقية من MNIST
     */
    private function trainDefaultModel(): void
    {
        // إنشاء قوائم بيانات التدريب والتسميات
        $trainingData = [];
        $trainingLabels = [];

        for ($digit = 0; $digit < 10; $digit++) {
            // إضافة عينات اصطناعية لكل رقم
            for ($sample = 0; $sample < 5; $sample++) {
                $trainingData[] = NeuralNetwork::generateSyntheticDigitPixels($digit, $sample); // إنشاء صورة رقم
                $trainingLabels[] = $digit; // حفظ التسمية الرقمية
            }
        }

        // تدريب النموذج باستخدام دفعات صغيرة للحفاظ على السرعة
        $this->neuralNetwork->train($trainingData, $trainingLabels, 8);
        $this->neuralNetwork->save($this->modelPath); // حفظ النموذج بعد التدريب
    }

    // مولد الصور الاصطناعية تم نقله إلى NeuralNetwork::generateSyntheticDigitPixels

    /**
     * إعادة تدريب النموذج (للاختبار والتطوير)
     */
    public function retrain(Request $request)
    {
        try {
            $this->trainDefaultModel(); // إعادة بناء النموذج باستخدام بيانات افتراضية

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة تدريب النموذج بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * الحصول على معلومات النموذج
     */
    public function modelInfo()
    {
        return response()->json([
            'success' => true,
            'model_exists' => file_exists($this->modelPath), // هل مدل موجود مسبقاً؟
            'input_size' => 784, // حجم الدخل المتوقع
            'output_size' => 10, // عدد المخرجات
            'model_path' => $this->modelPath, // مسار الملف المحفوظ
        ]);
    }
}
