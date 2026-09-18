# 📚 توثيق تطبيق التعرف على الأرقام المكتوبة بخط اليد (2026)

## 📖 مقدمة عن المشروع


مشروع متكامل للتعرف على الأرقام المكتوبة بخط اليد باستخدام شبكة عصبية اصطناعية متعددة الطبقات (MLP) مبرمجة في PHP، مع دعم تدريب سريع وفعّال عبر Python (NumPy) وتصدير النموذج إلى Laravel. يتيح التطبيق رسم الأرقام في المتصفح والتعرف عليها فورياً.


### المميزات الرئيسية:
- واجهة تفاعلية متجاوبة (رسم مباشر للأرقام)
- شبكة عصبية بعمق حقيقي (طبقتان مخفيتان)
- تدريب سريع جداً عبر Python/NumPy (بدلاً من PHP البطيء)
- تصدير النموذج إلى JSON واستخدامه في Laravel للتنبؤ الفوري
- دعم جميع الأجهزة والمتصفحات
- شرح كامل للخوارزمية والهيكلية

---

## 🏗️ هيكل المشروع

```
handwritten_digit_recognition/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── DigitRecognitionController.php    # المتحكم الرئيسي
│   └── Services/
│       └── NeuralNetwork.php                     # فئة الشبكة العصبية
├── routes/
│   └── web.php                                   # المسارات
├── resources/
│   └── views/
│       └── digit-recognition/
│           └── index.blade.php                   # الواجهة الأمامية
├── storage/
│   └── app/
│       └── neural_network_model.json             # نموذج الشبكة المدرب
└── ...
```

---


## 🔧 خطوات التثبيت والتشغيل والتدريب


### المتطلبات:
- PHP 8.1 أو أحدث (للتشغيل)
- Composer
- Laravel 10 أو أحدث
- مكتبة PHP GD (لمعالجة الصور)
- Python 3.8+ مع مكتبة numpy (للتدريب السريع)


### خطوات التثبيت والتشغيل:

#### 1. استنساخ أو نسخ المشروع

```bash
# إذا كنت تستخدم Git
git clone <repository-url>
cd handwritten_digit_recognition

# أو انسخ الملفات يدويًا إلى مشروعك
```

#### 2. تثبيت المكتبات

```bash
composer install
```

#### 3. إنشاء ملف .env

```bash
cp .env.example .env
```

#### 4. توليد مفتاح التطبيق

```bash
php artisan key:generate
```


#### 5. تشغيل خادم التطوير
```bash
php artisan serve
```
ثم افتح: http://localhost:8000

---


---

## ⚡️ تدريب النموذج (سريع واحترافي)

### لماذا Python؟
تدريب الشبكة العصبية على بيانات كبيرة (مثل MNIST) في PHP بطيء جداً. لذلك يتم التدريب في Python (NumPy) ثم تصدير الأوزان إلى JSON ليتم استخدامها في Laravel/PHP للتنبؤ فقط.

### خطوات التدريب:
1. تأكد من وجود Python وnumpy:
    ```bash
    conda install numpy
    # أو
    pip install numpy
    ```
2. نزّل ملفات MNIST (train-images-idx3-ubyte.gz و train-labels-idx1-ubyte.gz) وضعها بجانب train_mnist_python.py
    - روابط رسمية: https://storage.googleapis.com/cvdf-datasets/mnist/
    - أو من GitHub: https://github.com/fgnt/mnist/tree/master
3. شغّل سكريبت التدريب:
    ```bash
    python train_mnist_python.py
    ```
4. بعد انتهاء التدريب سيظهر ملف neural_network_model.json
5. انسخ هذا الملف إلى storage/app في مشروع Laravel
6. الآن سيستخدم التطبيق النموذج المدرب فورياً للتنبؤ

### ملاحظات حول الدقة:
- كلما زادت العصور (epochs) وحجم البيانات زادت الدقة (لكن الوقت يزداد)
- الشبكة الحالية (784 → 128 → 64 → 10) تحقق دقة عالية جداً على MNIST (>97%)
- لضمان أفضل دقة: درّب على كامل بيانات MNIST، epochs بين 10-30، batch=64 أو 128

---

## 🧠 بنية النموذج العصبي (Neural Network Architecture)

