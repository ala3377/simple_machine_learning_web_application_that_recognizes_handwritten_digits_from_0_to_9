<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تطبيق التعرف على الأرقام المكتوبة بخط اليد</title>
    <script src="{{ asset('js/tailwind.js') }}"></script>
    <style>
        #canvas {
            border: 2px solid #3b82f6;
            cursor: crosshair;
            background-color: white;
            border-radius: 8px;
        }

        .digit-button {
            transition: all 0.3s ease;
        }

        .digit-button:hover {
            transform: scale(1.05);
        }

        .digit-button.active {
            background-color: #3b82f6;
            color: white;
        }

        .result-card {
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .probability-bar {
            background: linear-gradient(90deg, #3b82f6, #1e40af);
            transition: width 0.3s ease;
        }

        .loading-spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- رأس الصفحة -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-2">
                🔢 التعرف على الأرقام المكتوبة بخط اليد
            </h1>
            <p class="text-gray-600 text-lg">
                استخدم الشبكات العصبية الاصطناعية للتعرف على الأرقام التي تكتبها
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- قسم الرسم -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">📝 ارسم الرقم</h2>
                <p class="text-gray-600 mb-4">ارسم رقماً من 0 إلى 9 في المربع أدناه</p>

                <div class="flex justify-center mb-6">
                    <canvas id="canvas" width="280" height="280" class="border-4 border-blue-400"></canvas>
                </div>

                <div class="flex gap-4 flex-wrap justify-center">
                    <button id="clearBtn" class="px-6 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition duration-300">
                        🗑️ مسح
                    </button>
                    <button id="recognizeBtn" class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg transition duration-300 flex items-center gap-2">
                        <span>🤖 تعرف على الرقم</span>
                    </button>
                </div>

                <!-- معلومات عن الرسم -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm text-gray-700">
                        <strong>نصائح:</strong>
                    </p>
                    <ul class="text-sm text-gray-600 mt-2 space-y-1">
                        <li>✓ ارسم الرقم بوضوح في منتصف المربع</li>
                        <li>✓ استخدم حركات سلسة وثابتة</li>
                        <li>✓ تأكد من أن الرقم يملأ معظم المساحة</li>
                    </ul>
                </div>
            </div>

            <!-- قسم النتائج -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">📊 النتيجة</h2>

                <!-- حالة الانتظار -->
                <div id="loadingState" class="hidden text-center py-12">
                    <div class="flex justify-center mb-4">
                        <div class="loading-spinner"></div>
                    </div>
                    <p class="text-gray-600 font-semibold">جاري معالجة الصورة...</p>
                </div>

                <!-- حالة عدم وجود نتيجة -->
                <div id="emptyState" class="text-center py-12">
                    <p class="text-gray-500 text-lg">ارسم رقماً واضغط على زر "تعرف على الرقم"</p>
                </div>

                <!-- حالة النتيجة -->
                <div id="resultState" class="hidden result-card">
                    <!-- الرقم المتنبأ به -->
                    <div class="text-center mb-8">
                        <p class="text-gray-600 text-sm mb-2">الرقم المتنبأ به:</p>
                        <div class="text-7xl font-bold text-blue-600" id="predictedDigit">-</div>
                        <p class="text-gray-600 text-sm mt-2">
                            دقة التنبؤ: <span id="confidence" class="font-bold text-blue-600">0%</span>
                        </p>
                    </div>

                    <!-- احتماليات كل رقم -->
                    <div class="space-y-3">
                        <p class="text-gray-700 font-semibold text-sm">احتماليات الأرقام:</p>
                        <div id="probabilitiesContainer" class="space-y-2">
                            <!-- سيتم ملؤها بواسطة JavaScript -->
                        </div>
                    </div>

                    <!-- رسالة الخطأ -->
                    <div id="errorMessage" class="hidden mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-700 text-sm"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- معلومات عن الشبكة العصبية -->
        <div class="mt-12 bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">🧠 معلومات الشبكة العصبية</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-gray-600 text-sm">حجم المدخلات</p>
                    <p class="text-2xl font-bold text-blue-600">784</p>
                    <p class="text-xs text-gray-500">(صورة 28×28 بكسل)</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-gray-600 text-sm">عدد المخرجات</p>
                    <p class="text-2xl font-bold text-green-600">10</p>
                    <p class="text-xs text-gray-500">(أرقام 0-9)</p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <p class="text-gray-600 text-sm">معدل التعلم</p>
                    <p class="text-2xl font-bold text-purple-600">0.01</p>
                    <p class="text-xs text-gray-500">(Learning Rate)</p>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg">
                    <p class="text-gray-600 text-sm">دورات التدريب</p>
                    <p class="text-2xl font-bold text-orange-600">100</p>
                    <p class="text-xs text-gray-500">(Epochs)</p>
                </div>
            </div>
        </div>

        <!-- شرح الخوارزمية -->
        <div class="mt-8 bg-indigo-50 rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">📚 كيف تعمل الخوارزمية؟</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-bold text-gray-800 mb-2">1️⃣ معالجة الصورة</h3>
                    <p class="text-gray-700 text-sm">
                        يتم تحويل الصورة المرسومة إلى مصفوفة 28×28 بكسل، ثم تحويلها إلى تدرج الرمادي وتطبيعها بين 0 و 1.
                    </p>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 mb-2">2️⃣ الانتشار الأمامي</h3>
                    <p class="text-gray-700 text-sm">
                        يتم حساب المخرجات من خلال ضرب المدخلات بالأوزان وإضافة الانحياز، ثم تطبيق دالة التنشيط (Sigmoid).
                    </p>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 mb-2">3️⃣ حساب الاحتماليات</h3>
                    <p class="text-gray-700 text-sm">
                        يتم تحويل المخرجات إلى احتماليات باستخدام دالة Softmax، حيث تمثل كل احتمالية ثقة الشبكة في كل رقم.
                    </p>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 mb-2">4️⃣ التنبؤ</h3>
                    <p class="text-gray-700 text-sm">
                        يتم اختيار الرقم ذو أعلى احتمالية كالنتيجة النهائية للتنبؤ.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const clearBtn = document.getElementById('clearBtn');
        const recognizeBtn = document.getElementById('recognizeBtn');

        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;

        // تعيين حجم Canvas الفعلي
        canvas.width = 280;
        canvas.height = 280;

        // تعيين خصائص الرسم
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 8;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // أحداث الرسم
        canvas.addEventListener('mousedown', (e) => {
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            lastX = e.clientX - rect.left;
            lastY = e.clientY - rect.top;
        });

        canvas.addEventListener('mousemove', (e) => {
            if (!isDrawing) return;

            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.stroke();

            lastX = x;
            lastY = y;
        });

        canvas.addEventListener('mouseup', () => {
            isDrawing = false;
        });

        canvas.addEventListener('mouseout', () => {
            isDrawing = false;
        });

        // دعم اللمس (للأجهزة المحمولة)
        canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            const touch = e.touches[0];
            lastX = touch.clientX - rect.left;
            lastY = touch.clientY - rect.top;
        });

        canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            if (!isDrawing) return;

            const rect = canvas.getBoundingClientRect();
            const touch = e.touches[0];
            const x = touch.clientX - rect.left;
            const y = touch.clientY - rect.top;

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.stroke();

            lastX = x;
            lastY = y;
        });

        canvas.addEventListener('touchend', () => {
            isDrawing = false;
        });

        // زر المسح
        clearBtn.addEventListener('click', () => {
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            document.getElementById('resultState').classList.add('hidden');
            document.getElementById('emptyState').classList.remove('hidden');
        });

        // زر التعرف
        recognizeBtn.addEventListener('click', async () => {
            const imageData = canvas.toDataURL('image/png');

            // إظهار حالة الانتظار
            document.getElementById('emptyState').classList.add('hidden');
            document.getElementById('resultState').classList.add('hidden');
            document.getElementById('loadingState').classList.remove('hidden');

            try {
                const response = await fetch('/api/recognize', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ image: imageData }),
                });

                const data = await response.json();

                document.getElementById('loadingState').classList.add('hidden');

                if (data.success) {
                    // عرض النتيجة
                    document.getElementById('predictedDigit').textContent = data.predicted_digit;
                    document.getElementById('confidence').textContent = data.confidence + '%';

                    // عرض احتماليات الأرقام
                    const probabilitiesContainer = document.getElementById('probabilitiesContainer');
                    probabilitiesContainer.innerHTML = '';

                    data.probabilities.forEach((prob, digit) => {
                        const barWidth = Math.max(prob, 5); // حد أدنى للعرض
                        const html = `
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-semibold text-gray-700">${digit}</span>
                                    <span class="text-sm text-gray-600">${prob}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="probability-bar h-3 rounded-full" style="width: ${barWidth}%"></div>
                                </div>
                            </div>
                        `;
                        probabilitiesContainer.innerHTML += html;
                    });

                    document.getElementById('resultState').classList.remove('hidden');
                } else {
                    // عرض رسالة الخطأ
                    const errorDiv = document.getElementById('errorMessage');
                    errorDiv.querySelector('p').textContent = data.error || 'حدث خطأ أثناء معالجة الصورة';
                    errorDiv.classList.remove('hidden');
                    document.getElementById('resultState').classList.remove('hidden');
                }
            } catch (error) {
                document.getElementById('loadingState').classList.add('hidden');
                const errorDiv = document.getElementById('errorMessage');
                errorDiv.querySelector('p').textContent = 'خطأ في الاتصال: ' + error.message;
                errorDiv.classList.remove('hidden');
                document.getElementById('resultState').classList.remove('hidden');
            }
        });

        // إضافة رمز CSRF إذا لم يكن موجوداً
        if (!document.querySelector('meta[name="csrf-token"]')) {
            const meta = document.createElement('meta');
            meta.name = 'csrf-token';
            meta.content = '{{ csrf_token() }}';
            document.head.appendChild(meta);
        }
    </script>
</body>
</html>