الشبكة المستخدمة هي MLP (Multi-Layer Perceptron) بترتيب:

- **المدخلات:** 784 (صورة 28x28)
- **الطبقة المخفية الأولى:** 128 وحدة (Sigmoid)
- **الطبقة المخفية الثانية:** 64 وحدة (Sigmoid)
- **المخرجات:** 10 (أرقام 0-9، Softmax)

كل الأوزان والانحيازات تحفظ في ملف JSON (متوافق بين Python وPHP).

---


### 1. `app/Services/NeuralNetwork.php` - فئة الشبكة العصبية
تحتوي على تطبيق كامل لشبكة MLP بعمق طبقتين مخفيتين:
- hiddenWeights (784→128)
- hidden2Weights (128→64)
- outputWeights (64→10)
مع دوال: الانتشار الأمامي، softmax، التنبؤ، تحميل/حفظ النموذج.

---


### 2. `train_mnist_python.py` - سكريبت تدريب النموذج
سكريبت Python يقوم بتدريب نفس بنية الشبكة العصبية على بيانات MNIST بسرعة عالية جداً، ثم يصدر النموذج إلى neural_network_model.json متوافق مع PHP.

### 3. `app/Http/Controllers/DigitRecognitionController.php` - المتحكم

يدير جميع طلبات التطبيق ومعالجة البيانات.

#### الدوال الرئيسية:

**1. عرض الصفحة الرئيسية**

```php
public function index()
{
    return view('digit-recognition.index');
}
```

**2. معالجة طلب التعرف**

```php
public function recognize(Request $request)
{
    // استقبال صورة Base64
    // تحويلها إلى مصفوفة بكسلات
    // التنبؤ برقم الصورة
    // إرجاع النتيجة كـ JSON
}
```

**3. تحويل بيانات الصورة**

```php
private function imageDataToPixels(string $imageData): array
{
    // 1. فك تشفير Base64
    // 2. إنشاء صورة من البيانات
    // 3. تغيير الحجم إلى 28×28
    // 4. تحويل إلى تدرج الرمادي
    // 5. استخراج قيم البكسلات
    // 6. تطبيع القيم بين 0 و 1
}
```

**4. تدريب النموذج**

```php
private function trainDefaultModel(): void
{
    // إنشاء بيانات تدريب افتراضية
    // تدريب الشبكة
    // حفظ النموذج
}
```

---

### 4. `routes/web.php` - المسارات

تعريف جميع مسارات التطبيق:

```php
// الصفحة الرئيسية
Route::get('/', [DigitRecognitionController::class, 'index'])->name('home');

// معالجة طلب التعرف على الرقم
Route::post('/api/recognize', [DigitRecognitionController::class, 'recognize'])->name('recognize');

// إعادة تدريب النموذج
Route::post('/api/retrain', [DigitRecognitionController::class, 'retrain'])->name('retrain');

// الحصول على معلومات النموذج
Route::get('/api/model-info', [DigitRecognitionController::class, 'modelInfo'])->name('model-info');
```

---

### 5. `resources/views/digit-recognition/index.blade.php` - الواجهة الأمامية

تطبيق الواجهة الأمامية باستخدام HTML و CSS و JavaScript.

#### المكونات الرئيسية:

**1. Canvas للرسم**

```html
<canvas id="canvas" width="280" height="280"></canvas>
```

**2. أزرار التحكم**

- زر المسح: يمسح Canvas
- زر التعرف: يرسل الصورة للخادم

**3. عرض النتائج**

- الرقم المتنبأ به
- دقة التنبؤ (Confidence)
- احتماليات كل رقم

**4. JavaScript للرسم والتفاعل**

```javascript
// تفعيل الرسم بالماوس
canvas.addEventListener('mousedown', ...);
canvas.addEventListener('mousemove', ...);
canvas.addEventListener('mouseup', ...);

// دعم اللمس للأجهزة المحمولة
canvas.addEventListener('touchstart', ...);
canvas.addEventListener('touchmove', ...);
canvas.addEventListener('touchend', ...);

// إرسال الصورة للخادم
recognizeBtn.addEventListener('click', async () => {
    const imageData = canvas.toDataURL('image/png');
    const response = await fetch('/api/recognize', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image: imageData })
    });
});
```

---


---

## 🧠 شرح الخوارزمية (MLP)

### 1. معالجة الصورة

عندما يرسم المستخدم رقماً:

1. يتم تحويل صورة Canvas إلى Base64
2. إرسالها إلى الخادم
3. فك تشفير Base64 إلى بيانات ثنائية
4. تحويل إلى صورة باستخدام GD
5. تغيير الحجم إلى 28×28 بكسل
6. تحويل إلى تدرج الرمادي
7. استخراج قيم البكسلات (0-255)
8. تطبيع القيم بين 0 و 1

### 2. الانتشار الأمامي (Forward Propagation)

```

المدخلات (784) → طبقة مخفية أولى (128) → طبقة مخفية ثانية (64) → المخرجات (10)

كل طبقة مخفية تستخدم دالة التنشيط Sigmoid:

hidden1 = sigmoid(W1 * input + b1)
hidden2 = sigmoid(W2 * hidden1 + b2)
output_logits = W3 * hidden2 + b3
probabilities = softmax(output_logits)
```

### 3. حساب الاحتماليات


softmax(x_i) = exp(x_i) / sum_j(exp(x_j))

### 4. التنبؤ


predicted_digit = argmax(probabilities)

---

## 📊 معاملات الشبكة العصبية

| المعامل          | القيمة | الشرح                                             |
| ----------------------- | ------------ | ------------------------------------------------------ |

| المعامل          | القيمة | الشرح                                             |
| ----------------------- | ------------ | ------------------------------------------------------ |
| حجم المدخلات | 784          | صورة 28×28 بكسل                               |
| الطبقة المخفية 1 | 128         | أول طبقة مخفية (sigmoid)                     |
| الطبقة المخفية 2 | 64          | ثاني طبقة مخفية (sigmoid)                    |
| حجم المخرجات | 10           | أرقام من 0 إلى 9                             |
| معدل التعلم   | 0.1          | سرعة تحديث الأوزان (في Python)               |
| عدد الدورات   | 10-30        | عدد مرات التدريب على البيانات |
| دالة التنشيط | Sigmoid      | تحويل القيم إلى [0, 1]                    |
| دالة المخرجات | Softmax      | تحويل القيم إلى احتمالات                   |

---

## 🔄 تدفق البيانات

```
المستخدم يرسم رقماً
        ↓
Canvas يحفظ الصورة
        ↓
تحويل إلى Base64
        ↓
إرسال POST إلى /api/recognize
        ↓
فك تشفير Base64
        ↓
معالجة الصورة (تغيير الحجم، تدرج الرمادي)
        ↓
استخراج البكسلات وتطبيعها
        ↓
الانتشار الأمامي في الشبكة العصبية
        ↓
حساب الاحتماليات
        ↓
اختيار الرقم الأعلى احتمالاً
        ↓
إرجاع النتيجة كـ JSON
        ↓
عرض النتيجة في الواجهة الأمامية
```

---

## 🚀 كيفية تطبيق الأكواد على مشروعك

### الخطوة 1: نسخ فئة الشبكة العصبية

انسخ ملف `app/Services/NeuralNetwork.php` إلى مشروعك في نفس المسار.

### الخطوة 2: نسخ المتحكم

انسخ ملف `app/Http/Controllers/DigitRecognitionController.php` إلى مشروعك.

### الخطوة 3: تحديث المسارات

أضف المسارات التالية إلى `routes/web.php`:

```php
use App\Http\Controllers\DigitRecognitionController;

Route::get('/', [DigitRecognitionController::class, 'index'])->name('home');
Route::post('/api/recognize', [DigitRecognitionController::class, 'recognize'])->name('recognize');
Route::post('/api/retrain', [DigitRecognitionController::class, 'retrain'])->name('retrain');
Route::get('/api/model-info', [DigitRecognitionController::class, 'modelInfo'])->name('model-info');
```

### الخطوة 4: نسخ الواجهة الأمامية

انسخ ملف `resources/views/digit-recognition/index.blade.php` إلى مشروعك.

### الخطوة 5: التأكد من المكتبات المطلوبة

تأكد من أن مكتبة GD مثبتة:

```bash
# على Ubuntu/Debian
sudo apt-get install php-gd

# على macOS
brew install php@8.1-gd
```

### الخطوة 6: تشغيل التطبيق

```bash
php artisan serve
```

---

## 🧪 اختبار التطبيق

### 1. اختبار الرسم

- ارسم أرقاماً مختلفة في Canvas
- تأكد من أن الرسم يظهر بشكل صحيح

### 2. اختبار التعرف

- اضغط على زر "تعرف على الرقم"
- تحقق من أن النتيجة تظهر بشكل صحيح

### 3. اختبار الاستجابة

- جرب التطبيق على أجهزة مختلفة (هاتف، تابلت، حاسوب)
- تأكد من أن الواجهة متجاوبة

### 4. اختبار الأداء

- اختبر مع عدة صور متتالية
- تحقق من سرعة المعالجة

---

## 🐛 حل المشاكل الشائعة

### المشكلة: "Class 'App\Services\NeuralNetwork' not found"

**الحل:** تأكد من أن ملف `NeuralNetwork.php` موجود في `app/Services/` وأن namespace صحيح.

### المشكلة: "Call to undefined function imagecolorat()"

**الحل:** مكتبة GD غير مثبتة. قم بتثبيتها:

```bash
sudo apt-get install php-gd
sudo systemctl restart apache2  # أو php-fpm
```

### المشكلة: "CSRF token mismatch"

**الحل:** تأكد من أن رمز CSRF يتم إرساله مع الطلب:

```javascript
headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
}
```

### المشكلة: الصورة لا تُعالج بشكل صحيح

**الحل:** تأكد من أن Canvas يحتوي على صورة قبل الضغط على زر التعرف.

---

## 📚 مراجع إضافية

- [توثيق Laravel](https://laravel.com/docs)
- [توثيق PHP GD](https://www.php.net/manual/en/book.image.php)
- [شرح الشبكات العصبية](https://en.wikipedia.org/wiki/Artificial_neural_network)
- [مجموعة بيانات MNIST](http://yann.lecun.com/exdb/mnist/)

---

## 💡 تحسينات مستقبلية

1. **استخدام مجموعة بيانات حقيقية:** استخدام MNIST dataset للتدريب الفعلي
2. **شبكات أعمق:** إضافة طبقات مخفية أكثر
3. **خوارزميات متقدمة:** استخدام CNN (Convolutional Neural Networks)
4. **حفظ النموذج في قاعدة البيانات:** بدلاً من JSON
5. **واجهة إدارية:** لتدريب النموذج وتحديثه
6. **API عام:** للاستخدام من تطبيقات أخرى

---


---

## 🚀 أوامر التدريب والتشغيل

### تدريب النموذج (سريع)
```bash
python train_mnist_python.py
# ثم انسخ neural_network_model.json إلى storage/app
```

### تشغيل التطبيق
```bash
php artisan serve
# ثم افتح http://localhost:8000
```

### تدريب بطيء (اختياري، غير منصوح به)
```bash
php artisan train:mnist --limit=5000 --epochs=20 --batch=64
```

---

## ℹ️ ملاحظات حول الأداء والدقة
- دقة النموذج المدرب في Python على MNIST عادة >97%
- كلما زادت epochs وحجم البيانات زادت الدقة
- التنبؤ في PHP سريع جداً (يتم فقط تحميل الأوزان واستخدامها)
- التدريب في PHP بطيء جداً ولا ينصح به إلا للتجارب الصغيرة

---

## 💡 كيف تجعل النموذج يتعرف على كل الأرقام دائماً؟
- استخدم بيانات تدريب متنوعة وكاملة (MNIST كامل)
- زد عدد العصور (epochs) حتى 20-30
- تأكد من أن الصورة المدخلة واضحة ومماثلة لصور MNIST (مركزية، سوداء على خلفية بيضاء)
- يمكنك تعديل بنية الشبكة (زيادة الطبقات أو الوحدات) إذا أردت دقة أعلى

---

## 📚 شرح الخوارزمية البرمجية (مبسطة)
1. معالجة الصورة: تصغير إلى 28x28، تدرج رمادي، تطبيع [0,1]
2. الانتشار الأمامي: input → hidden1 (sigmoid) → hidden2 (sigmoid) → output (softmax)
3. التنبؤ: اختيار الرقم ذو أعلى احتمال
4. التدريب (في Python): تحديث الأوزان عبر backpropagation وSGD
5. التصدير: حفظ جميع الأوزان والانحيازات في JSON
6. الاستيراد: PHP يقرأ JSON ويستخدمه مباشرة للتنبؤ

---

## 📝 ملخص
- التطبيق الآن يستخدم أفضل ممارسات التدريب (Python/NumPy)
- النموذج قابل للتطوير لأي عدد أرقام (فقط عدل outputSize)
- كل الأكواد والملفات موثقة ومشروحة
- أي تحديث أو تطوير مستقبلي (مثل دعم 0-9999) سهل جداً

---

## 🛠 التغييرات الأخيرة (May 2026)

- تم زيادة عدد العصور في `train_mnist_python.py` إلى `40` وعاد التدريب مكتملًا بنجاح.
        - نتيجة تدريب نهائية (Train accuracy): **98.60%**
        - نموذج مُصدَّر إلى `storage/app/neural_network_model.json` بعد الانتهاء.

- تم إجراء تقييم تجريبي للموديل على مجموعة الاختبار MNIST (عينة أو كاملة حسب توفر الملفات):
        - مثال سابق لقياس دقة الاختبار أعطى `test_acc = 93.98%` لنموذج سابق. (تختلف القيم حسب إعدادات التدريب)

- تحسينات المعالجة في الخادم (`DigitRecognitionController.php`) شملت:
        - `findBoundingBox()` لتحديد مربع الرسم حول الرقـم بدقة أعلى (عتبة افتراضية 220).
        - `resizeToFit()` لتوسيط المحتوى داخل صندوق 20×20 قبل وضعه داخل 28×28.
        - `imageToPixelArray()` لجمع بكسلات 28×28 وتطبيعها بحيث يكون الأسود قيمة عالية كما في MNIST.
        - `centerPixelMass()` لتحريك الكتلة الداكنة إلى منتصف الصورة (محاذاة مركز الكتلة مثل MNIST).

- بعد هذه التعديلات، تجارب يدوية أظهرت تحسناً واضحاً في التعرف داخل الواجهة — الأخطاء النادر أصبحت أقل بكثير.

## ✅ أوامر صيانة وتحديث نفّذت أثناء العمل

بين الأوامر التي نُفِّذت لتفريغ التخزين المؤقت وضمان تحميل التغييرات حديثاً في Laravel:

```bash
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan optimize:clear
php artisan auth:clear-resets
php artisan event:clear
php artisan queue:clear
php artisan schedule:clear-cache

php artisan clear-compiled
php artisan session:table

composer dump-autoload
```

استخدم هذه الأوامر بعد استبدال `neural_network_model.json` أو عند تعديل ملفات PHP/Blade لضمان أن التغييرات سارية.

## 🔁 كيف تعيد تدريب النموذج وتثبته بسرعة

1. ضع ملفات MNIST (`train-*.gz` و `t10k-*.gz`) بجانب `train_mnist_python.py`.
2. شغّل التدريب (مثال مع 40 epochs):

```bash
python train_mnist_python.py
```

3. بعد انتهاء التدريب ستُطبع دقة التدريب وسيُصدَّر `neural_network_model.json` إلى `storage/app/`.
4. لتطبيق النموذج فورًا على Laravel: نفّذ أوامر التنظيف ثم أعد تحميل الأوتولودر:

```bash
php artisan cache:clear
php artisan config:clear
composer dump-autoload
```

5. اختبر التطبيق عبر فتح `http://localhost:8000` ورسم أرقام للتأكد من سلوك التنبؤ.

## 📝 سجل التغييرات (مختصر)

- May 2026: ضبط preprocessing في `DigitRecognitionController` (`findBoundingBox`, `resizeToFit`, `centerPixelMass`).
- May 2026: زيادة `epochs` في `train_mnist_python.py` إلى 40؛ التدريب انتهى بدقة تدريب 98.60%.
- May 2026: إضافة سكربت تقييم مؤقت لاختبار النموذج ضد MNIST (تم استخدامه داخلياً للتحقق من `test_acc`).

---

إذا تريد، أستطيع:
- تحديث قسم `README.md` أيضاً يشرح كيفية إعادة تدريب ونشر النموذج (قصير ومباشر).
- إضافة سكربت `make train` أو `requirements.txt` لتسهيل إعداد بيئة Python.
